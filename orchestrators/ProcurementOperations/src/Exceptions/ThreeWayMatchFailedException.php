<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for three-way match tolerance exceeded.
 */
class ThreeWayMatchFailedException extends MatchingException
{
    /**
     * @param string $vendorBillId
     * @param string $purchaseOrderId
     * @param float $priceVariancePercent
     * @param float $quantityVariancePercent
     * @param float $priceTolerancePercent
     * @param float $quantityTolerancePercent
     * @param array<string, array{
     *     type: string,
     *     field: string,
     *     expected: mixed,
     *     actual: mixed,
     *     variancePercent: float
     * }> $variances
     */
    public function __construct(
        public readonly string $vendorBillId,
        public readonly string $purchaseOrderId,
        public readonly float $priceVariancePercent,
        public readonly float $quantityVariancePercent,
        public readonly float $priceTolerancePercent,
        public readonly float $quantityTolerancePercent,
        public readonly array $variances
    ) {
        $message = sprintf(
            'Three-way match failed for invoice %s against PO %s. ' .
            'Price variance: %.2f%% (tolerance: %.2f%%), ' .
            'Quantity variance: %.2f%% (tolerance: %.2f%%)',
            $vendorBillId,
            $purchaseOrderId,
            $priceVariancePercent,
            $priceTolerancePercent,
            $quantityVariancePercent,
            $quantityTolerancePercent
        );

        parent::__construct($message);
    }

    /**
     * Check if price variance exceeded.
     */
    public function isPriceVarianceExceeded(): bool
    {
        return $this->priceVariancePercent > $this->priceTolerancePercent;
    }

    /**
     * Check if quantity variance exceeded.
     */
    public function isQuantityVarianceExceeded(): bool
    {
        return $this->quantityVariancePercent > $this->quantityTolerancePercent;
    }

    /**
     * Get line-level variances.
     *
     * @return array<string, array{
     *     type: string,
     *     field: string,
     *     expected: mixed,
     *     actual: mixed,
     *     variancePercent: float
     * }>
     */
    public function getVariances(): array
    {
        return $this->variances;
    }
}
