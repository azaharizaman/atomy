# Nexus\Sales

Framework-agnostic sales management package for quotation-to-order commercial contract lifecycle.

## Overview

`Nexus\Sales` manages the complete sales process from quotation to order fulfillment, including:

- **Sales Quotations**: Price quotes with validity periods
- **Sales Orders**: Confirmed customer orders with payment terms
- **Pricing Engine**: Multi-strategy pricing (list price, tiered discounts, customer-specific, promotional)
- **Tax Calculation**: Pluggable tax calculation interface
- **Exchange Rate Locking**: Foreign currency order protection
- **Stock Reservation**: Integration point for inventory management
- **Invoice Generation**: Integration point for accounts receivable

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

### 3. Future-Proof Architecture

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

## Architecture

This package follows the **Nexus Atomic Package Pattern**:

- **NO Laravel dependencies** - Pure PHP 8.3
- **NO database schema** - Contracts only
- **NO Eloquent models** - Entity interfaces only
- **Framework-agnostic** - Can run in any PHP application

Implementation is provided by `apps/Atomy` (Laravel orchestrator).

## Usage

### Creating a Quotation

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

### Calculating Prices

```php
use Nexus\Sales\Services\PricingEngine;
use Nexus\Uom\ValueObjects\Quantity;

// Get price with quantity-based tiering
$unitPrice = $this->pricingEngine->getPrice(
    tenantId: 'tenant-123',
    productVariantId: 'variant-789',
    quantity: new Quantity(100, 'EA'),
    currencyCode: 'MYR',
    customerId: 'customer-456', // Optional for customer-specific pricing
    asOf: new DateTimeImmutable() // Optional, defaults to now
);
```

### Converting Quote to Order

```php
use Nexus\Sales\Services\QuoteToOrderConverter;

$order = $this->converter->convertToOrder(
    quotationId: 'quote-id',
    orderData: [
        'payment_term' => PaymentTerm::NET_30,
        'shipping_address' => '123 Main St',
        'customer_purchase_order' => 'PO-12345',
    ]
);
```

### Confirming Order (Locks Exchange Rate)

```php
use Nexus\Sales\Services\SalesOrderManager;

// Confirms order, locks exchange rate, checks credit, reserves stock
$this->salesOrderManager->confirmOrder(
    orderId: 'order-id',
    confirmedBy: 'user-id'
);
```

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

V1: `StubStockReservation` throws `BadMethodCallException`

Phase 2: Install `nexus/inventory` and bind `StockReservationInterface`:

```php
$this->app->singleton(
    StockReservationInterface::class,
    InventoryStockReservation::class
);
```

### Invoice Generation (Nexus\Receivable)

V1: `StubInvoiceManager` throws `BadMethodCallException`

Phase 2: Install `nexus/receivable` and bind `InvoiceManagerInterface`:

```php
$this->app->singleton(
    InvoiceManagerInterface::class,
    ReceivableInvoiceManager::class
);
```

### Credit Limit Checking (Nexus\Receivable)

V1: `NoOpCreditLimitChecker` always returns `true` (no enforcement)

Phase 2: Replace with real-time receivable balance checker:

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

5. **Sales Returns (RMA)**
   - Return order schema
   - Return authorization workflow
   - Restocking fees
   - Credit note generation

## License

MIT License - see LICENSE file for details.

## Support

For issues and questions, please use the GitHub issue tracker.
