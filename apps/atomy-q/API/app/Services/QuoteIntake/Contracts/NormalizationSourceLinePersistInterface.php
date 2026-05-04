<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface NormalizationSourceLinePersistInterface
{
    public function upsert(string $tenantId, string $quoteSubmissionId, string $rfqLineItemId, array $data): void;
}
