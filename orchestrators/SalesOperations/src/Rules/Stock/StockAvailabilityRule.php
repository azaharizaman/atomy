<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Stock;

use Nexus\SalesOperations\Contracts\StockAvailabilityInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class StockAvailabilityRule implements RuleInterface
{
    public function __construct(
        private StockAvailabilityInterface $stockAvailability
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof FulfillmentRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected FulfillmentRequest'
            );
        }

        $shortages = [];

        foreach ($context->lines as $line) {
            $productVariantId = $line['product_variant_id'] ?? $line->productVariantId ?? null;
            $quantity = $line['quantity'] ?? $line->quantity ?? 0;

            if ($productVariantId === null) {
                continue;
            }

            $availability = $this->stockAvailability->checkAvailability(
                $context->tenantId,
                $productVariantId,
                $context->warehouseId,
                $quantity
            );

            if (!$availability->isAvailable()) {
                $shortages[] = [
                    'product_variant_id' => $productVariantId,
                    'requested' => $quantity,
                    'available' => $availability->getAvailableQuantity(),
                    'shortage' => $availability->getShortageQuantity(),
                ];
            }
        }

        if (!empty($shortages)) {
            $messages = array_map(
                fn($s) => sprintf(
                    '%s: requested %.2f, available %.2f',
                    $s['product_variant_id'],
                    $s['requested'],
                    $s['available']
                ),
                $shortages
            );

            return RuleResult::fail(
                $this->getName(),
                'Insufficient stock: ' . implode('; ', $messages),
                ['shortages' => $shortages]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            'All items available for fulfillment'
        );
    }

    public function getName(): string
    {
        return 'stock_availability';
    }
}
