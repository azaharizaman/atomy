<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface QuotationInterface
{
    public function getId(): string;

    public function getQuotationNumber(): string;

    public function getCustomerId(): string;

    public function getStatus(): string;

    public function getSubtotal(): float;

    public function getTaxAmount(): float;

    public function getTotal(): float;

    public function getCurrencyCode(): string;

    public function getValidUntil(): ?\DateTimeImmutable;

    public function getLines(): array;

    public function isExpired(): bool;

    public function isConvertible(): bool;
}

interface QuotationLineInterface
{
    public function getId(): string;

    public function getProductVariantId(): string;

    public function getProductName(): string;

    public function getQuantity(): float;

    public function getUnitPrice(): float;

    public function getDiscountPercent(): float;

    public function getLineTotal(): float;
}
