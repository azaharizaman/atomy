<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Line matching result interface.
 *
 * Represents matching result for a single bill line.
 */
interface LineMatchingResultInterface
{
    public function getLineNumber(): int;
    public function isMatched(): bool;
    public function getQtyVariancePercent(): float;
    public function getPriceVariancePercent(): float;
    public function isQtyWithinTolerance(): bool;
    public function isPriceWithinTolerance(): bool;
    public function getPoQuantity(): float;
    public function getGrnQuantity(): float;
    public function getBillQuantity(): float;
    public function getPoUnitPrice(): float;
    public function getBillUnitPrice(): float;
    public function getVarianceReason(): ?string;
}
