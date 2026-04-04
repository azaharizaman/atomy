<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use App\Models\RfqLineItem;
use Nexus\SourcingOperations\Contracts\RfqLineItemPersistPortInterface;
use Nexus\SourcingOperations\DTOs\RfqLineItemRecord;

final readonly class AtomyRfqLineItemPersist implements RfqLineItemPersistPortInterface
{
    /**
     * @param array<int, RfqLineItemRecord> $lineItems
     */
    public function copyToRfq(string $tenantId, string $sourceRfqId, string $targetRfqId, array $lineItems): int
    {
        foreach ($lineItems as $lineItem) {
            $copy = new RfqLineItem();
            $copy->tenant_id = $tenantId;
            $copy->rfq_id = $targetRfqId;
            $copy->description = $lineItem->description;
            $copy->quantity = $lineItem->quantity;
            $copy->uom = $lineItem->uom;
            $copy->unit_price = $lineItem->unitPrice;
            $copy->currency = $lineItem->currency;
            $copy->specifications = $lineItem->specifications;
            $copy->sort_order = $lineItem->sortOrder;
            $copy->save();
        }

        return count($lineItems);
    }
}
