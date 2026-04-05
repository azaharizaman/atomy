<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface QuoteSubmissionPersistInterface
{
    public function updateStatus(QuoteSubmissionInterface $submission, string $status): void;

    public function markExtracting(QuoteSubmissionInterface $submission): void;

    public function markNormalizing(QuoteSubmissionInterface $submission): void;

    public function markCompleted(QuoteSubmissionInterface $submission, string $status, float $confidence, int $lineCount): void;

    public function markFailed(QuoteSubmissionInterface $submission, string $errorCode, ?string $errorMessage): void;
}