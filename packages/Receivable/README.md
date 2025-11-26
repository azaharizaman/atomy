# Nexus\Receivable

**Accounts Receivable (A/R) Package for Nexus ERP**

Framework-agnostic accounts receivable management handling customer invoicing, payment receipts, credit control, and collections.

## 📋 Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Core Concepts](#core-concepts)
- [Architecture](#architecture)
- [Revenue Recognition](#revenue-recognition)
- [Payment Allocation Strategies](#payment-allocation-strategies)
- [Credit Limit Enforcement](#credit-limit-enforcement)
- [Dunning & Collections](#dunning--collections)
- [Multi-Currency Support](#multi-currency-support)
- [Integration Points](#integration-points)
- [Usage Examples](#usage-examples)

## Overview

`Nexus\Receivable` manages the complete Accounts Receivable lifecycle:

1. **Invoicing**: Creating invoices from fulfilled sales orders
2. **Payment Application**: Tracking and applying customer payments
3. **Credit Control**: Enforcing credit limits before order confirmation
4. **Collections**: Automated dunning for overdue invoices
5. **Bad Debt**: Write-off procedures with GL integration

### Key Features

- ✅ Framework-agnostic pure PHP package
- ✅ Accrual basis revenue recognition (IFRS 15/ASC 606 compliant)
- ✅ Multi-currency payment support with FX gain/loss posting
- ✅ Flexible payment allocation strategies (FIFO, Proportional, Manual)
- ✅ Customer and customer group credit limits
- ✅ Automated collections workflow integration
- ✅ Unapplied cash (prepayment) management
- ✅ Comprehensive aging reports (Current, 1-30, 31-60, 61-90, 90+ days)

## Installation

Add to your application's `composer.json`:

```json
{
    "require": {
        "nexus/receivable": "*@dev"
    }
}
```

Install dependencies:

```bash
composer require nexus/receivable
```

### Required Dependencies

This package requires the following Nexus packages:

- `nexus/finance` - General Ledger integration
- `nexus/party` - Customer entity management
- `nexus/sales` - Sales order integration
- `nexus/currency` - Multi-currency support
- `nexus/period` - Accounting period validation
- `nexus/sequencing` - Invoice number generation
- `nexus/audit-logger` - Audit trail tracking

## Core Concepts

### Invoice Lifecycle

```
DRAFT → PENDING_APPROVAL → APPROVED → POSTED → PARTIALLY_PAID → PAID
                                     ↓
                              CANCELLED / OVERDUE / WRITTEN_OFF
```

### Invoice Status Definitions

| Status | Description | Can Receive Payment | Contributes to AR Balance |
|--------|-------------|---------------------|---------------------------|
| `DRAFT` | Invoice created but not submitted | ❌ | ❌ |
| `PENDING_APPROVAL` | Awaiting approval | ❌ | ❌ |
| `APPROVED` | Approved, ready for GL posting | ❌ | ❌ |
| `POSTED` | GL journal entry created | ✅ | ✅ |
| `PARTIALLY_PAID` | Some payment received | ✅ | ✅ |
| `PAID` | Fully paid | ❌ | ❌ |
| `OVERDUE` | Past due date | ✅ | ✅ |
| `CANCELLED` | Voided/cancelled | ❌ | ❌ |
| `WRITTEN_OFF` | Bad debt written off | ❌ | ❌ |

### Payment Receipt Lifecycle

```
PENDING → CLEARED → APPLIED → RECONCILED
        ↓
   BOUNCED / VOIDED
```

## Architecture

### Package Structure

```
packages/Receivable/
├── src/
│   ├── Contracts/                  # 16 interfaces
│   │   ├── CustomerInvoiceInterface.php
│   │   ├── PaymentReceiptInterface.php
│   │   ├── ReceivableManagerInterface.php
│   │   ├── CreditLimitCheckerInterface.php
│   │   ├── PaymentAllocationStrategyInterface.php
│   │   ├── DunningManagerInterface.php
│   │   ├── AgingCalculatorInterface.php
│   │   ├── PaymentProcessorInterface.php
│   │   ├── UnappliedCashManagerInterface.php
│   │   └── Repository interfaces...
│   ├── Services/                   # Service implementations (in Atomy)
│   ├── Enums/                      # 5 enums
│   │   ├── InvoiceStatus.php
│   │   ├── PaymentReceiptStatus.php
│   │   ├── PaymentMethod.php
│   │   ├── CreditTerm.php
│   │   └── PaymentAllocationType.php
│   ├── ValueObjects/               # 3 value objects
│   │   ├── InvoiceNumber.php
│   │   ├── ReceiptNumber.php
│   │   └── AgingBucket.php
│   └── Exceptions/                 # 8 domain exceptions
│       ├── InvoiceNotFoundException.php
│       ├── InvalidInvoiceStatusException.php
│       ├── CreditLimitExceededException.php
│       ├── PaymentAllocationException.php
│       ├── InvalidPaymentException.php
│       ├── InvoiceAlreadyPaidException.php
│       ├── CannotVoidInvoiceException.php
│       └── DunningFailedException.php
├── composer.json
├── LICENSE
└── README.md
```

### Key Design Patterns

- **Contract-Driven Design**: All dependencies defined via interfaces
- **Immutable Value Objects**: InvoiceNumber, AgingBucket, ReceiptNumber
- **Native PHP 8.x Enums**: Business logic embedded in enums
- **Strategy Pattern**: Payment allocation strategies (FIFO, Proportional, Manual)
- **Repository Pattern**: Data persistence abstraction

## Revenue Recognition

### Accrual Basis (Default and Mandatory)

Revenue is recognized **on invoice creation** (or goods/service delivery), in compliance with IFRS 15/ASC 606.

#### Revenue Recognition Flow

```
Sales Order Fulfilled
        ↓
Invoice Created (DRAFT)
        ↓
Invoice Approved
        ↓
Invoice Posted to GL ← REVENUE RECOGNIZED HERE
        ↓
    [Journal Entry]
    Debit:  AR Control (1200)     $1,000
    Credit: Sales Revenue (4100)  $1,000
        ↓
Payment Received (Later)
        ↓
    [Journal Entry]
    Debit:  Cash (1000)           $1,000
    Credit: AR Control (1200)     $1,000
```

#### Why No Cash-Basis Option?

**Cash-basis revenue recognition is NOT supported** to maintain:

1. **Compliance Integrity**: IFRS/GAAP mandate accrual accounting for most entities
2. **Architectural Simplicity**: Avoids dual-mode complexity in Finance package
3. **Accurate Financial Reporting**: Prevents revenue manipulation

**Cash-basis reporting** is handled by `Nexus\Analytics` as a **reporting view**, not a transactional mode.

## Payment Allocation Strategies

The package supports multiple strategies for applying payments across open invoices:

### 1. FIFO Strategy (Default)

**First In, First Out** - Applies payment to oldest invoice first.

```php
use Nexus\Receivable\Services\PaymentAllocation\FifoStrategy;

$strategy = new FifoStrategy();
$allocations = $strategy->allocate($paymentAmount, $openInvoices);

// Result: {'invoice-001' => 500.00, 'invoice-002' => 500.00}
```

**Use Case**: Standard practice for most businesses.

### 2. Proportional Strategy

Distributes payment **proportionally** across all open invoices.

```php
use Nexus\Receivable\Services\PaymentAllocation\ProportionalStrategy;

$strategy = new ProportionalStrategy();
$allocations = $strategy->allocate(1000.00, $openInvoices);

// If invoices: $600, $300, $300
// Result: {'inv-1' => 500.00, 'inv-2' => 250.00, 'inv-3' => 250.00}
```

**Use Case**: Customer preference for balanced allocation.

### 3. Manual Strategy

User specifies exact allocation amounts.

```php
use Nexus\Receivable\Services\PaymentAllocation\ManualStrategy;

$strategy = new ManualStrategy([
    'invoice-001' => 750.00,
    'invoice-002' => 250.00,
]);
```

**Use Case**: Specific customer instructions or disputes.

### Configuration

Set customer's preferred allocation strategy in `Party` package:

```php
$customer->setPaymentAllocationPreference('fifo'); // or 'proportional', 'manual'
```

## Credit Limit Enforcement

Credit limits are enforced **before** sales order confirmation to prevent over-extension of credit.

### Individual Customer Limits

```php
use Nexus\Receivable\Contracts\CreditLimitCheckerInterface;

$creditChecker = app(CreditLimitCheckerInterface::class);

try {
    $creditChecker->checkCreditLimit(
        tenantId: $tenantId,
        customerId: $customerId,
        orderTotal: 5000.00,
        currencyCode: 'MYR'
    );
    
    // ✅ Credit approved - proceed with order
    
} catch (CreditLimitExceededException $e) {
    // ❌ Credit limit exceeded
    // Message: "Credit limit exceeded for customer X. 
    //          Credit limit: 10000.00, Current balance: 8000.00, 
    //          Requested: 5000.00, Projected: 13000.00"
}
```

### Customer Group Limits

For corporate customers with multiple subsidiaries under a single credit umbrella:

```php
$creditChecker->checkGroupCreditLimit(
    tenantId: $tenantId,
    groupId: 'megacorp-group',
    orderTotal: 50000.00
);
```

### Outstanding Balance Calculation

Outstanding balance includes invoices with status:
- `POSTED`
- `PARTIALLY_PAID`
- `OVERDUE`

Formula:
```
Available Credit = Credit Limit - Current Outstanding Balance
```

### Integration with Sales

The `CreditLimitChecker` replaces the `NoOpCreditLimitChecker` stub in `Nexus\Sales`:

```php
// In Sales package
$this->creditLimitChecker->checkCreditLimit($tenantId, $customerId, $orderTotal, $currency);
```

## Dunning & Collections

Automated collections workflow for overdue invoices.

### Escalation Levels

| Days Overdue | Level | Action | Integration |
|--------------|-------|--------|-------------|
| 7 | First Reminder | Email notification | `Nexus\Notifier` |
| 14 | Second Reminder | Email + phone call flag | `Nexus\Notifier` |
| 30 | Final Notice | Certified letter | `Nexus\Notifier` + `Nexus\Workflow` |
| 60+ | Collections | Credit status change | `Nexus\Party` |

### Usage

```php
use Nexus\Receivable\Contracts\DunningManagerInterface;

$dunningManager = app(DunningManagerInterface::class);

// Process all overdue invoices
$noticesSent = $dunningManager->processOverdueInvoices(
    tenantId: $tenantId,
    asOfDate: new \DateTimeImmutable('2025-11-21')
);

// Send specific notice
$dunningManager->sendDunningNotice(
    customerId: $customerId,
    escalationLevel: 'second_reminder'
);
```

### Template Variables

Dunning email templates (managed via `Nexus\Notifier`) support:

- `{{customer_name}}`
- `{{invoice_number}}`
- `{{days_overdue}}`
- `{{amount_due}}`
- `{{total_outstanding}}`
- `{{due_date}}`

### Workflow Integration

The dunning process integrates with `Nexus\Workflow`:

```php
// Trigger workflow for 30-day overdue
$workflowEngine->startProcess('dunning_escalation', [
    'customer_id' => $customerId,
    'escalation_level' => 'final_notice',
    'invoices' => $overdueInvoices,
]);
```

## Multi-Currency Support

Handle payments in different currency than invoice.

### Scenario

- **Invoice**: $1,000 USD
- **Payment Received**: 4,500 MYR
- **Exchange Rate** (on payment date): 4.50 MYR/USD

### Processing

```php
$paymentData = [
    'customer_id' => $customerId,
    'amount' => 4500.00,
    'currency' => 'MYR',
    'payment_method' => 'bank_transfer',
    'invoice_allocations' => [
        'invoice-usd-001' => 1000.00, // Invoice amount in USD
    ],
];

$receipt = $receivableManager->recordPayment($tenantId, $paymentData);
```

### Database Storage

```php
payment_receipts:
    amount: 4500.00                    // Original payment amount
    currency: 'MYR'                    // Payment currency
    amount_in_invoice_currency: 1000.00 // Converted amount
    exchange_rate: 4.50                // Rate used
```

### GL Posting with FX Gain/Loss

```
Journal Entry:
Debit:  Cash (1000)                      4,500 MYR
Credit: AR Control (1200)                1,000 USD (equiv 4,500 MYR)
Credit: FX Gain (7100)                   0.00  (or Debit if FX Loss)
```

**FX Gain/Loss Calculation**:
```php
$fxGainLoss = $paymentProcessor->calculateFxGainLoss(
    paymentAmount: 4500.00,
    paymentCurrency: 'MYR',
    invoiceAmount: 1000.00,
    invoiceCurrency: 'USD',
    exchangeRate: 4.50
);
// Result: 0.00 (no gain/loss if rate matches perfectly)
```

### Exchange Rate Source

Exchange rates are retrieved from `Nexus\Currency` package:

```php
$exchangeRate = $currencyManager->getExchangeRate(
    fromCurrency: 'MYR',
    toCurrency: 'USD',
    asOfDate: $paymentDate
);
```

## Integration Points

### With Nexus\Sales

**Trigger**: `SalesOrderFulfilledEvent`

```php
// Sales package publishes event
$this->auditLogger->log($orderId, 'order_fulfilled', '...');

// Receivable listens and creates invoice
$invoice = $receivableManager->createInvoiceFromOrder($tenantId, $salesOrderId);
```

**Credit Limit Check**:

```php
// In SalesOrderManager::confirmOrder()
$this->creditLimitChecker->checkCreditLimit($tenantId, $customerId, $total, $currency);
```

### With Nexus\Finance

**GL Posting** - Revenue Recognition:

```php
$journalId = $financeManager->postJournal(
    tenantId: $tenantId,
    journalDate: $invoiceDate,
    description: "Customer invoice {$invoiceNumber}",
    lines: [
        ['account' => '1200', 'debit' => 1000.00, 'credit' => 0.00], // AR Control
        ['account' => '4100', 'debit' => 0.00, 'credit' => 1000.00], // Revenue
    ]
);
```

**GL Posting** - Payment Receipt:

```php
$journalId = $financeManager->postJournal(
    tenantId: $tenantId,
    journalDate: $paymentDate,
    description: "Payment receipt {$receiptNumber}",
    lines: [
        ['account' => '1000', 'debit' => 1000.00, 'credit' => 0.00], // Cash
        ['account' => '1200', 'debit' => 0.00, 'credit' => 1000.00], // AR Control
    ]
);
```

**GL Posting** - Bad Debt Write-Off:

```php
$journalId = $receivableManager->writeOffInvoice($invoiceId, 'Customer bankruptcy');

// Creates:
// Debit:  Bad Debt Expense (6100)  1,000.00
// Credit: AR Control (1200)        1,000.00
```

### With Nexus\Party

**Customer Entity**:

```php
$customer = $partyManager->getParty($customerId);
$creditLimit = $customer->getCreditLimit();
$groupId = $customer->getCustomerGroupId();
```

### With Nexus\Notifier

**Dunning Emails**:

```php
$notifier->send(
    channel: 'email',
    recipient: $customer->getEmail(),
    template: 'dunning.first_reminder',
    variables: [
        'customer_name' => $customer->getName(),
        'invoice_number' => $invoice->getInvoiceNumber(),
        'days_overdue' => $invoice->getDaysPastDue(new \DateTimeImmutable()),
        'amount_due' => $invoice->getOutstandingBalance(),
    ]
);
```

### With Nexus\Workflow

**Collections Escalation**:

```php
$workflowEngine->startProcess('dunning_cycle', [
    'customer_id' => $customerId,
    'escalation_level' => $dunningManager->getEscalationLevel($daysOverdue),
    'invoices' => $overdueInvoices,
]);
```

### With Nexus\AuditLogger

All state transitions are logged:

```php
$auditLogger->log(
    entity: 'customer_invoice',
    entityId: $invoiceId,
    action: 'posted_to_gl',
    tenantId: $tenantId,
    metadata: ['gl_journal_id' => $journalId]
);
```

**Logged Events**:
- `invoice_created`
- `invoice_approved`
- `invoice_posted`
- `payment_received`
- `payment_applied`
- `invoice_overdue`
- `invoice_written_off`

### With Nexus\EventStream (Optional)

For large enterprises requiring payment lifecycle replay:

```php
// config/eventstream.php
'critical_domains' => [
    'receivable' => env('EVENTSTREAM_RECEIVABLE_ENABLED', false),
],

// If enabled, publish events:
$eventStore->append($aggregateId, new InvoiceGeneratedFromOrderEvent(...));
$eventStore->append($aggregateId, new PaymentReceivedEvent(...));
$eventStore->append($aggregateId, new PaymentAppliedEvent(...));
```

## Usage Examples

### Create Invoice from Sales Order

```php
use Nexus\Receivable\Contracts\ReceivableManagerInterface;

$receivableManager = app(ReceivableManagerInterface::class);

$invoice = $receivableManager->createInvoiceFromOrder(
    tenantId: $tenantId,
    salesOrderId: $salesOrderId,
    overrides: [
        'description' => 'Custom invoice description',
    ]
);

// Invoice status: DRAFT
```

### Approve and Post Invoice

```php
// Approve
$invoice = $receivableManager->approveInvoice(
    invoiceId: $invoice->getId(),
    approvedBy: $userId
);

// Invoice status: APPROVED

// Post to GL (Revenue Recognition)
$glJournalId = $receivableManager->postInvoiceToGL($invoice->getId());

// Invoice status: POSTED
// GL Entry created: Debit AR / Credit Revenue
```

### Record and Apply Payment

```php
// Record payment
$payment = $receivableManager->recordPayment(
    tenantId: $tenantId,
    paymentData: [
        'customer_id' => $customerId,
        'amount' => 1000.00,
        'currency' => 'MYR',
        'payment_method' => 'bank_transfer',
        'receipt_date' => '2025-11-21',
        'reference' => 'TXN12345',
    ]
);

// Apply to invoices (FIFO automatic)
$payment = $receivableManager->applyPayment(
    receiptId: $payment->getId(),
    allocations: [] // Empty = auto-allocate using FIFO
);

// Or manual allocation
$payment = $receivableManager->applyPayment(
    receiptId: $payment->getId(),
    allocations: [
        'invoice-001' => 600.00,
        'invoice-002' => 400.00,
    ]
);
```

### Handle Prepayment (Unapplied Cash)

```php
use Nexus\Receivable\Contracts\UnappliedCashManagerInterface;

$unappliedManager = app(UnappliedCashManagerInterface::class);

// Customer pays before invoice created
$unappliedCash = $unappliedManager->recordUnappliedCash(
    tenantId: $tenantId,
    customerId: $customerId,
    receiptId: $receiptId,
    amount: 5000.00,
    currency: 'MYR'
);

// GL Entry: Debit Cash / Credit Unapplied Revenue (Liability)

// Later, when invoice created
$unappliedManager->applyToInvoice(
    unappliedCashId: $unappliedCash->getId(),
    invoiceId: $newInvoice->getId()
);

// GL Entry reverses liability and applies to invoice
```

### Generate Aging Report

```php
use Nexus\Receivable\Contracts\AgingCalculatorInterface;

$agingCalculator = app(AgingCalculatorInterface::class);

$agingReport = $agingCalculator->calculateAgingReport(
    tenantId: $tenantId,
    asOfDate: new \DateTimeImmutable('2025-11-21')
);

// Result:
[
    [
        'customer_id' => 'cust-001',
        'customer_name' => 'ABC Corp',
        'current' => 5000.00,
        '1_30' => 2000.00,
        '31_60' => 1000.00,
        '61_90' => 500.00,
        'over_90' => 300.00,
        'total' => 8800.00,
    ],
    // ... more customers
]
```

### Write Off Bad Debt

```php
$glJournalId = $receivableManager->writeOffInvoice(
    invoiceId: $badInvoice->getId(),
    reason: 'Customer bankruptcy - uncollectible'
);

// Invoice status: WRITTEN_OFF
// GL Entry: Debit Bad Debt Expense / Credit AR Control
```

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

For issues and questions, please refer to the main Nexus ERP documentation.

---

**Package Version**: 1.0.0  
**Nexus ERP Compatibility**: Laravel 12+  
**PHP Requirement**: ^8.3
