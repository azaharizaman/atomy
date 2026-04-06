<?php

declare(strict_types=1);

namespace App\Adapters\QuoteIngestion;

use App\Models\QuoteSubmission;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionInterface;
use Nexus\QuoteIngestion\Contracts\QuoteSubmissionQueryInterface;

final class EloquentQuoteSubmissionQuery implements QuoteSubmissionQueryInterface
{
    public function find(string $tenantId, string $id): ?QuoteSubmissionInterface
    {
        return QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }
}