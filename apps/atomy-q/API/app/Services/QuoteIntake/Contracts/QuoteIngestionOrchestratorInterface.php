<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface QuoteIngestionOrchestratorInterface
{
    public function process(string $quoteSubmissionId, string $tenantId): void;
}
