<?php

declare(strict_types=1);

namespace App\Adapters\QuoteIngestion;

use App\Models\QuoteSubmission;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionPersistInterface;

final class EloquentQuoteSubmissionPersist implements QuoteSubmissionPersistInterface
{
    public function updateStatus(object $submission, string $status): void
    {
        $submission->status = $status;
        $submission->save();
    }

    public function markExtracting(object $submission): void
    {
        $submission->status = 'extracting';
        $submission->processing_started_at = now();
        $submission->save();
    }

    public function markNormalizing(object $submission): void
    {
        $submission->status = 'normalizing';
        $submission->save();
    }

    public function markCompleted(object $submission, string $status, float $confidence, int $lineCount): void
    {
        $submission->status = $status;
        $submission->confidence = $confidence;
        $submission->line_items_count = $lineCount;
        $submission->processing_completed_at = now();
        $submission->parsed_at = now();
        $submission->save();
    }

    public function markFailed(object $submission, string $errorCode, ?string $errorMessage): void
    {
        $submission->status = 'failed';
        $submission->error_code = $errorCode;
        $submission->error_message = $errorMessage;
        $submission->processing_completed_at = now();
        $submission->save();
    }
}