<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface NormalizationSourceLinePersistInterface
{
    public function upsert(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId,
        array $data
    ): void;
}
