<?php

declare(strict_types=1);

namespace Nexus\Sales\ValueObjects;

use DateTimeImmutable;

/**
 * Data transfer object for creating a sales order.
 */
final readonly class SalesOrderData
{
    /**
     * @param string $tenantId
     * @param string $customerId
     * @param string $currencyCode
     * @param DateTimeImmutable|string $quoteDate
     * @param array<int, array{product_id: string, quantity: float, unit_price: float, discount_percent?: float}> $lines
     * @param string|null $orderNumber
     * @param string|null $warehouseId
     * @param string|null $salespersonId
     * @param string|null $shippingAddressId
     * @param string|null $billingAddressId
     * @param string|null $paymentTerm
     * @param string|null $customerPoNumber
     * @param string|null $customerNotes
     * @param string|null $internalNotes
     * @param float|null $exchangeRate
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $tenantId,
        public string $customerId,
        public string $currencyCode,
        public DateTimeImmutable|string $quoteDate,
        public array $lines,
        public ?string $orderNumber = null,
        public ?string $warehouseId = null,
        public ?string $salespersonId = null,
        public ?string $shippingAddressId = null,
        public ?string $billingAddressId = null,
        public ?string $paymentTerm = null,
        public ?string $customerPoNumber = null,
        public ?string $customerNotes = null,
        public ?string $internalNotes = null,
        public ?float $exchangeRate = null,
        public ?array $metadata = null,
    ) {
    }
}
