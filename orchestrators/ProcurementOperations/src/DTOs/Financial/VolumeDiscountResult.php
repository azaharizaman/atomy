<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Volume Discount Calculation Result
 * 
 * Represents the result of applying volume discounts.
 */
final readonly class VolumeDiscountResult
{
    /**
     * @param array<VolumeDiscountTierData> $appliedTiers
     * @param array<string, Money> $discountBreakdown
     */
    public function __construct(
        public string $vendorId,
        public string $productCategoryId,
        public float $totalQuantity,
        public Money $originalAmount,
        public Money $totalDiscount,
        public Money $finalAmount,
        public array $appliedTiers,
        public array $discountBreakdown,
        public bool $hasDiscount,
        public ?\DateTimeImmutable $calculatedAt = null,
    ) {}

    /**
     * Create result with no discount applicable
     */
    public static function noDiscount(
        string $vendorId,
        string $productCategoryId,
        float $quantity,
        Money $amount,
    ): self {
        return new self(
            vendorId: $vendorId,
            productCategoryId: $productCategoryId,
            totalQuantity: $quantity,
            originalAmount: $amount,
            totalDiscount: Money::zero($amount->getCurrency()),
            finalAmount: $amount,
            appliedTiers: [],
            discountBreakdown: [],
            hasDiscount: false,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create result with single tier discount
     */
    public static function withSingleTier(
        string $vendorId,
        string $productCategoryId,
        float $quantity,
        Money $originalAmount,
        VolumeDiscountTierData $tier,
        Money $discountAmount,
    ): self {
        return new self(
            vendorId: $vendorId,
            productCategoryId: $productCategoryId,
            totalQuantity: $quantity,
            originalAmount: $originalAmount,
            totalDiscount: $discountAmount,
            finalAmount: $originalAmount->subtract($discountAmount),
            appliedTiers: [$tier],
            discountBreakdown: [$tier->tierId => $discountAmount],
            hasDiscount: !$discountAmount->isZero(),
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get effective discount percentage
     */
    public function getEffectiveDiscountPercentage(): float
    {
        if ($this->originalAmount->isZero()) {
            return 0.0;
        }

        $originalCents = $this->originalAmount->getAmountInCents();
        $discountCents = $this->totalDiscount->getAmountInCents();

        return round(($discountCents / $originalCents) * 100, 2);
    }

    /**
     * Get savings summary
     */
    public function getSavingsSummary(): string
    {
        if (!$this->hasDiscount) {
            return 'No volume discount applicable';
        }

        return sprintf(
            'Save %s (%.1f%%) on %s order',
            $this->totalDiscount->format(),
            $this->getEffectiveDiscountPercentage(),
            number_format($this->totalQuantity)
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'product_category_id' => $this->productCategoryId,
            'total_quantity' => $this->totalQuantity,
            'original_amount' => $this->originalAmount->toArray(),
            'total_discount' => $this->totalDiscount->toArray(),
            'final_amount' => $this->finalAmount->toArray(),
            'effective_discount_percentage' => $this->getEffectiveDiscountPercentage(),
            'applied_tiers' => array_map(fn($t) => $t->toArray(), $this->appliedTiers),
            'discount_breakdown' => array_map(fn($d) => $d->toArray(), $this->discountBreakdown),
            'has_discount' => $this->hasDiscount,
            'savings_summary' => $this->getSavingsSummary(),
            'calculated_at' => $this->calculatedAt?->format('c'),
        ];
    }
}
