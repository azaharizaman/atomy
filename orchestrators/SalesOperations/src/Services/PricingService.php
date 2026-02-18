<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class PricingService
{
    public function __construct(
        private float $defaultVolumeThreshold = 100,
        private float $defaultVolumeDiscount = 5.0,
        private float $maxDiscountPercent = 30.0,
    ) {}

    public function calculatePrice(PricingInput $input): PricingResult
    {
        $basePrice = $input->listPrice;
        $discounts = [];
        $totalDiscountPercent = 0.0;

        $volumeDiscount = $this->calculateVolumeDiscount($input);
        if ($volumeDiscount > 0) {
            $discounts['volume'] = $volumeDiscount;
            $totalDiscountPercent += $volumeDiscount;
        }

        $customerDiscount = $this->calculateCustomerDiscount($input);
        if ($customerDiscount > 0) {
            $discounts['customer'] = $customerDiscount;
            $totalDiscountPercent += $customerDiscount;
        }

        $promoDiscount = $this->calculatePromoDiscount($input);
        if ($promoDiscount > 0) {
            $discounts['promo'] = $promoDiscount;
            $totalDiscountPercent += $promoDiscount;
        }

        $totalDiscountPercent = min($totalDiscountPercent, $this->maxDiscountPercent);

        $finalPrice = $basePrice * (1 - $totalDiscountPercent / 100);
        $discountAmount = $basePrice - $finalPrice;

        return new PricingResult(
            productId: $input->productId,
            customerId: $input->customerId,
            listPrice: $basePrice,
            discounts: $discounts,
            totalDiscountPercent: $totalDiscountPercent,
            discountAmount: $discountAmount,
            finalPrice: $finalPrice,
            currencyCode: $input->currencyCode,
            quantity: $input->quantity,
            lineTotal: $finalPrice * $input->quantity,
        );
    }

    public function applyVolumePricing(array $lines): array
    {
        $results = [];

        foreach ($lines as $line) {
            $input = new PricingInput(
                productId: $line['product_id'],
                customerId: $line['customer_id'] ?? '',
                listPrice: $line['unit_price'],
                quantity: $line['quantity'],
                currencyCode: $line['currency_code'] ?? 'MYR',
            );

            $results[] = $this->calculatePrice($input);
        }

        return $results;
    }

    public function validatePriceOverride(
        float $originalPrice,
        float $overridePrice,
        string $overrideReason,
        ?string $approverId = null
    ): PriceOverrideValidation {
        $discountPercent = $originalPrice > 0
            ? (($originalPrice - $overridePrice) / $originalPrice) * 100
            : 0;

        $requiresApproval = $discountPercent > 10;
        $isValid = $discountPercent <= $this->maxDiscountPercent;

        $warnings = [];
        if ($discountPercent > 20) {
            $warnings[] = 'High discount exceeds 20%';
        }
        if ($requiresApproval && $approverId === null) {
            $warnings[] = 'Approval required for discounts over 10%';
        }

        return new PriceOverrideValidation(
            originalPrice: $originalPrice,
            overridePrice: $overridePrice,
            discountPercent: $discountPercent,
            isValid: $isValid,
            requiresApproval: $requiresApproval,
            approverId: $approverId,
            overrideReason: $overrideReason,
            warnings: $warnings,
        );
    }

    public function calculateTieredPricing(
        float $basePrice,
        array $tiers
    ): TieredPricingResult {
        usort($tiers, fn($a, $b) => ($a['min_quantity'] ?? $a['minQuantity'] ?? 0) <=> ($b['min_quantity'] ?? $b['minQuantity'] ?? 0));

        $result = [];

        foreach ($tiers as $tier) {
            $minQty = $tier['min_quantity'] ?? $tier['minQuantity'] ?? 0;
            $maxQty = $tier['max_quantity'] ?? $tier['maxQuantity'] ?? null;
            $price = $tier['price'] ?? $basePrice;
            $discount = $tier['discount_percent'] ?? $tier['discountPercent'] ?? 0;

            $tierPrice = $discount > 0 ? $basePrice * (1 - $discount / 100) : $price;

            $result[] = [
                'min_quantity' => $minQty,
                'max_quantity' => $maxQty,
                'price' => $tierPrice,
                'discount_percent' => $discount,
            ];
        }

        return new TieredPricingResult(
            basePrice: $basePrice,
            tiers: $result,
        );
    }

    public function getQuantityTier(float $basePrice, array $tiers, int $quantity): array
    {
        usort($tiers, fn($a, $b) => ($b['min_quantity'] ?? $b['minQuantity'] ?? 0) <=> ($a['min_quantity'] ?? $a['minQuantity'] ?? 0));

        foreach ($tiers as $tier) {
            $minQty = $tier['min_quantity'] ?? $tier['minQuantity'] ?? 0;

            if ($quantity >= $minQty) {
                $price = $tier['price'] ?? $basePrice;
                $discount = $tier['discount_percent'] ?? $tier['discountPercent'] ?? 0;

                return [
                    'tier' => $tier,
                    'price' => $discount > 0 ? $basePrice * (1 - $discount / 100) : $price,
                    'discount_percent' => $discount,
                ];
            }
        }

        return [
            'tier' => null,
            'price' => $basePrice,
            'discount_percent' => 0,
        ];
    }

    private function calculateVolumeDiscount(PricingInput $input): float
    {
        if ($input->quantity >= $this->defaultVolumeThreshold) {
            return $this->defaultVolumeDiscount;
        }

        if ($input->quantity >= 50) {
            return 3.0;
        }

        if ($input->quantity >= 25) {
            return 1.5;
        }

        return 0.0;
    }

    private function calculateCustomerDiscount(PricingInput $input): float
    {
        return $input->customerDiscountPercent ?? 0.0;
    }

    private function calculatePromoDiscount(PricingInput $input): float
    {
        return $input->promoDiscountPercent ?? 0.0;
    }
}
