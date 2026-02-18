<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Order;

use Nexus\SalesOperations\DTOs\CreateOrderRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class OrderMinimumRule implements RuleInterface
{
    public function __construct(
        private float $minimumOrderAmount = 0.00
    ) {}

    public function check(object $context): RuleResult
    {
        if (!$context instanceof CreateOrderRequest) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type: expected CreateOrderRequest'
            );
        }

        $orderTotal = $this->calculateOrderTotal($context);

        if ($orderTotal < $this->minimumOrderAmount) {
            return RuleResult::fail(
                $this->getName(),
                sprintf(
                    'Order total %.2f is below minimum %.2f',
                    $orderTotal,
                    $this->minimumOrderAmount
                ),
                [
                    'order_total' => $orderTotal,
                    'minimum' => $this->minimumOrderAmount,
                ]
            );
        }

        return RuleResult::pass(
            $this->getName(),
            sprintf('Order meets minimum amount: %.2f >= %.2f', $orderTotal, $this->minimumOrderAmount)
        );
    }

    public function getName(): string
    {
        return 'order_minimum';
    }

    private function calculateOrderTotal(CreateOrderRequest $request): float
    {
        $total = 0.0;

        foreach ($request->lines as $line) {
            $quantity = $line['quantity'] ?? $line->quantity ?? 0;
            $unitPrice = $line['unit_price'] ?? $line->unitPrice ?? 0;
            $discountPercent = $line['discount_percent'] ?? $line->discountPercent ?? 0;

            $lineTotal = $quantity * $unitPrice;
            $discount = $lineTotal * ($discountPercent / 100);
            $total += $lineTotal - $discount;
        }

        return $total;
    }
}
