<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface NormalizationSourceLineQueryInterface
{
    public function findExisting(string $tenantId, string $quoteSubmissionId, string $rfqLineItemId): ?NormalizationSourceLineReadInterface;
}
