<?php

declare(strict_types=1);

namespace App\Adapters\QuoteIngestion;

use App\Models\QuoteSubmission;
use App\Services\QuoteIntake\Contracts\QuoteSubmissionInterface;
use App\Services\QuoteIntake\Contracts\QuoteSubmissionQueryInterface;

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
