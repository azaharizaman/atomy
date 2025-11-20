<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use Nexus\Sales\ValueObjects\DiscountRule;

/**
 * Price list item entity contract (product price in price list).
 */
interface PriceListItemInterface
{
    public function getId(): string;

    public function getPriceListId(): string;

    public function getProductVariantId(): string;

    public function getBasePrice(): float;

    public function getDiscountRule(): ?DiscountRule;

    /**
     * @return PriceTierInterface[]
     */
    public function getTiers(): array;

    public function toArray(): array;
}
