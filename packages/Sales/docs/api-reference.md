# API Reference: Sales

This document provides complete API documentation for all interfaces, services, value objects, and enums in the Nexus\Sales package.

---

## Table of Contents

1. [Core Interfaces](#core-interfaces)
2. [Services](#services)
3. [Value Objects](#value-objects)
4. [Enums](#enums)
5. [Exceptions](#exceptions)

---

## Core Interfaces

### QuotationRepositoryInterface

**Location:** `src/Contracts/QuotationRepositoryInterface.php`

**Purpose:** Manages persistence of sales quotations.

**Methods:**

#### findById()

```php
public function findById(string $quotationId): ?QuotationInterface;
```

**Description:** Finds a quotation by its ID.

**Parameters:**
- `$quotationId` (string) - The quotation ULID

**Returns:** `?QuotationInterface` - The quotation or null if not found

---

#### findByTenant()

```php
public function findByTenant(string $tenantId, int $page = 1, int $perPage = 20): array;
```

**Description:** Finds all quotations for a tenant with pagination.

**Parameters:**
- `$tenantId` (string) - The tenant ULID
- `$page` (int) - Page number (default: 1)
- `$perPage` (int) - Items per page (default: 20)

**Returns:** `array` - Array of quotations

---

#### findByCustomer()

```php
public function findByCustomer(string $tenantId, string $customerId): array;
```

**Description:** Finds all quotations for a specific customer.

**Parameters:**
- `$tenantId` (string) - The tenant ULID
- `$customerId` (string) - The customer ULID

**Returns:** `array` - Array of quotations

---

#### findByStatus()

```php
public function findByStatus(string $tenantId, string $status): array;
```

**Description:** Finds quotations by status.

**Parameters:**
- `$tenantId` (string) - The tenant ULID
- `$status` (string) - Status value

**Returns:** `array` - Array of quotations

---

#### save()

```php
public function save(QuotationInterface $quotation): QuotationInterface;
```

**Description:** Saves or updates a quotation.

**Parameters:**
- `$quotation` (QuotationInterface) - The quotation to save

**Returns:** `QuotationInterface` - The saved quotation

---

#### markAsConvertedToOrder()

```php
public function markAsConvertedToOrder(string $quotationId, string $orderId): void;
```

**Description:** Marks a quotation as converted to order.

**Parameters:**
- `$quotationId` (string) - The quotation ULID
- `$orderId` (string) - The created order ULID

---

### SalesOrderRepositoryInterface

**Location:** `src/Contracts/SalesOrderRepositoryInterface.php`

**Purpose:** Manages persistence of sales orders.

**Methods:**

#### findById()

```php
public function findById(string $orderId): ?SalesOrderInterface;
```

**Description:** Finds an order by its ID.

**Parameters:**
- `$orderId` (string) - The order ULID

**Returns:** `?SalesOrderInterface` - The order or null

---

#### findByTenant()

```php
public function findByTenant(string $tenantId, int $page = 1, int $perPage = 20): array;
```

**Description:** Finds all orders for a tenant with pagination.

---

#### findByCustomer()

```php
public function findByCustomer(string $tenantId, string $customerId): array;
```

**Description:** Finds all orders for a specific customer.

---

#### findByStatus()

```php
public function findByStatus(string $tenantId, string $status): array;
```

**Description:** Finds orders by status.

---

#### create()

```php
public function create(array $data): SalesOrderInterface;
```

**Description:** Creates a new sales order from data array.

**Parameters:**
- `$data` (array) - Order data including lines

**Returns:** `SalesOrderInterface` - Created order

---

#### save()

```php
public function save(SalesOrderInterface $order): SalesOrderInterface;
```

**Description:** Saves or updates an order.

---

#### delete()

```php
public function delete(string $orderId): void;
```

**Description:** Soft-deletes an order.

---

### StockReservationInterface

**Location:** `src/Contracts/StockReservationInterface.php`

**Purpose:** Manages stock reservation for sales orders.

**Methods:**

#### reserveStockForOrder()

```php
public function reserveStockForOrder(string $salesOrderId): array;
```

**Description:** Reserves stock for all line items in an order.

**Parameters:**
- `$salesOrderId` (string) - The order ULID

**Returns:** `array<string, string>` - Map of line ID to reservation ID

**Throws:** `InsufficientStockException` if stock is unavailable

---

#### releaseStockReservation()

```php
public function releaseStockReservation(string $salesOrderId): void;
```

**Description:** Releases all stock reservations for an order.

**Parameters:**
- `$salesOrderId` (string) - The order ULID

---

#### getOrderReservations()

```php
public function getOrderReservations(string $salesOrderId): array;
```

**Description:** Gets all active reservations for an order.

**Parameters:**
- `$salesOrderId` (string) - The order ULID

**Returns:** `array` - Map of reservation IDs to details

---

#### checkStockAvailability()

```php
public function checkStockAvailability(string $salesOrderId): StockAvailabilityResult;
```

**Description:** Checks stock availability without reserving.

**Parameters:**
- `$salesOrderId` (string) - The order ULID

**Returns:** `StockAvailabilityResult`

---

### CreditLimitCheckerInterface

**Location:** `src/Contracts/CreditLimitCheckerInterface.php`

**Purpose:** Validates customer credit limits.

**Methods:**

#### checkCreditLimit()

```php
public function checkCreditLimit(
    string $tenantId,
    string $customerId,
    float $orderTotal,
    string $currencyCode
): bool;
```

**Description:** Checks if customer can place order based on credit limit.

**Parameters:**
- `$tenantId` (string) - The tenant ULID
- `$customerId` (string) - The customer ULID
- `$orderTotal` (float) - Total order amount
- `$currencyCode` (string) - Currency code

**Returns:** `bool` - true if within credit limit

**Throws:** `CreditLimitExceededException` if limit exceeded

---

### InvoiceManagerInterface

**Location:** `src/Contracts/InvoiceManagerInterface.php`

**Purpose:** Generates invoices from sales orders.

**Methods:**

#### generateInvoiceFromOrder()

```php
public function generateInvoiceFromOrder(string $salesOrderId): string;
```

**Description:** Creates an invoice from a sales order.

**Parameters:**
- `$salesOrderId` (string) - The order ULID

**Returns:** `string` - Generated invoice ID

---

## Services

### QuoteToOrderConverter

**Location:** `src/Services/QuoteToOrderConverter.php`

**Purpose:** Converts accepted quotations to sales orders.

**Constructor Dependencies:**
- `QuotationRepositoryInterface`
- `SalesOrderRepositoryInterface`
- `SequenceGeneratorInterface`
- `AuditLogManagerInterface`
- `LoggerInterface`

**Methods:**

#### convertToOrder()

```php
public function convertToOrder(string $quotationId, array $orderData = []): SalesOrderInterface;
```

**Description:** Converts an accepted quotation to a sales order.

**Parameters:**
- `$quotationId` (string) - The quotation ID to convert
- `$orderData` (array) - Optional order data:
  - `payment_term`: PaymentTerm enum value
  - `shipping_address`: string|null
  - `billing_address`: string|null
  - `customer_po`: string|null
  - `notes`: string|null
  - `salesperson_id`: string|null
  - `preferred_warehouse_id`: string|null

**Returns:** `SalesOrderInterface` - Created sales order

**Throws:**
- `QuotationNotFoundException`
- `InvalidQuoteStatusException` - If quote not in ACCEPTED status

---

#### canConvertToOrder()

```php
public function canConvertToOrder(string $quotationId): bool;
```

**Description:** Validates if a quotation can be converted.

**Parameters:**
- `$quotationId` (string) - The quotation ID

**Returns:** `bool` - true if convertible

---

### SalesOrderManager

**Location:** `src/Services/SalesOrderManager.php`

**Purpose:** Manages the complete sales order lifecycle.

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `SequenceGeneratorInterface`
- `ExchangeRateServiceInterface`
- `CreditLimitCheckerInterface`
- `StockReservationInterface`
- `InvoiceManagerInterface`
- `AuditLogManagerInterface`
- `LoggerInterface`

**Methods:**

#### createOrder()

```php
public function createOrder(
    string $tenantId,
    string $customerId,
    array $lines,
    array $data
): SalesOrderInterface;
```

**Description:** Creates a new draft sales order.

**Parameters:**
- `$tenantId` (string) - Tenant ULID
- `$customerId` (string) - Customer ULID
- `$lines` (array) - Array of line data:
  - `product_variant_id`: string
  - `quantity`: float
  - `unit_price`: float
  - `uom_code`: string (optional, default: 'EA')
  - `tax_amount`: float (optional)
  - `discount_amount`: float (optional)
- `$data` (array) - Order data:
  - `currency_code`: string (default: 'MYR')
  - `payment_term`: PaymentTerm enum
  - `shipping_address`: string|null
  - `billing_address`: string|null
  - `customer_po`: string|null
  - `notes`: string|null
  - `salesperson_id`: string|null
  - `preferred_warehouse_id`: string|null

**Returns:** `SalesOrderInterface` - Created order

---

#### confirmOrder()

```php
public function confirmOrder(string $orderId, string $confirmedBy): void;
```

**Description:** Confirms an order (checks credit, reserves stock, locks exchange rate).

**Parameters:**
- `$orderId` (string) - Order ULID
- `$confirmedBy` (string) - User ID confirming

**Throws:**
- `SalesOrderNotFoundException`
- `InvalidOrderStatusException`
- `CreditLimitExceededException`
- `InsufficientStockException`

---

#### cancelOrder()

```php
public function cancelOrder(string $orderId, ?string $reason = null): void;
```

**Description:** Cancels an order and releases stock reservations.

**Parameters:**
- `$orderId` (string) - Order ULID
- `$reason` (string|null) - Cancellation reason

---

#### markAsShipped()

```php
public function markAsShipped(string $orderId, bool $isPartialShipment = false): void;
```

**Description:** Marks order as shipped.

**Parameters:**
- `$orderId` (string) - Order ULID
- `$isPartialShipment` (bool) - Whether partial shipment

---

#### generateInvoice()

```php
public function generateInvoice(string $orderId): string;
```

**Description:** Generates invoice from order.

**Parameters:**
- `$orderId` (string) - Order ULID

**Returns:** `string` - Invoice ID

---

#### findOrder()

```php
public function findOrder(string $orderId): SalesOrderInterface;
```

**Description:** Finds order by ID.

---

#### findOrdersByCustomer()

```php
public function findOrdersByCustomer(string $tenantId, string $customerId): array;
```

**Description:** Finds all orders for a customer.

---

### ReceivableCreditLimitChecker

**Location:** `src/Services/ReceivableCreditLimitChecker.php`

**Purpose:** Integrates with Nexus\Receivable for credit checking.

**Constructor Dependencies:**
- `Nexus\Receivable\Contracts\CreditLimitCheckerInterface`
- `LoggerInterface`

**Methods:**

#### checkCreditLimit()

```php
public function checkCreditLimit(
    string $tenantId,
    string $customerId,
    float $orderTotal,
    string $currencyCode
): bool;
```

**Description:** Delegates to Receivable package for credit validation.

---

### SalesReturnManager

**Location:** `src/Services/SalesReturnManager.php`

**Purpose:** Handles return order (RMA) processing.

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `LoggerInterface`

**Methods:**

#### createReturnOrder()

```php
public function createReturnOrder(
    string $salesOrderId,
    array $returnLineItems,
    string $returnReason
): string;
```

**Description:** Creates a return order from sales order.

**Parameters:**
- `$salesOrderId` (string) - Sales order ULID
- `$returnLineItems` (array) - Array of:
  - `product_variant_id`: string
  - `quantity`: float
- `$returnReason` (string) - Reason for return

**Returns:** `string` - Return order ID

**Throws:**
- `SalesOrderNotFoundException`
- `InvalidArgumentException` - If validation fails

---

### InventoryStockReservation

**Location:** `src/Services/InventoryStockReservation.php`

**Purpose:** Integrates with Nexus\Inventory for stock management.

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `Nexus\Inventory\Contracts\ReservationManagerInterface`
- `Nexus\Inventory\Contracts\StockLevelRepositoryInterface`
- `LoggerInterface`

**Methods:**

See `StockReservationInterface` methods above.

---

### ReceivableInvoiceManager

**Location:** `src/Services/ReceivableInvoiceManager.php`

**Purpose:** Integrates with Nexus\Receivable for invoice generation.

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `Nexus\Receivable\Contracts\ReceivableManagerInterface`
- `LoggerInterface`

**Methods:**

#### generateInvoiceFromOrder()

```php
public function generateInvoiceFromOrder(string $salesOrderId): string;
```

**Description:** Creates invoice in Receivable package.

---

### QuotationManager

**Location:** `src/Services/QuotationManager.php`

**Purpose:** Manages quotation lifecycle.

**Methods:**

#### createQuotation()

```php
public function createQuotation(
    string $tenantId,
    string $customerId,
    array $lines,
    array $data
): QuotationInterface;
```

**Description:** Creates a new quotation.

---

#### sendQuotation()

```php
public function sendQuotation(string $quotationId, string $sentBy): void;
```

**Description:** Marks quotation as sent.

---

#### acceptQuotation()

```php
public function acceptQuotation(string $quotationId, string $acceptedBy): void;
```

**Description:** Accepts a quotation.

---

#### rejectQuotation()

```php
public function rejectQuotation(string $quotationId, string $rejectedBy, string $reason): void;
```

**Description:** Rejects a quotation.

---

## Value Objects

### DiscountRule

**Location:** `src/ValueObjects/DiscountRule.php`

**Purpose:** Represents a discount rule with validity period.

**Constructor:**
```php
public function __construct(
    DiscountType $type,
    float $value,
    ?int $minQuantity = null,
    ?DateTimeImmutable $validFrom = null,
    ?DateTimeImmutable $validUntil = null
);
```

**Properties:**
- `$type` (DiscountType) - PERCENTAGE or FIXED
- `$value` (float) - Discount value
- `$minQuantity` (int|null) - Minimum quantity to qualify
- `$validFrom` (DateTimeImmutable|null) - Start date
- `$validUntil` (DateTimeImmutable|null) - End date

**Methods:**

#### isCurrentlyValid()

```php
public function isCurrentlyValid(): bool;
```

#### isValidAt()

```php
public function isValidAt(DateTimeImmutable $date): bool;
```

#### calculateDiscount()

```php
public function calculateDiscount(float $amount, float $quantity): float;
```

---

### StockAvailabilityResult

**Location:** `src/ValueObjects/StockAvailabilityResult.php`

**Purpose:** Represents stock availability check result.

**Methods:**

#### available()

```php
public static function available(array $lineItems): self;
```

**Description:** Creates a result indicating all items available.

#### unavailable()

```php
public static function unavailable(
    array $lineItems,
    array $unavailableLines,
    string $message
): self;
```

**Description:** Creates a result indicating unavailable items.

#### isAvailable()

```php
public function isAvailable(): bool;
```

#### getLineItems()

```php
public function getLineItems(): array;
```

---

### LineItemAvailability

**Location:** `src/ValueObjects/LineItemAvailability.php`

**Purpose:** Represents availability for a single line item.

**Properties:**
- `$lineId` (string)
- `$productVariantId` (string)
- `$warehouseId` (string)
- `$requestedQuantity` (float)
- `$availableQuantity` (float)
- `$isAvailable` (bool)

---

## Enums

### QuoteStatus

**Location:** `src/Enums/QuoteStatus.php`

**Cases:**
- `DRAFT` - Initial state
- `SENT` - Sent to customer
- `ACCEPTED` - Customer accepted
- `REJECTED` - Customer rejected
- `EXPIRED` - Past validity date
- `CONVERTED` - Converted to order

**Methods:**

#### canBeConverted()

```php
public function canBeConverted(): bool;
```

Returns true for ACCEPTED status.

---

### SalesOrderStatus

**Location:** `src/Enums/SalesOrderStatus.php`

**Cases:**
- `DRAFT` - Initial state
- `CONFIRMED` - Confirmed by customer
- `PROCESSING` - Being prepared
- `PARTIALLY_SHIPPED` - Some items shipped
- `FULLY_SHIPPED` - All items shipped
- `INVOICED` - Invoice generated
- `PAID` - Payment received
- `CANCELLED` - Order cancelled

**Methods:**

#### canBeConfirmed()

```php
public function canBeConfirmed(): bool;
```

#### canBeShipped()

```php
public function canBeShipped(): bool;
```

#### canBeInvoiced()

```php
public function canBeInvoiced(): bool;
```

#### isFinal()

```php
public function isFinal(): bool;
```

Returns true for CANCELLED, PAID.

---

### PaymentTerm

**Location:** `src/Enums/PaymentTerm.php`

**Cases:**
- `DUE_ON_RECEIPT` - Due immediately
- `NET_7` - Due in 7 days
- `NET_15` - Due in 15 days
- `NET_30` - Due in 30 days
- `NET_45` - Due in 45 days
- `NET_60` - Due in 60 days
- `NET_90` - Due in 90 days
- `CUSTOM` - Custom term

**Methods:**

#### calculateDueDate()

```php
public function calculateDueDate(DateTimeImmutable $orderDate, ?int $days = null): DateTimeImmutable;
```

---

### DiscountType

**Location:** `src/Enums/DiscountType.php`

**Cases:**
- `PERCENTAGE` - Percentage off
- `FIXED` - Fixed amount off

---

### PricingStrategy

**Location:** `src/Enums/PricingStrategy.php`

**Cases:**
- `LIST_PRICE` - Standard price list
- `TIERED` - Quantity-based tiers
- `CUSTOMER_SPECIFIC` - Customer-negotiated
- `PROMOTIONAL` - Time-sensitive

---

## Exceptions

### SalesException

**Location:** `src/Exceptions/SalesException.php`

Base exception for all sales errors.

---

### QuotationNotFoundException

**Location:** `src/Exceptions/QuotationNotFoundException.php`

**Methods:**

#### forQuotation()

```php
public static function forQuotation(string $quotationId): self;
```

---

### SalesOrderNotFoundException

**Location:** `src/Exceptions/SalesOrderNotFoundException.php`

**Methods:**

#### forOrder()

```php
public static function forOrder(string $orderId): self;
```

---

### InvalidQuoteStatusException

**Location:** `src/Exceptions/InvalidQuoteStatusException.php`

**Methods:**

#### cannotConvert()

```php
public static function cannotConvert(string $quotationId, QuoteStatus $status): self;
```

---

### InvalidOrderStatusException

**Location:** `src/Exceptions/InvalidOrderStatusException.php`

**Methods:**

#### cannotConfirm()

```php
public static function cannotConfirm(string $orderId, SalesOrderStatus $status): self;
```

#### cannotTransition()

```php
public static function cannotTransition(
    string $orderId,
    SalesOrderStatus $current,
    SalesOrderStatus $target
): self;
```

---

### CreditLimitExceededException

**Location:** `src/Exceptions/CreditLimitExceededException.php`

**Methods:**

#### forCustomer()

```php
public static function forCustomer(
    string $customerId,
    float $orderTotal,
    float $creditLimit
): self;
```

**Properties:**
- `$customerId` (string)
- `$orderTotal` (float)
- `$creditLimit` (float)
- `$currentBalance` (float|null)

---

### InsufficientStockException

**Location:** `src/Exceptions/InsufficientStockException.php`

**Methods:**

#### forOrder()

```php
public static function forOrder(string $orderId, string $message): self;
```

---

### DuplicateQuoteNumberException

**Location:** `src/Exceptions/DuplicateQuoteNumberException.php`

### DuplicateOrderNumberException

**Location:** `src/Exceptions/DuplicateOrderNumberException.php`

### PriceNotFoundException

**Location:** `src/Exceptions/PriceNotFoundException.php`

### ExchangeRateLockedException

**Location:** `src/Exceptions/ExchangeRateLockedException.php`

---

## Related Documentation

- [Getting Started Guide](../getting-started.md)
- [Integration Guide](../integration-guide.md)
- [Examples](../examples/)
