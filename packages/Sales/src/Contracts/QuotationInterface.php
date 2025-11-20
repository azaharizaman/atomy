<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use DateTimeImmutable;
use Nexus\Sales\Enums\QuoteStatus;
use Nexus\Sales\ValueObjects\DiscountRule;

/**
 * Sales quotation (price quote) entity contract.
 */
interface QuotationInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getQuoteNumber(): string;

    public function getCustomerId(): string;

    public function getQuoteDate(): DateTimeImmutable;

    public function getValidUntil(): ?DateTimeImmutable;

    public function getStatus(): QuoteStatus;

    public function getCurrencyCode(): string;

    public function getSubtotal(): float;

    public function getTaxAmount(): float;

    public function getDiscountAmount(): float;

    public function getTotal(): float;

    public function getDiscountRule(): ?DiscountRule;

    public function getNotes(): ?string;

    public function getPreparedBy(): string;

    public function getSentAt(): ?DateTimeImmutable;

    public function getAcceptedAt(): ?DateTimeImmutable;

    public function getConvertedToOrderId(): ?string;

    /**
     * @return SalesOrderLineInterface[]
     */
    public function getLines(): array;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function toArray(): array;
}
