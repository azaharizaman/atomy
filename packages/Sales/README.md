# Nexus\Sales

Framework-agnostic sales management package for quotation-to-order commercial contract lifecycle.

## Overview

`Nexus\Sales` manages the complete sales process from quotation to order fulfillment, including:

- **Sales Quotations**: Price quotes with validity periods and versioning
- **Sales Orders**: Confirmed customer orders with payment terms and fulfillment tracking
- **Pricing Engine**: Multi-strategy pricing (list price, tiered discounts, customer-specific, promotional)
- **Tax Calculation**: Pluggable tax calculation interface
- **Exchange Rate Locking**: Foreign currency order protection
- **Stock Reservation**: Real-time inventory integration with automatic reservation
- **Invoice Generation**: Seamless integration with accounts receivable
- **Credit Limit Checking**: Real-time customer credit validation
- **Sales Returns (RMA)**: Return order processing and validation

## Key Features

### 1. Quotation-to-Order Workflow

```
DRAFT → SENT → ACCEPTED → CONVERTED_TO_ORDER
              ↓         ↓
           REJECTED  EXPIRED
```

Sales Order Workflow:
```
DRAFT → CONFIRMED → PARTIALLY_SHIPPED → FULLY_SHIPPED → INVOICED → PAID
                 ↓
             CANCELLED
```

### 2. Pricing Strategies

- **List Price**: Standard price list pricing
- **Tiered Discount**: Quantity-based pricing tiers (matrix pricing foundation)
- **Customer-Specific**: Customer-negotiated pricing
- **Promotional**: Time-sensitive promotional campaigns

### 3. Integrated Services

The package includes the following production-ready services:

| Service | Purpose | Integration |
|---------|---------|-------------|
| [`QuoteToOrderConverter`](#quotetoorderconverter) | Converts accepted quotes to sales orders | Nexus\Sequencing |
| [`SalesOrderManager`](#salesordermanager) | Full order lifecycle management | All packages |
| [`ReceivableCreditLimitChecker`](#receivablecreditlimitchecker) | Real-time credit limit validation | Nexus\Receivable |
| [`SalesReturnManager`](#salesreturnmanager) | Return order (RMA) processing | Nexus\Receivable (Phase 2) |
| [`InventoryStockReservation`](#inventorystockreservation) | Real-time stock reservation | Nexus\Inventory |
| [`ReceivableInvoiceManager`](#receivableinvoicemanager) | Invoice generation from orders | Nexus\Receivable |

### 4. Future-Proof Architecture

V1 includes schema fields for Phase 2 features:

- **Recurring Subscriptions**: `is_recurring`, `recurrence_rule` (JSON)
- **Sales Commission**: `salesperson_id`, `commission_percentage`
- **Multi-Warehouse**: `preferred_warehouse_id`

All V1 stub implementations provide clear upgrade paths.

## Installation

```bash
composer require nexus/sales
```

## Dependencies

- `nexus/party` - Customer management
- `nexus/product` - Product catalog
- `nexus/uom` - Unit of measurement
- `nexus/currency` - Multi-currency support
- `nexus/finance` - Accounting integration
- `nexus/sequencing` - Auto-numbering
- `nexus/period` - Fiscal period management
- `nexus/audit-logger` - Audit trail
- `nexus/inventory` - Stock management (for InventoryStockReservation)
- `nexus/receivable` - Accounts receivable (for credit checking and invoicing)

## Architecture

This package follows the **Nexus Atomic Package Pattern**:

- **NO Laravel dependencies** - Pure PHP 8.3
- **NO database schema** - Contracts only
- **NO Eloquent models** - Entity interfaces only
- **Framework-agnostic** - Can run in any PHP application

Implementation is provided by `apps/Atomy` (Laravel orchestrator).

## Quick Start Guide

### Step 1: Create a Quotation

```php
use Nexus\Sales\Services\QuotationManager;

// Inject via constructor
public function __construct(
    private readonly QuotationManager $quotationManager
) {}

// Create quotation
$quotation = $this->quotationManager->createQuotation(
    tenantId: 'tenant-123',
    customerId: 'customer-456',
    lines: [
        [
            'product_variant_id' => 'variant-789',
            'quantity' => 10,
            'uom_code' => 'EA',
        ]
    ],
    data: [
        'quote_date' => new DateTimeImmutable(),
        'valid_until' => new DateTimeImmutable('+30 days'),
        'currency_code' => 'MYR',
        'prepared_by' => 'user-id',
    ]
);
```

### Step 2: Convert Quote to Order

```php
use Nexus\Sales\Services\QuoteToOrderConverter;

$order = $this->converter->convertToOrder(
    quotationId: 'quote-id',
    orderData: [
        'payment_term' => \Nexus\Sales\Enums\PaymentTerm::NET_30,
        'shipping_address' => '123 Main St',
        'customer_po' => 'PO-12345',
    ]
);
```

### Step 3: Confirm Order

The confirmation process performs three critical operations:

1. **Credit Limit Check** - Validates customer has sufficient credit
2. **Stock Reservation** - Reserves inventory for the order
3. **Exchange Rate Lock** - Locks foreign currency rate (if applicable)

```php
use Nexus\Sales\Services\SalesOrderManager;

$this->salesOrderManager->confirmOrder(
    orderId: 'order-id',
    confirmedBy: 'user-id'
);
```

### Step 4: Generate Invoice

```php
$invoiceId = $this->salesOrderManager->generateInvoice(
    orderId: 'order-id'
);
```

## Service Reference

### QuoteToOrderConverter

Converts accepted quotations to sales orders, copying all line items, pricing, and customer information.

**Location:** `src/Services/QuoteToOrderConverter.php`

**Constructor Dependencies:**
- `QuotationRepositoryInterface`
- `SalesOrderRepositoryInterface`
- `SequenceGeneratorInterface`
- `AuditLogManagerInterface`
- `LoggerInterface`

**Methods:**

| Method | Description |
|--------|-------------|
| `convertToOrder(string $quotationId, array $orderData = []): SalesOrderInterface` | Converts accepted quote to order |
| `canConvertToOrder(string $quotationId): bool` | Validates if quote can be converted |

### SalesOrderManager

Manages the complete sales order lifecycle including creation, confirmation, shipping, invoicing, and cancellation.

**Location:** `src/Services/SalesOrderManager.php`

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

| Method | Description |
|--------|-------------|
| `createOrder(string $tenantId, string $customerId, array $lines, array $data): SalesOrderInterface` | Creates a new draft order |
| `confirmOrder(string $orderId, string $confirmedBy): void` | Confirms order, checks credit, reserves stock |
| `cancelOrder(string $orderId, ?string $reason = null): void` | Cancels order and releases stock |
| `markAsShipped(string $orderId, bool $isPartialShipment = false): void` | Marks order as shipped |
| `generateInvoice(string $orderId): string` | Generates invoice from order |
| `findOrder(string $orderId): SalesOrderInterface` | Finds order by ID |
| `findOrdersByCustomer(string $tenantId, string $customerId): array` | Finds orders by customer |

### ReceivableCreditLimitChecker

Integrates with Nexus\Receivable for real-time credit limit validation.

**Location:** `src/Services/ReceivableCreditLimitChecker.php`

**Constructor Dependencies:**
- `Nexus\Receivable\Contracts\CreditLimitCheckerInterface`
- `LoggerInterface`

**Methods:**

| Method | Description |
|--------|-------------|
| `checkCreditLimit(string $tenantId, string $customerId, float $orderTotal, string $currencyCode): bool` | Validates customer credit limit |

**Throws:** `CreditLimitExceededException` when limit exceeded

### SalesReturnManager

Handles return order (RMA) processing including validation and quantity checks.

**Location:** `src/Services/SalesReturnManager.php`

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `LoggerInterface`

**Methods:**

| Method | Description |
|--------|-------------|
| `createReturnOrder(string $salesOrderId, array $returnLineItems, string $returnReason): string` | Creates return order |

### InventoryStockReservation

Integrates with Nexus\Inventory for real-time stock reservation.

**Location:** `src/Services/InventoryStockReservation.php`

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `Nexus\Inventory\Contracts\ReservationManagerInterface`
- `Nexus\Inventory\Contracts\StockLevelRepositoryInterface`
- `LoggerInterface`

**Methods:**

| Method | Description |
|--------|-------------|
| `reserveStockForOrder(string $salesOrderId): array` | Reserves stock for all order lines |
| `releaseStockReservation(string $salesOrderId): void` | Releases all reservations |
| `getOrderReservations(string $salesOrderId): array` | Gets active reservations |
| `checkStockAvailability(string $salesOrderId): StockAvailabilityResult` | Checks availability without reserving |

**Throws:** `InsufficientStockException` when stock unavailable

### ReceivableInvoiceManager

Integrates with Nexus\Receivable for invoice generation.

**Location:** `src/Services/ReceivableInvoiceManager.php`

**Constructor Dependencies:**
- `SalesOrderRepositoryInterface`
- `Nexus\Receivable\Contracts\ReceivableManagerInterface`
- `LoggerInterface`

**Methods:**

| Method | Description |
|--------|-------------|
| `generateInvoiceFromOrder(string $salesOrderId): string` | Generates invoice from sales order |

## Pricing Engine

### Matrix Pricing (Quantity Tiers)

The pricing engine supports quantity-based tiered pricing via dedicated `price_tiers` table:

```php
// Example: Volume discount structure
// 1-99 units: $10.00 each
// 100-499 units: $9.50 each
// 500+ units: $9.00 each

$price = $this->pricingEngine->getPrice(
    tenantId: 'tenant-123',
    productVariantId: 'variant-789',
    quantity: new Quantity(250, 'EA'), // Returns $9.50
    currencyCode: 'MYR'
);
```

Performance: SQL-based tier matching enables efficient queries:

```sql
SELECT unit_price 
FROM price_tiers 
WHERE price_list_item_id = ? 
  AND ? >= min_quantity 
  AND (max_quantity IS NULL OR ? < max_quantity)
ORDER BY min_quantity DESC 
LIMIT 1
```

## Tax Calculation

V1 provides `SimpleTaxCalculator` with configurable flat tax rate.

Phase 2: Replace with tax jurisdiction engine for SST, VAT, GST compliance.

```php
use Nexus\Sales\Services\SimpleTaxCalculator;
use Nexus\Sales\Contracts\TaxCalculatorInterface;

// In AppServiceProvider
$this->app->singleton(TaxCalculatorInterface::class, function () {
    return new SimpleTaxCalculator(
        logger: app(LoggerInterface::class),
        defaultTaxRate: 6.0 // 6% SST for Malaysia
    );
});
```

## Exchange Rate Locking

For foreign currency orders, the exchange rate is locked at **confirmation time**:

```php
// Order created in USD (exchange rate not yet locked)
$order = $this->salesOrderManager->createOrder(...);
// exchange_rate = NULL

// Order confirmed (exchange rate locked)
$this->salesOrderManager->confirmOrder($order->getId(), 'user-id');
// exchange_rate = 4.75 (locked at current rate)

// Rate changes in market, but order is unaffected
// Prevents currency fluctuation risk
```

## Integration Points

### Stock Reservation (Nexus\Inventory)

Production-ready via `InventoryStockReservation`:

```php
$this->app->singleton(
    StockReservationInterface::class,
    InventoryStockReservation::class
);
```

### Invoice Generation (Nexus\Receivable)

Production-ready via `ReceivableInvoiceManager`:

```php
$this->app->singleton(
    InvoiceManagerInterface::class,
    ReceivableInvoiceManager::class
);
```

### Credit Limit Checking (Nexus\Receivable)

Production-ready via `ReceivableCreditLimitChecker`:

```php
$this->app->singleton(
    CreditLimitCheckerInterface::class,
    ReceivableCreditLimitChecker::class
);
```

## Discount Rules

Time-sensitive promotional pricing:

```php
use Nexus\Sales\ValueObjects\DiscountRule;
use Nexus\Sales\Enums\DiscountType;

$discountRule = new DiscountRule(
    type: DiscountType::PERCENTAGE,
    value: 15.0,
    minQuantity: 50,
    validFrom: new DateTimeImmutable('2024-12-01'),
    validUntil: new DateTimeImmutable('2024-12-31')
);

// Check if discount is currently active
if ($discountRule->isCurrentlyValid()) {
    // Apply discount
}

// Check at specific date
if ($discountRule->isValidAt(new DateTimeImmutable('2024-12-15'))) {
    // Discount was/will be active
}
```

## Payment Terms

Standard payment terms with automatic due date calculation:

```php
use Nexus\Sales\Enums\PaymentTerm;

$orderDate = new DateTimeImmutable('2024-12-01');

PaymentTerm::NET_30->calculateDueDate($orderDate); // 2024-12-31
PaymentTerm::NET_45->calculateDueDate($orderDate); // 2025-01-15
PaymentTerm::DUE_ON_RECEIPT->calculateDueDate($orderDate); // 2024-12-01

// Custom payment term (e.g., NET 7)
PaymentTerm::CUSTOM->calculateDueDate($orderDate, 7); // 2024-12-08
```

## Exception Handling

All domain exceptions extend `SalesException`:

```php
use Nexus\Sales\Exceptions\{
    QuotationNotFoundException,
    SalesOrderNotFoundException,
    DuplicateQuoteNumberException,
    DuplicateOrderNumberException,
    InsufficientStockException,
    CreditLimitExceededException,
    InvalidQuoteStatusException,
    InvalidOrderStatusException,
    PriceNotFoundException,
    ExchangeRateLockedException
};

try {
    $this->salesOrderManager->confirmOrder($orderId, $userId);
} catch (CreditLimitExceededException $e) {
    // Handle credit limit exceeded
} catch (InsufficientStockException $e) {
    // Handle out of stock
} catch (SalesException $e) {
    // Handle generic sales error
}
```

## Audit Trail

All state changes are logged via `Nexus\AuditLogger`:

- Quotation sent to customer
- Quotation accepted/rejected
- Quotation converted to order
- Order confirmed (with locked exchange rate)
- Order shipped (partial/full)
- Order invoiced
- Order cancelled
- Return order created
- Stock reserved/released

Example audit log entry:

```
Event: order_confirmed
Description: Sales order SO-2024-001 confirmed by user-123
Metadata: {"order_number":"SO-2024-001","exchange_rate":4.75,"confirmed_by":"user-123"}
```

## Testing

```bash
# Run package tests
composer test

# Run with coverage
composer test -- --coverage
```

## Future Enhancements (Phase 2)

1. **Recurring Subscriptions**
   - Use `is_recurring` and `recurrence_rule` fields
   - Automatic renewal order generation
   - Subscription lifecycle management

2. **Sales Commission**
   - Track salesperson via `salesperson_id`
   - Calculate commission via `commission_percentage`
   - Commission payout integration

3. **Multi-Warehouse Fulfillment**
   - Route orders via `preferred_warehouse_id`
   - Split shipments across warehouses
   - Warehouse selection optimization

4. **Advanced Tax Engine**
   - Tax jurisdiction determination
   - Multi-level tax (federal + state)
   - Tax exemption certificates
   - Reverse charge mechanism

5. **Advanced Sales Returns**
   - Credit note integration with Nexus\Receivable
   - Restocking fee calculation
   - Return quality inspection workflow

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation


## License

MIT License - see LICENSE file for details.

## Support

For issues and questions, please use the GitHub issue tracker.
