<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface NormalizationSourceLineQueryInterface
{
    public function findExisting(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId
    ): ?object;
}
