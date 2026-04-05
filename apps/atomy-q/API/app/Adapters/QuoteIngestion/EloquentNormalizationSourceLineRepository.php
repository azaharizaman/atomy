<?php

declare(strict_types=1);

namespace App\Adapters\QuoteIngestion;

use App\Models\NormalizationSourceLine;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineRepositoryInterface;

final class EloquentNormalizationSourceLineRepository implements NormalizationSourceLineRepositoryInterface
{
    public function findExisting(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId
    ): ?object {
        return NormalizationSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('quote_submission_id', $quoteSubmissionId)
            ->where('rfq_line_item_id', $rfqLineItemId)
            ->first();
    }

    public function upsert(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId,
        array $data
    ): void {
        NormalizationSourceLine::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'quote_submission_id' => $quoteSubmissionId,
                'rfq_line_item_id' => $rfqLineItemId,
            ],
            $data
        );
    }
}