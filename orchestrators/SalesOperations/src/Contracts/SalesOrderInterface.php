<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface SalesOrderInterface
{
    public function getId(): string;

    public function getOrderNumber(): string;

    public function getCustomerId(): string;

    public function getStatus(): string;

    public function getSubtotal(): float;

    public function getTaxAmount(): float;

    public function getTotal(): float;

    public function getCurrencyCode(): string;

    public function getPaymentTerms(): string;

    public function getShippingAddress(): ?string;

    public function getBillingAddress(): ?string;

    public function getSalespersonId(): ?string;

    public function getConfirmedAt(): ?\DateTimeImmutable;

    public function getLines(): array;

    public function isConfirmed(): bool;

    public function isCancelled(): bool;

    public function canBeCancelled(): bool;
}

interface SalesOrderLineInterface
{
    public function getId(): string;

    public function getProductVariantId(): string;

    public function getProductName(): string;

    public function getQuantity(): float;

    public function getQuantityShipped(): float;

    public function getQuantityInvoiced(): float;

    public function getUnitPrice(): float;

    public function getDiscountPercent(): float;

    public function getLineTotal(): float;

    public function getRemainingToShip(): float;

    public function getRemainingToInvoice(): float;

    public function isFullyShipped(): bool;

    public function isFullyInvoiced(): bool;
}
