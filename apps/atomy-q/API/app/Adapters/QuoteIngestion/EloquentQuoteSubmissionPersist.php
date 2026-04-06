<?php

declare(strict_types=1);

namespace App\Adapters\QuoteIngestion;

use App\Models\QuoteSubmission;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionPersistInterface;

final readonly class EloquentQuoteSubmissionPersist implements QuoteSubmissionPersistInterface
{
    public function updateStatus(QuoteSubmissionInterface $submission, string $status): void
    {
        /** @var QuoteSubmission $submission */
        $submission->status = $status;
        $submission->save();
    }

    public function markExtracting(QuoteSubmissionInterface $submission): void
    {
        /** @var QuoteSubmission $submission */
        $submission->status = 'extracting';
        $submission->processing_started_at = now();
        $submission->save();
    }

    public function markNormalizing(QuoteSubmissionInterface $submission): void
    {
        /** @var QuoteSubmission $submission */
        $submission->status = 'normalizing';
        $submission->save();
    }

    public function markCompleted(QuoteSubmissionInterface $submission, string $status, float $confidence, int $lineCount): void
    {
        /** @var QuoteSubmission $submission */
        $now = now();
        $submission->status = $status;
        $submission->confidence = $confidence;
        $submission->line_items_count = $lineCount;
        $submission->processing_completed_at = $now;
        $submission->parsed_at = $now;
        $submission->save();
    }

    public function markFailed(QuoteSubmissionInterface $submission, string $errorCode, ?string $errorMessage): void
    {
        /** @var QuoteSubmission $submission */
        $submission->status = 'failed';
        $submission->error_code = $errorCode;
        $submission->error_message = $errorMessage;
        $submission->processing_completed_at = now();
        $submission->save();
    }
}