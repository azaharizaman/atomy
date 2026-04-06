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

            $lines = $result['lines'] ?? [];
            $this->persistSourceLines($submission, $lines);

            $avgConfidence = $this->calculateAvgConfidence($lines);
            $finalStatus = $avgConfidence >= 80.0 ? 'ready' : 'needs_review';
            $this->submissionPersist->markCompleted($submission, $finalStatus, $avgConfidence, count($lines));

        } catch (\Throwable $e) {
            $this->handleFailure($submission, 'INTELLIGENCE_FAILED', $e->getMessage());
        } finally {
            $this->tenantContext->clearTenant();
        }
    }

    private function persistSourceLines(QuoteSubmissionInterface $submission, array $lines): void
    {
        $sortOrder = 0;
        $tenantId = $submission->getTenantId();
        $quoteSubmissionId = $submission->getId();
        $vendorName = $submission->getVendorName();

        foreach ($lines as $line) {
            $rfqLineId = $line['rfq_line_id'] ?? null;
            if ($rfqLineId === null) {
                continue;
            }

            $existingLine = $this->sourceLineQuery->findExisting($tenantId, $quoteSubmissionId, $rfqLineId);

            if ($existingLine !== null) {
                $existingRaw = is_array($existingLine->raw_data) ? $existingLine->raw_data : [];
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
                [
                    'source_vendor' => $vendorName,
                    'source_description' => $line['vendor_description'],
                    'source_quantity' => (float) ($line['quoted_quantity'] ?? 0),
                    'source_uom' => $line['quoted_unit'] ?? 'EA',
                    'source_unit_price' => (float) ($line['quoted_unit_price'] ?? 0),
                    'raw_data' => [
                        'quoted_quantity' => $line['quoted_quantity'],
                        'quoted_unit' => $line['quoted_unit'],
                        'quoted_unit_price' => $line['quoted_unit_price'],
                        'normalized_quantity' => $line['normalized_quantity'] ?? null,
                        'normalized_unit_price' => $line['normalized_unit_price'] ?? null,
                    ],
                    'sort_order' => $sortOrder,
                    'ai_confidence' => (float) ($line['ai_confidence'] ?? 0),
                    'taxonomy_code' => $line['taxonomy_code'] ?? '',
                    'mapping_version' => $line['metadata']['mapping_version'] ?? '',
                ]
            );

            $this->writeDecisionTrail(
                $submission,
                $rfqLineId,
                $line
            );

            $sortOrder++;
        }
    }

    private function writeDecisionTrail(QuoteSubmissionInterface $submission, string $rfqLineId, array $line): void
    {
        $confidence = (float) ($line['ai_confidence'] ?? 0);
        if ($confidence >= 80.0) {
            $this->decisionTrailWriter->write(
                $submission->getTenantId(),
                $submission->getRfqId(),
                [
                    [
                        'event_type' => self::DECISION_ACTION_AUTO_MAP,
                        'payload' => [
                            'quote_submission_id' => $submission->getId(),
                            'rfq_line_item_id' => $rfqLineId,
                            'taxonomy_code' => $line['taxonomy_code'] ?? '',
                            'confidence' => $confidence,
                            'mapping_version' => $line['metadata']['mapping_version'] ?? '',
                        ],
                    ],
                ]
            );
        }
    }

    private function calculateAvgConfidence(array $lines): float
    {
        if ($lines === []) {
            return 0.0;
        }

        return array_sum(array_column($lines, 'ai_confidence')) / count($lines);
    }

    private function handleFailure(QuoteSubmissionInterface $submission, string $errorCode, ?string $errorMessage): void
    {
        $this->submissionPersist->markFailed($submission, $errorCode, $errorMessage);
    }
}
