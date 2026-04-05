<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface QuoteSubmissionPersistInterface
{
    public function updateStatus(object $submission, string $status): void;

    public function markExtracting(object $submission): void;

    public function markNormalizing(object $submission): void;

    public function markCompleted(object $submission, string $status, float $confidence, int $lineCount): void;

    public function markFailed(object $submission, string $errorCode, ?string $errorMessage): void;
}