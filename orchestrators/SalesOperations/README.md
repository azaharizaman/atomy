# Nexus SalesOperations Orchestrator

**Framework-Agnostic Order-to-Cash Workflow Coordination**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\SalesOperations` orchestrates the complete **Order-to-Cash (O2C)** cycle, coordinating workflows across Sales, Receivable, Inventory, and Warehouse domains. Designed with **Progressive Disclosure** to serve businesses from small startups to large enterprises.

---

## Progressive Disclosure Philosophy

This orchestrator scales with your business needs:

### Tier 1: Essential (Small Business)
- Quote → Order conversion
- Simple order confirmation
- Basic fulfillment tracking
- Direct invoicing

### Tier 2: Growth (Mid-Market)
- Credit limit enforcement
- Multi-warehouse allocation
- Partial shipment handling
- Commission calculations

### Tier 3: Enterprise (Large Corporation)
- Multi-currency with rate locking
- Revenue recognition (IFRS 15/ASC 606)
- Advanced pricing rules
- Complete audit trail
- Approval workflows

---

## Architecture

```
src/
├── Contracts/           # Orchestrator interfaces (dependency inversion)
│   ├── QuotationInterface.php
│   ├── SalesOrderInterface.php
│   ├── CustomerInterface.php
│   ├── InvoiceInterface.php
│   ├── CreditManagerInterface.php
│   └── ...
├── Coordinators/        # Stateless traffic cops
│   ├── QuotationToOrderCoordinator.php
│   ├── OrderFulfillmentCoordinator.php
│   ├── CreditCheckCoordinator.php
│   └── ...
├── Workflows/           # Stateful long-running processes
│   ├── OrderToCashWorkflow.php
│   ├── SplitShipmentWorkflow.php
│   └── ...
├── Services/            # Pure business calculations
│   ├── MarginCalculator.php
│   ├── CommissionCalculator.php
│   ├── RevenueRecognitionService.php
│   └── ...
├── Listeners/           # Event reactors
│   ├── OnOrderConfirmedReserveStock.php
│   ├── OnShipmentCreatedGenerateInvoice.php
│   └── ...
├── Rules/               # Validation constraints
│   ├── CreditLimitRule.php
│   ├── StockAvailabilityRule.php
│   └── ...
├── DataProviders/       # Cross-package data aggregation
│   ├── OrderContextProvider.php
│   └── ...
├── DTOs/                # Strict data contracts
│   ├── CreateOrderRequest.php
│   ├── FulfillmentRequest.php
│   └── ...
├── Enums/               # Type-safe enumerations
│   ├── OrderStatus.php
│   ├── FulfillmentStatus.php
│   └── ...
└── Exceptions/          # Domain-specific errors
    ├── CreditLimitExceededException.php
    ├── InsufficientStockException.php
    └── ...
```

---

## Core Components

### Coordinators

#### 1. QuotationToOrderCoordinator

Converts quotations to confirmed sales orders.

```php
use Nexus\SalesOperations\Coordinators\QuotationToOrderCoordinator;
use Nexus\SalesOperations\DTOs\ConvertQuotationRequest;

$coordinator = $container->get(QuotationToOrderCoordinator::class);

$result = $coordinator->convertToOrder(new ConvertQuotationRequest(
    quotationId: 'quote-001',
    confirmedBy: 'user-123',
    notes: 'Customer confirmed via email',
    overrides: [
        'payment_terms' => 'NET_30',
    ]
));

if ($result->success) {
    echo "Order created: {$result->orderId}";
}
```

#### 2. OrderFulfillmentCoordinator

Coordinates order fulfillment across inventory and warehouse.

```php
use Nexus\SalesOperations\Coordinators\OrderFulfillmentCoordinator;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;

$coordinator = $container->get(OrderFulfillmentCoordinator::class);

$result = $coordinator->fulfill(new FulfillmentRequest(
    orderId: 'order-001',
    warehouseId: 'wh-001',
    lines: [
        ['product_id' => 'prod-001', 'quantity' => 10],
        ['product_id' => 'prod-002', 'quantity' => 5],
    ],
    shippedBy: 'user-123',
    trackingNumber: 'TRK-123456',
));

echo "Shipment created: {$result->shipmentId}";
echo "Invoice generated: {$result->invoiceId}";
```

#### 3. CreditCheckCoordinator

Enforces credit limits before order confirmation.

```php
use Nexus\SalesOperations\Coordinators\CreditCheckCoordinator;

$coordinator = $container->get(CreditCheckCoordinator::class);

$check = $coordinator->checkCredit(
    customerId: 'customer-001',
    orderAmount: 5000.00,
    currency: 'USD'
);

if ($check->approved) {
    echo "Credit approved. Available: {$check->availableCredit}";
} else {
    echo "Credit denied: {$check->reason}";
    // Place order on credit hold
}
```

### Workflows

#### OrderToCashWorkflow

Complete O2C lifecycle management.

```php
use Nexus\SalesOperations\Workflows\OrderToCashWorkflow;
use Nexus\SalesOperations\DTOs\OrderToCashRequest;

$workflow = $container->get(OrderToCashWorkflow::class);

$result = $workflow->execute(new OrderToCashRequest(
    tenantId: 'tenant-001',
    customerId: 'customer-001',
    lines: [
        ['product_id' => 'prod-001', 'quantity' => 100, 'unit_price' => 50.00],
    ],
    paymentTerms: 'NET_30',
    requestedBy: 'user-001',
));

// Workflow handles:
// 1. Credit check
// 2. Stock reservation
// 3. Order confirmation
// 4. Fulfillment
// 5. Invoicing
// 6. Payment tracking
// 7. Commission calculation
```

### Services

#### MarginCalculator

```php
use Nexus\SalesOperations\Services\MarginCalculator;

$calculator = $container->get(MarginCalculator::class);

$analysis = $calculator->analyze(
    orderLines: $order->getLines(),
    costBasis: 'weighted_average',  // or 'fifo', 'standard'
    includeLandedCost: true,
);

echo "Gross Margin: {$analysis->grossMarginPercent}%";
echo "Net Margin: {$analysis->netMarginPercent}%";
echo "Total Profit: {$analysis->totalProfit}";
```

#### CommissionCalculator

```php
use Nexus\SalesOperations\Services\CommissionCalculator;

$calculator = $container->get(CommissionCalculator::class);

$commission = $calculator->calculate(
    orderId: 'order-001',
    salespersonId: 'sales-001',
    basis: 'gross_profit',  // or 'revenue', 'quantity'
);

echo "Commission earned: {$commission->amount}";
echo "Rate applied: {$commission->rate}%";
```

### Rules

#### CreditLimitRule

```php
use Nexus\SalesOperations\Rules\CreditLimitRule;

$rule = $container->get(CreditLimitRule::class);

$result = $rule->validate(new CreditCheckRequest(
    customerId: 'customer-001',
    orderAmount: 10000.00,
));

if (!$result->passed) {
    throw new CreditLimitExceededException($result->message);
}
```

---

## Event-Driven Integration

### Events Published

| Event | When Fired |
|-------|------------|
| `QuotationCreatedEvent` | New quotation submitted |
| `QuotationAcceptedEvent` | Customer accepts quote |
| `OrderCreatedEvent` | Order created from quote |
| `OrderConfirmedEvent` | Order confirmed for fulfillment |
| `OrderCancelledEvent` | Order cancelled |
| `ShipmentCreatedEvent` | Goods shipped |
| `InvoiceGeneratedEvent` | Invoice created |
| `PaymentReceivedEvent` | Payment applied |
| `CommissionCalculatedEvent` | Sales comp calculated |

### Event Listeners

| Listens To | Listener | Action |
|------------|----------|--------|
| `OrderConfirmedEvent` | `ReserveStockListener` | Reserve inventory |
| `OrderConfirmedEvent` | `CheckCreditListener` | Verify credit limit |
| `ShipmentCreatedEvent` | `GenerateInvoiceListener` | Create invoice |
| `ShipmentCreatedEvent` | `UpdateInventoryListener` | Deduct stock |
| `PaymentReceivedEvent` | `ReleaseCreditHoldListener` | Free up credit |
| `PaymentReceivedEvent` | `CalculateCommissionListener` | Compute sales comp |

---

## Progressive Disclosure Matrix

| Feature | Tier 1 (SMB) | Tier 2 (Mid) | Tier 3 (Enterprise) |
|---------|--------------|--------------|---------------------|
| Quote → Order | ✅ | ✅ | ✅ |
| Basic Fulfillment | ✅ | ✅ | ✅ |
| Direct Invoicing | ✅ | ✅ | ✅ |
| Credit Limit Check | - | ✅ | ✅ |
| Partial Shipments | - | ✅ | ✅ |
| Multi-Warehouse | - | ✅ | ✅ |
| Commission Calc | - | ✅ | ✅ |
| Multi-Currency | - | - | ✅ |
| Revenue Recognition | - | - | ✅ |
| Advanced Pricing | - | - | ✅ |
| Approval Workflows | - | - | ✅ |
| Full Audit Trail | - | - | ✅ |

---

## Contracts (Interfaces)

All interfaces are defined in `Contracts/` following the **Orchestrator Interface Segregation Pattern**. Adapters implement these interfaces using atomic packages.

### Core Interfaces

```php
interface CustomerInterface
{
    public function getId(): string;
    public function getName(): string;
    public function getCreditLimit(): float;
    public function getPaymentTerms(): string;
    public function getCurrency(): string;
}

interface QuotationInterface
{
    public function getId(): string;
    public function getCustomerId(): string;
    public function getStatus(): string;
    public function getTotal(): float;
    public function getLines(): array;
    public function getValidUntil(): \DateTimeImmutable;
}

interface SalesOrderInterface
{
    public function getId(): string;
    public function getOrderNumber(): string;
    public function getCustomerId(): string;
    public function getStatus(): string;
    public function getTotal(): float;
    public function getLines(): array;
    public function getPaymentTerms(): string;
}

interface InvoiceInterface
{
    public function getId(): string;
    public function getInvoiceNumber(): string;
    public function getOrderId(): string;
    public function getTotal(): float;
    public function getBalanceDue(): float;
    public function getStatus(): string;
}

interface CreditManagerInterface
{
    public function checkCreditLimit(string $customerId, float $amount): bool;
    public function getAvailableCredit(string $customerId): float;
    public function placeOnHold(string $orderId, string $reason): void;
    public function releaseHold(string $orderId): void;
}

interface StockReservationInterface
{
    public function reserve(string $orderId, string $productId, float $quantity): bool;
    public function release(string $orderId): void;
    public function getReservedQuantity(string $orderId, string $productId): float;
}

interface ShipmentInterface
{
    public function getId(): string;
    public function getOrderId(): string;
    public function getStatus(): string;
    public function getTrackingNumber(): ?string;
    public function getLines(): array;
}
```

---

## Installation

```bash
composer require nexus/sales-operations
```

### Required Dependencies (via Adapters)

| Package | Purpose |
|---------|---------|
| `Nexus\Sales` | Sales orders, quotations |
| `Nexus\Receivable` | Invoicing, credit control |
| `Nexus\Inventory` | Stock management |
| `Nexus\Warehouse` | Shipping, staging |
| `Nexus\Party` | Customer management |
| `Nexus\AuditLogger` | Audit trail |
| `Nexus\Notifier` | Notifications |

---

## Testing

```bash
# Run all tests
vendor/bin/phpunit orchestrators/SalesOperations/tests

# Run with coverage
vendor/bin/phpunit --coverage-html coverage orchestrators/SalesOperations/tests
```

---

## License

MIT License - See [LICENSE](LICENSE) file.

---

## Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - System architecture
- [ORCHESTRATOR_INTERFACE_SEGREGATION.md](../../docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md) - Interface pattern
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
