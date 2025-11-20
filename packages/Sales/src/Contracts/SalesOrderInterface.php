<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

use DateTimeImmutable;
use Nexus\Sales\Enums\PaymentTerm;
use Nexus\Sales\Enums\SalesOrderStatus;
use Nexus\Sales\ValueObjects\DiscountRule;

/**
 * Sales order entity contract.
 */
interface SalesOrderInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getOrderNumber(): string;

    public function getCustomerId(): string;

    public function getOrderDate(): DateTimeImmutable;

    public function getStatus(): SalesOrderStatus;

    public function getCurrencyCode(): string;

    /**
     * Locked exchange rate at confirmation (for foreign currency orders).
     * NULL for base currency.
     */
    public function getExchangeRate(): ?float;

    public function getSubtotal(): float;

    public function getTaxAmount(): float;

    public function getDiscountAmount(): float;

    public function getTotal(): float;

    public function getDiscountRule(): ?DiscountRule;

    public function getPaymentTerm(): PaymentTerm;

    public function getPaymentDueDate(): ?DateTimeImmutable;

    public function getShippingAddress(): ?string;

    public function getBillingAddress(): ?string;

    public function getCustomerPurchaseOrder(): ?string;

    public function getNotes(): ?string;

    public function getConfirmedAt(): ?DateTimeImmutable;

    public function getConfirmedBy(): ?string;

    /**
     * Future-proof field for recurring subscriptions (Phase 2).
     */
    public function isRecurring(): bool;

    /**
     * Future-proof field for recurring subscriptions (Phase 2).
     * JSON: {"frequency":"monthly","interval":1,"endDate":"2025-12-31"}
     */
    public function getRecurrenceRule(): ?string;

    /**
     * Future-proof field for sales commission tracking (Phase 2).
     */
    public function getSalespersonId(): ?string;

    /**
     * Future-proof field for sales commission tracking (Phase 2).
     */
    public function getCommissionPercentage(): ?float;

    /**
     * Future-proof field for multi-warehouse delivery (Phase 2).
     */
    public function getPreferredWarehouseId(): ?string;

    /**
     * @return SalesOrderLineInterface[]
     */
    public function getLines(): array;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function toArray(): array;
}
