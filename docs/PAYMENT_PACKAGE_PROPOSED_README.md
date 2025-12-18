# Nexus\Payment - Core Payment Package

**Version:** 0.1.0  
**Last Updated:** December 18, 2025  
**Status:** Proposed

## Overview

`Nexus\Payment` is a framework-agnostic PHP package providing a unified payment domain model for handling monetary transfers across Accounts Receivable, Accounts Payable, and Payroll domains.

## Installation

```bash
composer require nexus/payment
```

## Architecture

The Payment package follows the **Progressive Disclosure Pattern**:

- **Core Package (`Nexus\Payment`)** - Covers 80% of use cases
- **Extension Packages** - Add specialized capabilities:
  - `Nexus\PaymentRails` - ACH, Wire, Check, RTGS payment rails
  - `Nexus\PaymentGateway` - Stripe, PayPal, Square integrations
  - `Nexus\PaymentBank` - NACHA, SWIFT, ISO 20022 bank files
  - `Nexus\PaymentWallet` - Digital wallets (GrabPay, DuitNow)
  - `Nexus\PaymentRecurring` - Subscriptions and recurring payments

## Core Concepts

### Payment Direction

Payments are either **inbound** (receipts from customers) or **outbound** (disbursements to vendors/employees):

```php
use Nexus\Payment\Enums\PaymentDirection;

$receipt = PaymentDirection::INBOUND;      // Customer pays us
$disbursement = PaymentDirection::OUTBOUND; // We pay vendor/employee
```

### Payment Purpose

```php
use Nexus\Payment\Enums\PaymentPurpose;

PaymentPurpose::INVOICE;   // Invoice settlement (AR/AP)
PaymentPurpose::REFUND;    // Customer/vendor refund
PaymentPurpose::PAYROLL;   // Employee salary
PaymentPurpose::ADVANCE;   // Advance payment
PaymentPurpose::TRANSFER;  // Internal transfer
```

### Payment Status Lifecycle

```
INITIATED → PENDING → AUTHORIZED → PROCESSING → COMPLETED
                ↓                       ↓
              CANCELLED              FAILED
                                       ↓
                                   REVERSED
                                   REFUNDED
```

## Quick Start

### Creating a Payment

```php
use Nexus\Payment\Contracts\PaymentPersistInterface;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethod;
use Nexus\Payment\Enums\PaymentPurpose;
use Nexus\Common\ValueObjects\Money;

final readonly class InvoicePaymentService
{
    public function __construct(
        private PaymentPersistInterface $paymentPersist,
        private PaymentIdGeneratorInterface $idGenerator,
    ) {}

    public function recordCustomerPayment(
        string $tenantId,
        string $customerId,
        string $invoiceId,
        Money $amount,
    ): string {
        $payment = new Payment(
            id: $this->idGenerator->generate(),
            tenantId: $tenantId,
            reference: $this->idGenerator->generateReference(),
            direction: PaymentDirection::INBOUND,
            purpose: PaymentPurpose::INVOICE,
            method: PaymentMethod::BANK_TRANSFER,
            amount: $amount,
            payerPartyId: $customerId,
            payeePartyId: $tenantId, // Company receives
            allocations: [$invoiceId => $amount],
            createdBy: $this->getCurrentUserId(),
        );

        return $this->paymentPersist->save($payment);
    }
}
```

### Allocating Payments

```php
use Nexus\Payment\Contracts\AllocationStrategyInterface;
use Nexus\Payment\Strategies\FifoAllocationStrategy;

final readonly class PaymentAllocationService
{
    public function __construct(
        private AllocationStrategyInterface $strategy = new FifoAllocationStrategy(),
    ) {}

    public function allocate(
        PaymentInterface $payment,
        array $outstandingDocuments,
    ): AllocationResult {
        return $this->strategy->allocate($payment, $outstandingDocuments);
    }
}
```

### Payment Instruments

```php
use Nexus\Payment\Contracts\PaymentInstrumentInterface;
use Nexus\Payment\ValueObjects\BankAccountDetails;

// Register a bank account instrument
$instrument = new PaymentInstrument(
    id: $this->idGenerator->generate(),
    tenantId: $tenantId,
    partyId: $vendorId,
    type: InstrumentType::BANK_ACCOUNT,
    details: new BankAccountDetails(
        accountName: 'Acme Corp',
        accountNumber: '1234567890',
        routingNumber: '021000021',
        bankName: 'Chase Bank',
        currency: 'USD',
    ),
    isDefault: true,
    isVerified: true,
);

$this->instrumentPersist->save($instrument);
```

## Available Interfaces

### Core Contracts

| Interface | Purpose |
|-----------|---------|
| `PaymentInterface` | Core payment entity |
| `PaymentQueryInterface` | Read operations (CQRS) |
| `PaymentPersistInterface` | Write operations (CQRS) |
| `PaymentInstrumentInterface` | Bank account/card abstraction |
| `PaymentInstrumentQueryInterface` | Instrument read operations |
| `PaymentInstrumentPersistInterface` | Instrument write operations |
| `AllocationStrategyInterface` | Payment allocation algorithm |
| `PaymentValidatorInterface` | Payment validation rules |
| `PaymentIdGeneratorInterface` | ID and reference generation |
| `PaymentEventPublisherInterface` | Event publishing contract |

### Enums

| Enum | Values |
|------|--------|
| `PaymentMethod` | `BANK_TRANSFER`, `CHEQUE`, `CASH`, `CREDIT_CARD`, `DEBIT_CARD`, `ACH`, `WIRE`, `CHECK`, `DIRECT_DEBIT`, `VIRTUAL_CARD`, `DIGITAL_WALLET` |
| `PaymentStatus` | `INITIATED`, `PENDING`, `AUTHORIZED`, `PROCESSING`, `COMPLETED`, `FAILED`, `CANCELLED`, `REFUNDED`, `REVERSED` |
| `PaymentDirection` | `INBOUND`, `OUTBOUND` |
| `PaymentPurpose` | `INVOICE`, `REFUND`, `PAYROLL`, `TRANSFER`, `ADVANCE`, `SETTLEMENT` |
| `InstrumentType` | `BANK_ACCOUNT`, `CREDIT_CARD`, `DEBIT_CARD`, `DIGITAL_WALLET`, `VIRTUAL_CARD` |
| `AllocationMethod` | `FIFO`, `LIFO`, `PROPORTIONAL`, `MANUAL`, `OLDEST_FIRST` |

### Value Objects

| Value Object | Purpose |
|--------------|---------|
| `PaymentReference` | Unique, human-readable payment reference |
| `PaymentAmount` | Amount with currency (wraps Money) |
| `BankAccountDetails` | Bank account information |
| `CardDetails` | Tokenized card details (no PAN!) |
| `AllocationResult` | Result of allocation engine |
| `PaymentSchedule` | Scheduled payment information |

### Domain Events

| Event | Triggered When |
|-------|----------------|
| `PaymentInitiatedEvent` | Payment created |
| `PaymentAuthorizedEvent` | Payment authorized by gateway |
| `PaymentProcessingEvent` | Payment execution started |
| `PaymentCompletedEvent` | Payment successfully completed |
| `PaymentFailedEvent` | Payment execution failed |
| `PaymentCancelledEvent` | Payment cancelled before execution |
| `PaymentRefundedEvent` | Payment refunded (partial/full) |
| `PaymentReversedEvent` | Payment reversed (bank reversal) |

## Allocation Strategies

### FIFO (First-In-First-Out)

Allocates payment to oldest documents first:

```php
use Nexus\Payment\Strategies\FifoAllocationStrategy;

$strategy = new FifoAllocationStrategy();
$result = $strategy->allocate($payment, $invoices);
```

### Proportional

Allocates proportionally across all documents:

```php
use Nexus\Payment\Strategies\ProportionalAllocationStrategy;

$strategy = new ProportionalAllocationStrategy();
$result = $strategy->allocate($payment, $invoices);
```

### Manual

User specifies exact allocation amounts:

```php
use Nexus\Payment\Strategies\ManualAllocationStrategy;

$strategy = new ManualAllocationStrategy();
$result = $strategy->allocate($payment, $invoices, [
    'INV-001' => Money::of(500, 'MYR'),
    'INV-002' => Money::of(300, 'MYR'),
]);
```

## Integration with Other Packages

### With Nexus\Receivable

```php
// Receivable package creates inbound payments
use Nexus\Payment\Contracts\PaymentPersistInterface;
use Nexus\Payment\Enums\PaymentDirection;

final readonly class ReceivablePaymentService
{
    public function __construct(
        private PaymentPersistInterface $paymentPersist,
    ) {}

    public function recordReceipt(CustomerReceipt $receipt): void
    {
        $payment = $this->mapReceiptToPayment($receipt);
        $this->paymentPersist->save($payment);
    }
}
```

### With Nexus\Payable

```php
// Payable package creates outbound payments
use Nexus\Payment\Contracts\PaymentPersistInterface;
use Nexus\Payment\Enums\PaymentDirection;

final readonly class PayablePaymentService
{
    public function __construct(
        private PaymentPersistInterface $paymentPersist,
    ) {}

    public function processVendorPayment(VendorPayment $payment): void
    {
        $payment = $this->mapVendorPaymentToPayment($payment);
        $this->paymentPersist->save($payment);
    }
}
```

### With Extensions

```php
// Using PaymentRails extension for ACH payment
use Nexus\PaymentRails\Contracts\PaymentRailSelectorInterface;

final readonly class DisbursementService
{
    public function __construct(
        private PaymentRailSelectorInterface $railSelector,
    ) {}

    public function disburse(PaymentRequest $request): PaymentResult
    {
        $rail = $this->railSelector->selectOptimalRail($request);
        return $rail->execute($request);
    }
}
```

## Security Considerations

### PCI Compliance

- **NEVER** store full card numbers (PAN) in this package
- Use tokenization via `Nexus\PaymentGateway` extension
- Card details are represented as tokens only

### Bank Account Security

- Bank account numbers are stored encrypted
- Use `Nexus\Crypto` for encryption
- Mask account numbers in logs: `****7890`

## Testing

```bash
cd packages/Payment
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.

