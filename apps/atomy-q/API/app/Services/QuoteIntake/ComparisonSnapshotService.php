<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\QuoteSubmission;
use App\Models\Rfq;

final readonly class ComparisonSnapshotService
{
    /**
     * @return array{rfq_version: int, normalized_lines: list<array<string, mixed>>, resolutions: list<array<string, mixed>>, currency_meta: array<string, string>, vendors: list<array<string, mixed>>}
     */
    public function freezeForRfq(string $tenantId, string $rfqId): array
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->with('lineItems')
            ->firstOrFail();

        $normalizedLines = [];
        $resolutions = [];
        $vendors = [];

        $submissions = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->with(['normalizationSourceLines.conflicts', 'normalizationSourceLines.rfqLineItem'])
            ->orderBy('created_at')
            ->get();

        foreach ($submissions as $submission) {
            if (! array_key_exists((string) $submission->vendor_id, $vendors)) {
                $vendors[(string) $submission->vendor_id] = [
                    'vendor_id' => (string) $submission->vendor_id,
                    'vendor_name' => $submission->vendor_name,
                    'quote_submission_id' => (string) $submission->id,
                ];
            }

            foreach ($submission->normalizationSourceLines as $line) {
                $normalizedLines[] = [
                    'quote_submission_id' => $submission->id,
                    'vendor_id' => $submission->vendor_id,
                    'source_line_id' => $line->id,
                    'rfq_line_item_id' => $line->rfq_line_item_id,
                    'source_description' => $line->source_description,
                    'source_unit_price' => $line->source_unit_price !== null ? (string) $line->source_unit_price : null,
                    'source_uom' => $line->source_uom,
                    'source_quantity' => $line->source_quantity !== null ? (string) $line->source_quantity : null,
                ];
                foreach ($line->conflicts as $conflict) {
                    if ($conflict->resolution !== null) {
                        $resolutions[] = [
                            'conflict_id' => $conflict->id,
                            'source_line_id' => $line->id,
                            'resolution' => $conflict->resolution,
                        ];
                    }
                }
            }
        }

        $currencyMeta = [];
        foreach ($rfq->lineItems as $lineItem) {
            $currencyMeta[(string) $lineItem->id] = (string) $lineItem->currency;
        }

        return [
            'rfq_version' => (int) $rfq->updated_at->timestamp,
            'normalized_lines' => $normalizedLines,
            'resolutions' => $resolutions,
            'currency_meta' => $currencyMeta,
            'vendors' => array_values($vendors),
        ];
    }
}
