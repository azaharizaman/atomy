<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion;

use App\Models\QuoteSubmission;
use Nexus\MachineLearning\Contracts\QuoteExtractionServiceInterface;
use Nexus\MachineLearning\ValueObjects\QuoteExtractionResult;

final readonly class QuoteIngestionOrchestrator
{
    public function __construct(
        private QuoteExtractionServiceInterface $extractionService,
    ) {}

    /**
     * Process a quote submission through the full pipeline
     *
     * @param string $quoteSubmissionId
     * @param string $tenantId
     * @return void
     */
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
            $filePath = storage_path('app/' . $submission->file_path);
            $result = $this->extractionService->extract($filePath, $submission->tenant_id);

            if ($result->errorCode !== null) {
                $this->handleFailure($submission, $result->errorCode, $result->errorMessage);
                return;
            }

            $submission->status = 'normalizing';
            $submission->save();

            $this->persistSourceLines($submission, $result);

            $finalStatus = $result->confidence >= 80.0 ? 'ready' : 'needs_review';
            $submission->status = $finalStatus;
            $submission->confidence = $result->confidence;
            $submission->line_items_count = count($result->extractedLines);
            $submission->processing_completed_at = now();
            $submission->parsed_at = now();
            $submission->save();

        } catch (\Throwable $e) {
            $this->handleFailure($submission, 'EXTRACTION_FAILED', $e->getMessage());
        }
    }

    private function persistSourceLines(QuoteSubmission $submission, QuoteExtractionResult $result): void
    {
        $sortOrder = 0;
        foreach ($result->extractedLines as $line) {
            $submission->normalizationSourceLines()->create([
                'tenant_id' => $submission->tenant_id,
                'quote_submission_id' => $submission->id,
                'source_vendor' => $line['source_vendor'],
                'source_description' => $line['source_description'],
                'source_quantity' => $line['source_quantity'],
                'source_uom' => $line['source_uom'],
                'source_unit_price' => $line['source_unit_price'],
                'raw_data' => $line['raw_data'],
                'sort_order' => $sortOrder++,
            ]);
        }
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
