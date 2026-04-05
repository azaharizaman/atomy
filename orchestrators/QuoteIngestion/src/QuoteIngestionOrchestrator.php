<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion;

use App\Models\QuoteSubmission;
use App\Models\NormalizationSourceLine;
use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use Psr\Log\LoggerInterface;

final readonly class QuoteIngestionOrchestrator
{
    private const DECISION_SOURCE = 'quote_ingestion';
    private const DECISION_ACTION_AUTO_MAP = 'auto_map';

    public function __construct(
        private QuotationIntelligenceCoordinatorInterface $coordinator,
        private DecisionTrailWriterInterface $decisionTrailWriter,
        private LoggerInterface $logger,
    ) {}

    public function process(string $quoteSubmissionId, string $tenantId): void
    {
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $quoteSubmissionId)
            ->first();

        if ($submission === null) {
            return;
        }

        $submission->status = 'extracting';
        $submission->processing_started_at = now();
        $submission->save();

        try {
            $result = $this->coordinator->processQuote($tenantId, $quoteSubmissionId);

            $submission->status = 'normalizing';
            $submission->save();

            $lines = $result['lines'] ?? [];
            $this->persistSourceLines($submission, $lines);

            $avgConfidence = $this->calculateAvgConfidence($lines);
            $finalStatus = $avgConfidence >= 80.0 ? 'ready' : 'needs_review';
            $submission->status = $finalStatus;
            $submission->confidence = $avgConfidence;
            $submission->line_items_count = count($lines);
            $submission->processing_completed_at = now();
            $submission->parsed_at = now();
            $submission->save();

        } catch (\Throwable $e) {
            $this->handleFailure($submission, 'INTELLIGENCE_FAILED', $e->getMessage());
        }
    }

    private function persistSourceLines(QuoteSubmission $submission, array $lines): void
    {
        $sortOrder = 0;

        foreach ($lines as $line) {
            $rfqLineId = $line['rfq_line_id'] ?? null;
            if ($rfqLineId === null) {
                continue;
            }

            $normalizedLine = NormalizationSourceLine::updateOrCreate(
                [
                    'tenant_id' => $submission->tenant_id,
                    'quote_submission_id' => $submission->id,
                    'rfq_line_item_id' => $rfqLineId,
                ],
                [
                    'source_vendor' => $line['vendor_description'],
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

    private function writeDecisionTrail(QuoteSubmission $submission, string $rfqLineId, array $line): void
    {
        $confidence = (float) ($line['ai_confidence'] ?? 0);
        if ($confidence >= 80.0) {
            $this->decisionTrailWriter->write(
                $submission->tenant_id,
                $submission->id,
                self::DECISION_SOURCE,
                self::DECISION_ACTION_AUTO_MAP,
                [
                    'rfq_line_item_id' => $rfqLineId,
                    'taxonomy_code' => $line['taxonomy_code'] ?? '',
                    'confidence' => $confidence,
                    'mapping_version' => $line['metadata']['mapping_version'] ?? '',
                ]
            );
        }
    }

    private function calculateAvgConfidence(array $lines): float
    {
        if ($lines === []) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($lines as $line) {
            $total += (float) ($line['ai_confidence'] ?? 0);
        }

        $count = count($lines);
        return $count > 0 ? $total / $count : 0.0;
    }

    private function handleFailure(QuoteSubmission $submission, string $errorCode, ?string $errorMessage): void
    {
        $submission->status = 'failed';
        $submission->error_code = $errorCode;
        $submission->error_message = $errorMessage;
        $submission->processing_completed_at = now();
        $submission->save();
    }
}