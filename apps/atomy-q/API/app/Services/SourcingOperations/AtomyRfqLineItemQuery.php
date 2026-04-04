<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use App\Models\RfqLineItem;
use Nexus\SourcingOperations\Contracts\RfqLineItemQueryPortInterface;
use Nexus\SourcingOperations\DTOs\RfqLineItemRecord;

final readonly class AtomyRfqLineItemQuery implements RfqLineItemQueryPortInterface
{
    /**
     * @return array<int, RfqLineItemRecord>
     */
    public function findByTenantAndRfqId(string $tenantId, string $rfqId): array
    {
        return RfqLineItem::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get()
            ->map(static fn (RfqLineItem $item): RfqLineItemRecord => new RfqLineItemRecord(
                id: (string) $item->id,
                description: (string) $item->description,
                quantity: (float) $item->quantity,
                uom: (string) $item->uom,
                unitPrice: (float) $item->unit_price,
                currency: (string) $item->currency,
                specifications: $item->specifications,
                sortOrder: (int) $item->sort_order,
            ))
            ->values()
            ->all();
    }
}
