<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Stock;

use Nexus\SalesOperations\Contracts\StockReservationInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class StockReservationRule implements RuleInterface
{
    public function __construct(
        private StockReservationInterface $stockReservation
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof FulfillmentRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected FulfillmentRequest'
            );
        }

        $reservationIssues = [];

        foreach ($context->lines as $line) {
            $productVariantId = $line['product_variant_id'] ?? $line->productVariantId ?? null;
            $quantity = $line['quantity'] ?? $line->quantity ?? 0;

            if ($productVariantId === null) {
                continue;
            }

            $reservedQty = $this->stockReservation->getReservedQuantity(
                $context->tenantId,
                $context->orderId,
                $productVariantId
            );

            if ($reservedQty < $quantity) {
                $reservationIssues[] = [
                    'product_variant_id' => $productVariantId,
                    'required' => $quantity,
                    'reserved' => $reservedQty,
                    'unreserved' => $quantity - $reservedQty,
                ];
            }
        }

        if (!empty($reservationIssues)) {
            $messages = array_map(
                fn($i) => sprintf(
                    '%s: required %.2f, reserved %.2f',
                    $i['product_variant_id'],
                    $i['required'],
                    $i['reserved']
                ),
                $reservationIssues
            );

            return RuleResult::fail(
                $this->getName(),
                'Stock not fully reserved: ' . implode('; ', $messages),
                ['issues' => $reservationIssues]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            'All items properly reserved'
        );
    }

    public function getName(): string
    {
        return 'stock_reservation';
    }
}
