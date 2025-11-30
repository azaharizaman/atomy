<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use DateTimeImmutable;
use Nexus\Sales\Enums\PricingStrategy;

/**
 * Price list entity contract (header).
 */
interface PriceListInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getName(): string;

    public function getCurrencyCode(): string;

    public function getStrategy(): PricingStrategy;

    public function getValidFrom(): DateTimeImmutable;

    public function getValidUntil(): ?DateTimeImmutable;

    /**
     * Customer-specific price list (NULL = default for all).
     */
    public function getCustomerId(): ?string;

    public function isActive(): bool;

    /**
     * @return PriceListItemInterface[]
     */
    public function getItems(): array;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function toArray(): array;
}
