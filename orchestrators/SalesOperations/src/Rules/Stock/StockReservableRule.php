<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Stock;

use Nexus\SalesOperations\Contracts\StockAvailabilityInterface;
use Nexus\SalesOperations\DTOs\CreateOrderRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class StockReservableRule implements RuleInterface
{
    public function __construct(
        private StockAvailabilityInterface $stockAvailability
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof CreateOrderRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected CreateOrderRequest'
            );
        }

        $unreservableItems = [];

        foreach ($context->lines as $line) {
            $productVariantId = $line['product_variant_id'] ?? $line->productVariantId ?? null;
            $quantity = $line['quantity'] ?? $line->quantity ?? 0;

            if ($productVariantId === null) {
                continue;
            }

            $totalAvailable = $this->stockAvailability->getTotalAvailableQuantity(
                $context->tenantId,
                $productVariantId
            );

            if ($totalAvailable < $quantity) {
                $unreservableItems[] = [
                    'product_variant_id' => $productVariantId,
                    'requested' => $quantity,
                    'total_available' => $totalAvailable,
                    'shortage' => $quantity - $totalAvailable,
                ];
            }
        }

        if (!empty($unreservableItems)) {
            $messages = array_map(
                fn($i) => sprintf(
                    '%s: requested %.2f, available %.2f',
                    $i['product_variant_id'],
                    $i['requested'],
                    $i['total_available']
                ),
                $unreservableItems
            );

            return RuleResult::fail(
                $this->getName(),
                'Insufficient stock to reserve: ' . implode('; ', $messages),
                ['items' => $unreservableItems]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            'All items can be reserved'
        );
    }

    public function getName(): string
    {
        return 'stock_reservable';
    }
}
