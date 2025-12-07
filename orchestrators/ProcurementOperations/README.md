# Nexus ProcurementOperations Orchestrator

**Package:** `nexus/procurement-operations`  
**Namespace:** `Nexus\ProcurementOperations`  
**Type:** Orchestrator (Pure PHP)

---

## Overview

The `ProcurementOperations` orchestrator coordinates the complete **Procure-to-Pay (P2P)** cycle across multiple atomic Nexus packages. It handles:

1. **Requisition Workflow** - Create, validate, approve/reject purchase requisitions
2. **Purchase Order Workflow** - Convert requisitions to POs, send to vendors, track amendments
3. **Goods Receipt Workflow** - Record partial/full receipts, integrate with inventory
4. **Three-Way Matching** - Match PO ↔ GR ↔ Invoice with configurable tolerances
5. **Payment Processing** - Schedule, batch, and execute vendor payments

---

## Architecture

This orchestrator follows the **Advanced Orchestrator Pattern v1.1** with strict component separation:

```
src/
├── Contracts/        # Coordinator and service interfaces
├── Coordinators/     # Stateless traffic cops (direct flow, don't do work)
├── DataProviders/    # Cross-package data aggregation into Context DTOs
├── DTOs/             # Requests, Results, and Context objects
├── Exceptions/       # Domain-specific error scenarios
├── Listeners/        # Event reactors for async side-effects
├── Rules/            # Individual business constraint validators
├── Services/         # Pure calculation/formatting logic
└── Workflows/        # Stateful long-running processes (Sagas)
```

---

## Package Dependencies

| Package | Purpose |
|---------|---------|
| `Nexus\Procurement` | Requisition, PO, Goods Receipt management |
| `Nexus\Payable` | Vendor bills, invoice matching, payments |
| `Nexus\Inventory` | Stock receipt and management |
| `Nexus\JournalEntry` | GL posting for accruals and payments |
| `Nexus\Budget` | Budget commitment and release |
| `Nexus\Workflow` | Approval workflows |
| `Nexus\Party` | Vendor management |
| `Nexus\Period` | Fiscal period validation |
| `Nexus\Currency` | Multi-currency support |
| `Nexus\Notifier` | Notifications |
| `Nexus\AuditLogger` | Audit trail |
| `Nexus\Setting` | Configuration |

---

## Quick Start

### 1. Requisition Workflow

```php
use Nexus\ProcurementOperations\Coordinators\RequisitionCoordinator;
use Nexus\ProcurementOperations\DTOs\CreateRequisitionRequest;

$coordinator = $container->get(RequisitionCoordinator::class);

$result = $coordinator->create(new CreateRequisitionRequest(
    tenantId: 'tenant-123',
    requestedBy: 'user-456',
    departmentId: 'dept-789',
    lineItems: [
        [
            'productId' => 'prod-001',
            'description' => 'Office Supplies',
            'quantity' => 100,
            'estimatedUnitPriceCents' => 1500,
            'uom' => 'EA',
        ],
    ],
    justification: 'Monthly office supplies replenishment',
));

if ($result->success) {
    echo "Requisition created: {$result->requisitionId}";
}
```

### 2. Three-Way Matching

```php
use Nexus\ProcurementOperations\Coordinators\InvoiceMatchingCoordinator;
use Nexus\ProcurementOperations\DTOs\MatchInvoiceRequest;

$coordinator = $container->get(InvoiceMatchingCoordinator::class);

$result = $coordinator->match(new MatchInvoiceRequest(
    tenantId: 'tenant-123',
    vendorBillId: 'bill-001',
    purchaseOrderId: 'po-001',
    goodsReceiptIds: ['gr-001', 'gr-002'],
    performedBy: 'user-456',
));

if ($result->matched) {
    echo "Invoice matched successfully!";
} else {
    echo "Match failed: {$result->failureReason}";
    print_r($result->variances);
}
```

### 3. Payment Processing

```php
use Nexus\ProcurementOperations\Coordinators\PaymentProcessingCoordinator;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;

$coordinator = $container->get(PaymentProcessingCoordinator::class);

$result = $coordinator->process(new ProcessPaymentRequest(
    tenantId: 'tenant-123',
    vendorBillIds: ['bill-001', 'bill-002'],
    paymentMethod: 'bank_transfer',
    bankAccountId: 'bank-001',
    scheduledDate: new \DateTimeImmutable('+3 days'),
    processedBy: 'user-456',
));

if ($result->success) {
    echo "Payment scheduled: {$result->paymentReference}";
}
```

---

## Event-Driven Integration

The orchestrator listens to domain events from atomic packages and reacts accordingly:

| Event | Listener | Action |
|-------|----------|--------|
| `RequisitionApprovedEvent` | `CreatePurchaseOrderOnRequisitionApproved` | Auto-create PO from approved requisition |
| `GoodsReceiptCompletedEvent` | `TriggerMatchingOnGoodsReceiptCompleted` | Initiate 3-way matching |
| `InvoiceMatchedEvent` | `SchedulePaymentOnInvoiceMatched` | Add to payment queue |
| `PaymentExecutedEvent` | `PostPaymentJournalEntry` | DR AP, CR Bank |

---

## Configuration

Configure via `Nexus\Setting`:

| Setting Key | Default | Description |
|-------------|---------|-------------|
| `procurement.matching.price_tolerance_percent` | `5.0` | Max price variance (%) |
| `procurement.matching.quantity_tolerance_percent` | `2.0` | Max quantity variance (%) |
| `procurement.matching.auto_match_enabled` | `true` | Auto-match on GR complete |
| `procurement.payment.batch_size` | `50` | Max invoices per payment batch |
| `procurement.payment.default_method` | `bank_transfer` | Default payment method |

---

## License

MIT License - see [LICENSE](LICENSE) file.
