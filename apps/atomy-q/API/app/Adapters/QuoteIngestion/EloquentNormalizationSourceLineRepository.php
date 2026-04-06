<?php

declare(strict_types=1);

namespace App\Adapters\QuoteIngestion;

use App\Models\NormalizationSourceLine;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineReadInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLineQueryInterface;
use Nexus\QuoteIngestion\Contracts\NormalizationSourceLinePersistInterface;

final class EloquentNormalizationSourceLineRepository implements NormalizationSourceLineQueryInterface, NormalizationSourceLinePersistInterface
{
    public function findExisting(
        string $tenantId,
        string $quoteSubmissionId,
        string $rfqLineItemId
    ): ?NormalizationSourceLineReadInterface {
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
        $filtered = array_diff_key($data, array_flip(['tenant_id', 'quote_submission_id', 'rfq_line_item_id']));

        NormalizationSourceLine::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'quote_submission_id' => $quoteSubmissionId,
                'rfq_line_item_id' => $rfqLineItemId,
            ],
            $filtered
        );
    }
}