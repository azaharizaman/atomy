<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Vendor entity interface.
 *
 * Represents a vendor (supplier) with payment terms and matching tolerances.
 */
interface VendorInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getCode(): string;
    public function getName(): string;
    public function getStatus(): string;
    public function getPaymentTerms(): string;
    public function getQtyTolerancePercent(): float;
    public function getPriceTolerancePercent(): float;
    public function getTaxId(): ?string;
    public function getBankDetails(): ?array;
    public function getCurrency(): string;
    public function getEmail(): ?string;
    public function getPhone(): ?string;
    public function getAddress(): ?array;
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;
}
