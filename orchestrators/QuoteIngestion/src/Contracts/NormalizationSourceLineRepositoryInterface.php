<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

interface NormalizationSourceLineRepositoryInterface
{
    public function findExisting(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId
    ): ?object;

    public function upsert(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId,
        array $data
    ): void;
}