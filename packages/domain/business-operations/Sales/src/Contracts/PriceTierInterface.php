<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Price tier entity contract (quantity-based discount tier).
 */
interface PriceTierInterface
{
    public function getId(): string;

    public function getPriceListItemId(): string;

    public function getMinQuantity(): float;

    public function getMaxQuantity(): ?float;

    public function getUnitPrice(): float;

    public function getDiscountPercentage(): ?float;

    public function appliesToQuantity(float $quantity): bool;

    public function toArray(): array;
}
