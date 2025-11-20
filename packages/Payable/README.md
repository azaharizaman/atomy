# Nexus\Payable

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)

Framework-agnostic accounts payable, vendor bill management, 3-way matching, and payment processing for the Nexus ERP system.

## Overview

The `Nexus\Payable` package provides comprehensive accounts payable (AP) functionality including:

- **Vendor Management**: Complete vendor lifecycle with configurable payment terms and matching tolerances
- **Bill Processing**: Vendor bill submission via manual entry or CSV import (OCR planned for Phase 2)
- **3-Way Matching**: Automated matching of Purchase Orders, Goods Received Notes, and Vendor Invoices
- **Payment Scheduling**: Automated due date calculation with early payment discount tracking
- **GL Integration**: Seamless posting to `Nexus\Finance` general ledger
- **Multi-Currency Support**: Full currency conversion via `Nexus\Currency`
- **Audit Trail**: Comprehensive change tracking via `Nexus\AuditLogger`

## Key Features

### Enterprise-Grade AP Management
- **Per-Vendor Tolerance Configuration**: Configure quantity and price variance thresholds per vendor
- **Flexible Payment Terms**: Net 30, 2/10 Net 30, COD, and custom terms
- **Multi-Currency Bills**: Store bills in original currency with base currency equivalent
- **Payment Reconciliation**: Automatic matching of payments to GL postings
- **Aging Reports**: 30/60/90-day vendor aging analysis

### 3-Way Matching Engine
The matching engine validates vendor bills against:
1. **Purchase Order (PO)** from `Nexus\Procurement` - Expected quantity and agreed price
2. **Goods Received Note (GRN)** from `Nexus\Inventory` - Actual received quantity
3. **Vendor Invoice** - Billed quantity and price

Configurable per-vendor tolerance rules prevent GL posting when variances exceed thresholds.

### Payment Processing
- Automated payment scheduling with due date calculation
- Early payment discount tracking and alerts
- GL journal entry generation via `Nexus\Finance`
- Payment approval workflows (optional via `Nexus\Workflow`)
- Bank transfer file generation (Phase 2)

## Architecture

This package follows the **Nexus Architecture Principle**: "Logic in Packages, Implementation in Applications."

### Package Layer (Pure PHP)
- **Framework-agnostic**: No Laravel dependencies
- **Business Logic**: All AP rules, matching algorithms, payment calculations
- **Interfaces**: Defines data structures and persistence contracts
- **Value Objects**: Immutable domain objects (PaymentTerm, MatchingTolerance, etc.)
- **Services**: PayableManager, MatchingEngine, PaymentScheduler for orchestration

### Application Layer (Laravel/Atomy)
- **Eloquent Models**: Vendor, VendorBill, VendorBillLine, BillMatching, PaymentSchedule
- **Repository Implementations**: Concrete persistence implementations
- **Database Migrations**: Schema definitions
- **Service Provider**: IoC container bindings
- **API Controllers**: RESTful endpoints for AP operations

## Installation

```bash
composer require nexus/payable:"*@dev"
```

## Requirements

- **PHP**: ^8.3
- **Dependencies**:
  - `nexus/finance` - General ledger integration
  - `nexus/period` - Fiscal period validation
  - `nexus/uom` - Unit of measurement (currency)
  - `nexus/currency` - Multi-currency support
  - `nexus/audit-logger` - Change tracking
  - `psr/log` - Logging interface

## Core Concepts

### Vendor Management

```php
use Nexus\Payable\Contracts\PayableManagerInterface;

$payableManager = app(PayableManagerInterface::class);

// Create vendor with payment terms and tolerance
$vendor = $payableManager->createVendor([
    'code' => 'VEND-001',
    'name' => 'ABC Supplies Ltd',
    'payment_terms' => 'net_30',
    'qty_tolerance_percent' => 5.0,
    'price_tolerance_percent' => 2.0,
    'tax_id' => '12-3456789',
    'bank_details' => [
        'account_number' => '1234567890',
        'bank_name' => 'ABC Bank',
        'swift_code' => 'ABCMYKL'
    ]
]);
```

### Bill Submission

```php
// Submit vendor bill for matching
$bill = $payableManager->submitBill([
    'vendor_id' => $vendor->getId(),
    'bill_number' => 'INV-2025-001',
    'bill_date' => '2025-11-20',
    'due_date' => '2025-12-20',
    'currency' => 'USD',
    'lines' => [
        [
            'description' => 'Office Supplies',
            'quantity' => 100,
            'unit_price' => 25.00,
            'gl_account' => '5100-10',
            'po_line_reference' => 'PO-2025-001-L1'
        ]
    ]
]);
```

### 3-Way Matching

```php
use Nexus\Payable\Contracts\ThreeWayMatcherInterface;

$matcher = app(ThreeWayMatcherInterface::class);

// Perform matching against PO and GRN
$matchResult = $matcher->match($bill->getId());

if ($matchResult->isMatched()) {
    // Bill can be posted to GL
    $payableManager->postBillToGL($bill->getId());
} else {
    // Review variances manually
    $variances = $matchResult->getVariances();
}
```

### Payment Processing

```php
// Schedule payment
$schedule = $payableManager->schedulePayment($bill->getId());

// Process payment and post to GL
$payment = $payableManager->processPayment($schedule->getId(), [
    'payment_date' => '2025-12-15',
    'payment_method' => 'bank_transfer',
    'bank_account' => '1000-01',
    'reference' => 'PAY-2025-001'
]);
```

## Directory Structure

```
src/
├── Contracts/              # 6 Interfaces
│   ├── PayableManagerInterface.php
│   ├── VendorRepositoryInterface.php
│   ├── VendorBillRepositoryInterface.php
│   ├── ThreeWayMatcherInterface.php
│   ├── PaymentSchedulerInterface.php
│   └── PaymentAllocationInterface.php
├── Services/               # Business logic
│   ├── PayableManager.php
│   ├── VendorManager.php
│   ├── BillProcessor.php
│   ├── MatchingEngine.php
│   ├── PaymentScheduler.php
│   └── PaymentProcessor.php
├── ValueObjects/           # Immutable domain objects
│   ├── VendorBillNumber.php
│   ├── PaymentTerm.php (enum)
│   ├── MatchingTolerance.php
│   ├── VendorStatus.php (enum)
│   ├── BillStatus.php (enum)
│   ├── MatchingStatus.php (enum)
│   └── PaymentStatus.php (enum)
└── Exceptions/             # Domain exceptions
    ├── PayableException.php (base)
    ├── VendorNotFoundException.php
    ├── BillNotFoundException.php
    ├── BillAlreadyMatchedException.php
    ├── MatchingToleranceExceededException.php
    ├── ThreeWayMatchFailedException.php
    ├── InvalidPaymentTermException.php
    ├── PaymentScheduleException.php
    └── InsufficientCreditException.php
```

## Integration with Nexus Packages

### Required Dependencies
- **Nexus\Finance**: GL journal entry posting for AP liability and expense accounts
- **Nexus\Period**: Fiscal period validation for bill dating
- **Nexus\Uom**: Currency management and conversion
- **Nexus\Currency**: Exchange rate resolution for multi-currency bills
- **Nexus\AuditLogger**: Audit trail for all AP state changes

### Optional Dependencies
- **Nexus\Procurement**: Purchase Order data for 3-way matching
- **Nexus\Inventory**: Goods Received Note data for 3-way matching
- **Nexus\Workflow**: Multi-level payment approval workflows
- **Nexus\DataProcessor**: OCR for automated bill data extraction (Phase 2)
- **Nexus\EventStream**: Event sourcing for payment lifecycle (large enterprises)

## Consumed By
- **Nexus\Accounting**: Financial statement generation (AP aging reports)
- **Nexus\Export**: Payment advice document generation

## Performance Requirements

- Bill submission and validation: < 200ms per bill
- 3-way matching: < 500ms for 100-line bill
- Payment scheduling: < 100ms per payment
- GL posting: < 300ms (via Finance package)
- Vendor aging report: < 3s for 10,000 bills

## Security & Compliance

- **Immutable Bills**: Once matched and posted, bills cannot be modified (reversal only)
- **Tenant Isolation**: All data scoped to tenant ID
- **Audit Trail**: All state changes logged to `Nexus\AuditLogger`
- **Field-Level Encryption**: Bank details encrypted at rest
- **RBAC Integration**: Authorization via `Nexus\Identity` (application layer)

## Roadmap

### Phase 1 (Current - V1.0)
- ✅ Vendor management with configurable tolerances
- ✅ Manual bill submission and CSV import
- ✅ 3-way matching engine
- ✅ Payment scheduling and GL integration
- ✅ Multi-currency support

### Phase 2 (Future)
- ⏳ OCR integration via `Nexus\DataProcessor`
- ⏳ Payment approval workflows via `Nexus\Workflow`
- ⏳ Bank transfer file generation
- ⏳ Automatic payment reminders
- ⏳ Vendor portal for self-service bill submission

### Phase 3 (Enterprise)
- ⏳ Event sourcing via `Nexus\EventStream`
- ⏳ Advanced analytics and forecasting
- ⏳ Batch payment processing
- ⏳ ACH/wire transfer integration

## Testing

```bash
# Run package tests
vendor/bin/phpunit packages/Payable/tests

# Run application integration tests
php artisan test --filter=Payable
```

## License

MIT License - see LICENSE file for details.

## Support

Part of the Nexus ERP Monorepo.

- Documentation: `/docs/PAYABLE_IMPLEMENTATION.md`
- Architecture: `/ARCHITECTURE.md`
- Requirements: `/REQUIREMENTS.csv`
