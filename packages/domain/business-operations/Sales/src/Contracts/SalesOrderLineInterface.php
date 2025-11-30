<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use Nexus\Sales\ValueObjects\DiscountRule;

/**
 * Sales order line (quotation line or order line) entity contract.
 */
interface SalesOrderLineInterface
{
    public function getId(): string;

    public function getParentId(): string;

    public function getProductVariantId(): string;

    public function getQuantity(): float;

    public function getUomCode(): string;

    public function getUnitPrice(): float;

    public function getLineSubtotal(): float;

    public function getTaxAmount(): float;

    public function getDiscountAmount(): float;

    public function getLineTotal(): float;

    public function getDiscountRule(): ?DiscountRule;

    public function getLineNotes(): ?string;

    public function getLineSequence(): int;

    public function toArray(): array;
}
