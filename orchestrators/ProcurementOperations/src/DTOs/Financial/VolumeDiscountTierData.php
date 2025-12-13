<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Volume Discount Tier Data
 * 
 * Represents a tier in a volume-based discount structure.
 */
final readonly class VolumeDiscountTierData
{
    public function __construct(
        public string $tierId,
        public string $vendorId,
        public string $productCategoryId,
        public float $minQuantity,
        public ?float $maxQuantity,
        public float $discountPercentage,
        public ?Money $fixedDiscountAmount = null,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo = null,
        public bool $isActive = true,
        public int $priority = 0,
        public array $metadata = [],
    ) {}

    /**
     * Create percentage-based tier
     */
    public static function percentageTier(
        string $tierId,
        string $vendorId,
        string $productCategoryId,
        float $minQuantity,
        ?float $maxQuantity,
        float $discountPercentage,
        \DateTimeImmutable $effectiveFrom,
        ?\DateTimeImmutable $effectiveTo = null,
        int $priority = 0,
    ): self {
        return new self(
            tierId: $tierId,
            vendorId: $vendorId,
            productCategoryId: $productCategoryId,
            minQuantity: $minQuantity,
            maxQuantity: $maxQuantity,
            discountPercentage: $discountPercentage,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
            priority: $priority,
        );
    }

    /**
     * Create fixed amount tier
     */
    public static function fixedAmountTier(
        string $tierId,
        string $vendorId,
        string $productCategoryId,
        float $minQuantity,
        ?float $maxQuantity,
        Money $fixedDiscount,
        \DateTimeImmutable $effectiveFrom,
        ?\DateTimeImmutable $effectiveTo = null,
        int $priority = 0,
    ): self {
        return new self(
            tierId: $tierId,
            vendorId: $vendorId,
            productCategoryId: $productCategoryId,
            minQuantity: $minQuantity,
            maxQuantity: $maxQuantity,
            discountPercentage: 0.0,
            fixedDiscountAmount: $fixedDiscount,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
            priority: $priority,
        );
    }

    /**
     * Check if quantity falls within this tier
     */
    public function appliesTo(float $quantity): bool
    {
        if ($quantity < $this->minQuantity) {
            return false;
        }

        if ($this->maxQuantity !== null && $quantity > $this->maxQuantity) {
            return false;
        }

        return true;
    }

    /**
     * Check if tier is currently effective
     */
    public function isEffective(?\DateTimeImmutable $asOf = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $checkDate = $asOf ?? new \DateTimeImmutable();

        if ($checkDate < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $checkDate > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount for given amount
     */
    public function calculateDiscount(Money $amount): Money
    {
        if ($this->fixedDiscountAmount !== null) {
            return $this->fixedDiscountAmount;
        }

        return $amount->multiply($this->discountPercentage / 100);
    }

    /**
     * Get tier range description
     */
    public function getRangeDescription(): string
    {
        if ($this->maxQuantity === null) {
            return sprintf('%.0f+', $this->minQuantity);
        }

        return sprintf('%.0f - %.0f', $this->minQuantity, $this->maxQuantity);
    }

    /**
     * Get discount description
     */
    public function getDiscountDescription(): string
    {
        if ($this->fixedDiscountAmount !== null) {
            return sprintf('%s off', $this->fixedDiscountAmount->format());
        }

        return sprintf('%.1f%% off', $this->discountPercentage);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'tier_id' => $this->tierId,
            'vendor_id' => $this->vendorId,
            'product_category_id' => $this->productCategoryId,
            'min_quantity' => $this->minQuantity,
            'max_quantity' => $this->maxQuantity,
            'discount_percentage' => $this->discountPercentage,
            'fixed_discount_amount' => $this->fixedDiscountAmount?->toArray(),
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo?->format('Y-m-d'),
            'is_active' => $this->isActive,
            'priority' => $this->priority,
            'range_description' => $this->getRangeDescription(),
            'discount_description' => $this->getDiscountDescription(),
        ];
    }
}
