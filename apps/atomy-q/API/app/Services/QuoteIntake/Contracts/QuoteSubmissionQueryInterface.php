<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface QuoteSubmissionQueryInterface
{
    public function find(string $tenantId, string $id): ?QuoteSubmissionInterface;
}
