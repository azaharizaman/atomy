<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\LineMatchingResultInterface;

/**
 * Line matching result implementation.
 */
final readonly class LineMatchingResult implements LineMatchingResultInterface
{
    public function __construct(
        private int $lineNumber,
        private bool $matched,
        private float $qtyVariancePercent,
        private float $priceVariancePercent,
        private bool $qtyWithinTolerance,
        private bool $priceWithinTolerance,
        private float $poQuantity,
        private float $grnQuantity,
        private float $billQuantity,
        private float $poUnitPrice,
        private float $billUnitPrice,
        private ?string $varianceReason = null
    ) {}

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function isMatched(): bool
    {
        return $this->matched;
    }

    public function getQtyVariancePercent(): float
    {
        return $this->qtyVariancePercent;
    }

    public function getPriceVariancePercent(): float
    {
        return $this->priceVariancePercent;
    }

    public function isQtyWithinTolerance(): bool
    {
        return $this->qtyWithinTolerance;
    }

    public function isPriceWithinTolerance(): bool
    {
        return $this->priceWithinTolerance;
    }

    public function getPoQuantity(): float
    {
        return $this->poQuantity;
    }

    public function getGrnQuantity(): float
    {
        return $this->grnQuantity;
    }

    public function getBillQuantity(): float
    {
        return $this->billQuantity;
    }

    public function getPoUnitPrice(): float
    {
        return $this->poUnitPrice;
    }

    public function getBillUnitPrice(): float
    {
        return $this->billUnitPrice;
    }

    public function getVarianceReason(): ?string
    {
        return $this->varianceReason;
    }
}
