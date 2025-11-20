<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Vendor bill line entity interface.
 */
interface VendorBillLineInterface
{
    public function getId(): string;
    public function getBillId(): string;
    public function getLineNumber(): int;
    public function getDescription(): string;
    public function getQuantity(): float;
    public function getUnitPrice(): float;
    public function getLineAmount(): float;
    public function getGlAccount(): string;
    public function getTaxCode(): ?string;
    public function getPoLineReference(): ?string;
    public function getGrnLineReference(): ?string;
}
