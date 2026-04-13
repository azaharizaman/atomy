<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion;

use Nexus\QuoteIngestion\Contracts\QuoteSubmissionInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionQueryInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionPersistInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineQueryInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLinePersistInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;

final readonly class QuoteIngestionOrchestrator
{
    private const DECISION_ACTION_AUTO_MAP = 'auto_map';
    private const CONFIDENCE_THRESHOLD = 80.0;
    private const FAILURE_MESSAGE_GENERIC = 'Quote intelligence processing failed.';

    public function __construct(
        private QuotationIntelligenceCoordinatorInterface $coordinator,
        private DecisionTrailWriterInterface $decisionTrailWriter,
        private TenantContextInterface $tenantContext,
        private LoggerInterface $logger,
        private QuoteSubmissionQueryInterface $submissionQuery,
        private QuoteSubmissionPersistInterface $submissionPersist,
        private NormalizationSourceLineQueryInterface $sourceLineQuery,
        private NormalizationSourceLinePersistInterface $sourceLinePersist,
    ) {}

    public function process(string $quoteSubmissionId, string $tenantId): void
    {
        $submission = $this->submissionQuery->find($tenantId, $quoteSubmissionId);

        if ($submission === null) {
            $this->logger->error('Quote submission not found', [
                'tenant_id' => $tenantId,
                'quote_submission_id' => $quoteSubmissionId,
            ]);
            return;
        }

        $this->tenantContext->setTenant($tenantId);

        $this->submissionPersist->markExtracting($submission);

        try {
            $result = $this->coordinator->processQuote($tenantId, $quoteSubmissionId);

            $this->submissionPersist->markNormalizing($submission);

            $lines = is_array($result['lines'] ?? null) ? $result['lines'] : [];
            ['persisted_count' => $persistedLineCount, 'confidences' => $confidences] = $this->persistSourceLines($submission, $lines);

            $avgConfidence = $this->calculateAvgConfidence($confidences);
            $finalStatus = $avgConfidence >= self::CONFIDENCE_THRESHOLD ? 'ready' : 'needs_review';
            $this->submissionPersist->markCompleted($submission, $finalStatus, $avgConfidence, $persistedLineCount);

        } catch (\Throwable $e) {
            $this->logger->error('Quote intelligence processing failed', [
                'tenant_id' => $tenantId,
                'quote_submission_id' => $quoteSubmissionId,
                'error_class' => $e::class,
                'error_message' => $e->getMessage(),
            ]);
            $this->handleFailure($submission, 'INTELLIGENCE_FAILED', self::FAILURE_MESSAGE_GENERIC);
        } finally {
            $this->tenantContext->clearTenant();
        }
    }

    /**
     * @return array{persisted_count: int, confidences: array<int, float>}
     */
    private function persistSourceLines(QuoteSubmissionInterface $submission, array $lines): array
    {
        $sortOrder = 0;
        $persistedLineCount = 0;
        $persistedConfidences = [];
        $tenantId = $submission->getTenantId();
        $quoteSubmissionId = $submission->getId();
        $vendorName = $submission->getVendorName();

        foreach ($lines as $lineIndex => $line) {
            if (!is_array($line)) {
                $this->logger->warning('Skipping malformed quote line payload', [
                    'tenant_id' => $tenantId,
                    'quote_submission_id' => $quoteSubmissionId,
                    'line_index' => $lineIndex,
                ]);
                continue;
            }

            $rfqLineIdRaw = $line['rfq_line_id'] ?? null;
            if (!is_scalar($rfqLineIdRaw) || (string) $rfqLineIdRaw === '') {
                $this->logger->warning('Skipping quote line without valid rfq line id', [
                    'tenant_id' => $tenantId,
                    'quote_submission_id' => $quoteSubmissionId,
                    'line_index' => $lineIndex,
                ]);
                continue;
            }

            $rfqLineId = (string) $rfqLineIdRaw;
            $existingLine = $this->sourceLineQuery->findExisting($tenantId, $quoteSubmissionId, $rfqLineId);

            if ($existingLine !== null) {
                $existingRaw = $existingLine->getRawData();
                if (array_key_exists('override', $existingRaw)) {
                    $this->logger->info('Skipping source line due to existing override', [
                        'tenant_id' => $tenantId,
                        'quote_submission_id' => $quoteSubmissionId,
                        'rfq_line_id' => $rfqLineId,
                    ]);
                    continue;
                }
            }

            $this->sourceLinePersist->upsert(
                $tenantId,
                $quoteSubmissionId,
                $rfqLineId,
                $this->buildSourceLinePayload($submission, $rfqLineId, $line, $sortOrder, $vendorName)
            );

            $this->writeDecisionTrail(
                $submission,
                $rfqLineId,
                $line
            );

            $confidence = $this->extractFiniteConfidence($line);
            if ($confidence !== null) {
                $persistedConfidences[] = $confidence;
            }

            $sortOrder++;
            $persistedLineCount++;
        }

        return [
            'persisted_count' => $persistedLineCount,
            'confidences' => $persistedConfidences,
        ];
    }

    private function writeDecisionTrail(QuoteSubmissionInterface $submission, string $rfqLineId, array $line): void
    {
        $confidence = $this->extractFiniteConfidence($line) ?? 0.0;
        if ($confidence >= self::CONFIDENCE_THRESHOLD) {
            $this->decisionTrailWriter->write(
                $submission->getTenantId(),
                $submission->getRfqId(),
                [
                    [
                        'event_type' => self::DECISION_ACTION_AUTO_MAP,
                        'payload' => [
                            'quote_submission_id' => $submission->getId(),
                            'rfq_line_item_id' => $rfqLineId,
                            'taxonomy_code' => $this->extractStringValue($line, 'taxonomy_code'),
                            'confidence' => $confidence,
                            'mapping_version' => $this->extractMappingVersion($line),
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * @param array<int, float> $confidences
     */
    private function calculateAvgConfidence(array $confidences): float
    {
        if ($confidences === []) {
            return 0.0;
        }

        return array_sum($confidences) / count($confidences);
    }

    private function handleFailure(QuoteSubmissionInterface $submission, string $errorCode, ?string $errorMessage): void
    {
        $this->submissionPersist->markFailed($submission, $errorCode, $errorMessage);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSourceLinePayload(
        QuoteSubmissionInterface $submission,
        string $rfqLineId,
        array $line,
        int $sortOrder,
        string $vendorName
    ): array {
        $missingFields = [];
        if (!array_key_exists('quoted_quantity', $line)) {
            $missingFields[] = 'quoted_quantity';
        }

        if (!array_key_exists('quoted_unit', $line)) {
            $missingFields[] = 'quoted_unit';
        }

        if (!array_key_exists('quoted_unit_price', $line)) {
            $missingFields[] = 'quoted_unit_price';
        }

        if (!array_key_exists('vendor_description', $line)) {
            $missingFields[] = 'vendor_description';
        }

        if ($missingFields !== []) {
            $this->logger->warning('Missing upstream quote data for normalization source line', [
                'tenant_id' => $submission->getTenantId(),
                'quote_submission_id' => $submission->getId(),
                'rfq_id' => $submission->getRfqId(),
                'rfq_line_id' => $rfqLineId,
                'missing_fields' => $missingFields,
            ]);
        }

        return [
            'source_vendor' => $vendorName,
            'source_description' => $this->extractStringValue($line, 'vendor_description'),
            'source_quantity' => (float) ($line['quoted_quantity'] ?? 0),
            'source_uom' => $this->extractStringValue($line, 'quoted_unit', 'EA'),
            'source_unit_price' => (float) ($line['quoted_unit_price'] ?? 0),
            'raw_data' => [
                'quoted_quantity' => $line['quoted_quantity'] ?? null,
                'quoted_unit' => $line['quoted_unit'] ?? null,
                'quoted_unit_price' => $line['quoted_unit_price'] ?? null,
                'normalized_quantity' => $line['normalized_quantity'] ?? null,
                'normalized_unit_price' => $line['normalized_unit_price'] ?? null,
            ],
            'sort_order' => $sortOrder,
            'ai_confidence' => $this->extractFiniteConfidence($line) ?? 0.0,
            'taxonomy_code' => $this->extractStringValue($line, 'taxonomy_code'),
            'mapping_version' => $this->extractMappingVersion($line),
        ];
    }

    private function extractFiniteConfidence(array $line): ?float
    {
        if (!is_numeric($line['ai_confidence'] ?? null)) {
            return null;
        }

        $confidence = (float) $line['ai_confidence'];
        if (!is_finite($confidence)) {
            return null;
        }

        return $confidence;
    }

    private function extractMappingVersion(array $line): string
    {
        $metadata = $line['metadata'] ?? null;
        if (!is_array($metadata) || !is_scalar($metadata['mapping_version'] ?? null)) {
            return '';
        }

        return (string) $metadata['mapping_version'];
    }

    private function extractStringValue(array $line, string $key, string $default = ''): string
    {
        if (!is_scalar($line[$key] ?? null)) {
            return $default;
        }

        return (string) $line[$key];
    }
}
