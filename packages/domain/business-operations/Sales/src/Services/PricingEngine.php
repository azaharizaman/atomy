<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use DateTimeImmutable;
use Nexus\Sales\Contracts\PriceListItemInterface;
use Nexus\Sales\Contracts\PriceListRepositoryInterface;
use Nexus\Sales\Enums\PricingStrategy;
use Nexus\Sales\Exceptions\PriceNotFoundException;
use Nexus\Uom\Contracts\QuantityInterface;

/**
 * Pricing engine for calculating product prices based on strategy.
 */
final readonly class PricingEngine
{
    public function __construct(
        private PriceListRepositoryInterface $priceListRepository
    ) {}

    /**
     * Get price for a product variant.
     *
     * @param string $tenantId
     * @param string $productVariantId
     * @param QuantityInterface $quantity Quantity with UOM
     * @param string $currencyCode
     * @param string|null $customerId Customer ID for customer-specific pricing
     * @param DateTimeImmutable|null $asOf Date for price validity check (default: now)
     * @return float Unit price in specified currency
     * @throws PriceNotFoundException
     */
    public function getPrice(
        string $tenantId,
        string $productVariantId,
        QuantityInterface $quantity,
        string $currencyCode,
        ?string $customerId = null,
        ?DateTimeImmutable $asOf = null
    ): float {
        $asOf ??= new DateTimeImmutable();

        // 1. Try customer-specific price list (if customer provided)
        if ($customerId !== null) {
            $customerPriceLists = $this->priceListRepository->findActiveByCustomer(
                $tenantId,
                $customerId,
                $asOf
            );

            foreach ($customerPriceLists as $priceList) {
                if ($priceList->getCurrencyCode() !== $currencyCode) {
                    continue;
                }

                $price = $this->findPriceInPriceList($priceList->getItems(), $productVariantId, $quantity);
                if ($price !== null) {
                    return $price;
                }
            }
        }

        // 2. Try default price list
        $defaultPriceLists = $this->priceListRepository->findDefaultActive($tenantId, $asOf);

        foreach ($defaultPriceLists as $priceList) {
            if ($priceList->getCurrencyCode() !== $currencyCode) {
                continue;
            }

            $price = $this->findPriceInPriceList($priceList->getItems(), $productVariantId, $quantity);
            if ($price !== null) {
                return $price;
            }
        }

        // 3. No price found
        throw PriceNotFoundException::forProduct($productVariantId, $customerId ?? 'default', $currencyCode);
    }

    /**
     * Find price in price list items.
     *
     * @param PriceListItemInterface[] $items
     * @param string $productVariantId
     * @param QuantityInterface $quantity
     * @return float|null Unit price or null if not found
     */
    private function findPriceInPriceList(
        array $items,
        string $productVariantId,
        QuantityInterface $quantity
    ): ?float {
        foreach ($items as $item) {
            if ($item->getProductVariantId() !== $productVariantId) {
                continue;
            }

            // Check for quantity-based tiered pricing
            $tiers = $item->getTiers();
            if (count($tiers) > 0) {
                foreach ($tiers as $tier) {
                    if ($tier->appliesToQuantity($quantity->getValue())) {
                        return $tier->getUnitPrice();
                    }
                }
            }

            // No applicable tier, use base price
            return $item->getBasePrice();
        }

        return null;
    }

    /**
     * Calculate discount amount for a line.
     *
     * @param float $lineSubtotal
     * @param \Nexus\Sales\ValueObjects\DiscountRule|null $discountRule
     * @param DateTimeImmutable|null $asOf
     * @return float Discount amount
     */
    public function calculateDiscount(
        float $lineSubtotal,
        ?\Nexus\Sales\ValueObjects\DiscountRule $discountRule,
        ?DateTimeImmutable $asOf = null
    ): float {
        if ($discountRule === null) {
            return 0.0;
        }

        $asOf ??= new DateTimeImmutable();

        if (!$discountRule->isValidAt($asOf)) {
            return 0.0;
        }

        if ($discountRule->getType()->isPercentage()) {
            return round($lineSubtotal * ($discountRule->getValue() / 100), 2);
        }

        // Fixed amount discount
        return min($discountRule->getValue(), $lineSubtotal);
    }
}
