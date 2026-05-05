# Nexus\Payment Package Feasibility Study

**Version:** 1.0  
**Date:** December 18, 2025  
**Author:** Nexus Architecture Team  
**Status:** Proposal

---

## Executive Summary

This document analyzes the feasibility of extracting payment-related functionality from existing Nexus packages into a dedicated **`Nexus\Payment`** atomic package with progressive disclosure extensions. The study finds that a Payment package is **highly feasible and recommended** due to significant duplication across packages and the need for a unified payment abstraction layer.

---

## 1. Current State Analysis

### 1.1 Payment Functionality Distribution

Payment-related code is currently scattered across multiple packages and orchestrators:

| Package/Orchestrator | Payment Functionality | Issues |
|---------------------|----------------------|--------|
| **`Nexus\Receivable`** | `PaymentReceiptInterface`, `PaymentMethod` enum, `PaymentAllocationStrategyInterface`, `PaymentProcessorInterface`, `PaymentHistoryRepositoryInterface` | Customer-centric, lacks B2B payment rails |
| **`Nexus\Payable`** | `PaymentInterface`, `PaymentStatus` enum, `PaymentMethod` enum (duplicate!), `PaymentSchedulerInterface`, `PaymentAllocationInterface` | Vendor-centric, limited payment methods |
| **`Nexus\CashManagement`** | Bank account management, reconciliation | Focuses on reconciliation, not payment execution |
| **`Nexus\Connector`** | `PaymentGatewayConnectorInterface` | External gateway abstraction only (Stripe, PayPal) |
| **`ProcurementOperations`** | `PaymentProcessingCoordinator`, `PaymentBatchBuilder`, payment strategies (ACH, Wire, Check, Virtual Card), bank file generators (NACHA, SWIFT MT101, Positive Pay) | Orchestration-level, not reusable as atomic package |
| **`HumanResourceOperations`** | `BankExportGatewayInterface` (planned) | Payroll disbursement, not general payment |
| **`Nexus\HRM\PayrollCore`** | Payment file generation | Payroll-specific, not reusable |

### 1.2 Identified Duplication

| Duplicate Element | Locations | Impact |
|-------------------|-----------|--------|
| **`PaymentMethod` enum** | `Nexus\Receivable\Enums\PaymentMethod`, `Nexus\Payable\Enums\PaymentMethod` | Different values, inconsistent behavior |
| **`PaymentStatus` concepts** | Multiple packages define payment states differently | No unified payment lifecycle |
| **Payment allocation logic** | Both Receivable and Payable implement allocation strategies | Duplicated algorithms |
| **Bank file generation** | ProcurementOperations (vendor payments), HRM (payroll) | Should be unified |

### 1.3 Gap Analysis

| Missing Capability | Business Impact |
|--------------------|-----------------|
| **Unified payment entity** | Cannot track payments across AR/AP in one view |
| **Payment instrument management** | No central store for payment methods (bank accounts, cards, wallets) |
| **Payment routing engine** | Manual selection of payment rails (ACH vs Wire vs Check) |
| **Payment reconciliation** | Each domain reconciles independently |
| **Real-time payment status** | No unified event stream for payment lifecycle |
| **Multi-currency payment** | FX conversion scattered across packages |
| **Payment security** | PCI compliance, tokenization, encryption scattered |

---

## 2. Proposed Solution: Nexus\Payment with Extensions

### 2.1 Architecture Overview

Following the **Progressive Disclosure Pattern** from ARCHITECTURE.md:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        PROGRESSIVE DISCLOSURE                          │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ CORE: Nexus\Payment (80% use case)                              │  │
│  │ • Payment entity & lifecycle                                     │  │
│  │ • Payment methods (enum)                                         │  │
│  │ • Payment status management                                      │  │
│  │ • Basic allocation strategies                                    │  │
│  │ • Payment instrument abstraction                                 │  │
│  │ • Event publishing contracts                                     │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                              │                                         │
│         ┌────────────────────┼────────────────────┐                   │
│         ▼                    ▼                    ▼                   │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐            │
│  │PaymentRails  │    │PaymentGateway│    │PaymentBank   │            │
│  │ ACH, Wire,   │    │ Stripe,      │    │ NACHA, SWIFT │            │
│  │ Check, RTGS  │    │ PayPal, etc. │    │ ISO20022     │            │
│  └──────────────┘    └──────────────┘    └──────────────┘            │
│                                                                        │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐            │
│  │PaymentWallet │    │PaymentRecur  │    │PaymentRegion │            │
│  │ Digital      │    │ Subscription │    │ MY, SG, US   │            │
│  │ wallets      │    │ recurring    │    │ regulations  │            │
│  └──────────────┘    └──────────────┘    └──────────────┘            │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Core Package: `Nexus\Payment`

**Purpose:** Unified payment domain model covering 80% of payment use cases across AR, AP, and payroll.

**Responsibilities:**
1. **Payment Entity & Lifecycle** - Unified payment representation with status FSM
2. **Payment Method Registry** - Canonical enumeration of all payment methods
3. **Payment Instrument Abstraction** - Bank accounts, cards, wallets as first-class entities
4. **Allocation Engine** - FIFO, Manual, Proportional allocation strategies
5. **Payment Event Contracts** - Domain events for payment lifecycle
6. **Validation Rules** - Amount limits, currency validation, fraud indicators

**Proposed Structure:**

```
packages/Payment/
├── composer.json
├── README.md
├── LICENSE
├── IMPLEMENTATION_SUMMARY.md
├── REQUIREMENTS.md
├── TEST_SUITE_SUMMARY.md
├── VALUATION_MATRIX.md
├── CHANGELOG.md
├── UPGRADE.md
├── CONTRIBUTING.md
├── SECURITY.md
├── CODE_OF_CONDUCT.md
├── .gitignore
├── docs/
│   ├── getting-started.md
│   ├── api-reference.md
│   └── integration-guide.md
├── src/
│   ├── Contracts/
│   │   ├── PaymentInterface.php             # Core payment entity
│   │   ├── PaymentQueryInterface.php        # Read operations (CQRS)
│   │   ├── PaymentPersistInterface.php      # Write operations (CQRS)
│   │   ├── PaymentInstrumentInterface.php   # Bank account/card abstraction
│   │   ├── PaymentInstrumentQueryInterface.php
│   │   ├── PaymentInstrumentPersistInterface.php
│   │   ├── AllocationStrategyInterface.php  # Payment allocation
│   │   ├── PaymentValidatorInterface.php    # Validation rules
│   │   ├── PaymentIdGeneratorInterface.php  # ID/reference generation
│   │   └── PaymentEventPublisherInterface.php # Event contract
│   │
│   ├── Enums/
│   │   ├── PaymentMethod.php           # Unified: BANK_TRANSFER, CHEQUE, CARD, CASH, ACH, WIRE, CHECK, DIRECT_DEBIT, VIRTUAL_CARD
│   │   ├── PaymentStatus.php           # INITIATED, PENDING, AUTHORIZED, PROCESSING, COMPLETED, FAILED, CANCELLED, REFUNDED, REVERSED
│   │   ├── PaymentDirection.php        # INBOUND (receipt), OUTBOUND (disbursement)
│   │   ├── PaymentPurpose.php          # INVOICE, REFUND, PAYROLL, TRANSFER, ADVANCE
│   │   ├── InstrumentType.php          # BANK_ACCOUNT, CREDIT_CARD, DEBIT_CARD, DIGITAL_WALLET, VIRTUAL_CARD
│   │   └── AllocationMethod.php        # FIFO, LIFO, PROPORTIONAL, MANUAL, OLDEST_FIRST
│   │
│   ├── ValueObjects/
│   │   ├── PaymentReference.php        # Unique payment reference
│   │   ├── PaymentAmount.php           # Amount with currency (wraps Money)
│   │   ├── BankAccountDetails.php      # Account number, routing, IBAN, SWIFT
│   │   ├── CardDetails.php             # Tokenized card (no PAN storage!)
│   │   ├── AllocationResult.php        # Result of allocation engine
│   │   └── PaymentSchedule.php         # Scheduled payment details
│   │
│   ├── Events/
│   │   ├── PaymentInitiatedEvent.php
│   │   ├── PaymentAuthorizedEvent.php
│   │   ├── PaymentProcessingEvent.php
│   │   ├── PaymentCompletedEvent.php
│   │   ├── PaymentFailedEvent.php
│   │   ├── PaymentCancelledEvent.php
│   │   ├── PaymentRefundedEvent.php
│   │   └── PaymentReversedEvent.php
│   │
│   ├── Services/
│   │   ├── AllocationEngine.php        # Core allocation logic
│   │   ├── PaymentReferenceGenerator.php # Reference number generation
│   │   └── PaymentValidator.php        # Validation rules engine
│   │
│   ├── Strategies/
│   │   ├── FifoAllocationStrategy.php
│   │   ├── LifoAllocationStrategy.php
│   │   ├── ProportionalAllocationStrategy.php
│   │   └── ManualAllocationStrategy.php
│   │
│   └── Exceptions/
│       ├── PaymentException.php        # Base exception
│       ├── PaymentNotFoundException.php
│       ├── InsufficientFundsException.php
│       ├── InvalidPaymentMethodException.php
│       ├── PaymentValidationException.php
│       ├── AllocationException.php
│       └── PaymentInstrumentException.php
│
└── tests/
    ├── Unit/
    └── Feature/
```

**Key Interfaces:**

```php
<?php
declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethod;
use Nexus\Payment\Enums\PaymentPurpose;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\ValueObjects\PaymentReference;

/**
 * Core payment entity interface.
 *
 * Represents a monetary transfer between parties - unified for both
 * inbound (customer receipts) and outbound (vendor/employee payments).
 */
interface PaymentInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getReference(): PaymentReference;
    public function getDirection(): PaymentDirection;
    public function getPurpose(): PaymentPurpose;
    public function getMethod(): PaymentMethod;
    public function getStatus(): PaymentStatus;
    
    // Amounts
    public function getAmount(): Money;
    public function getFeeAmount(): ?Money;
    public function getNetAmount(): Money;
    
    // Parties
    public function getPayerPartyId(): string;
    public function getPayeePartyId(): string;
    public function getPayerInstrumentId(): ?string;
    public function getPayeeInstrumentId(): ?string;
    
    // Allocations
    /** @return array<string, Money> Document ID => Allocated amount */
    public function getAllocations(): array;
    public function getAllocatedAmount(): Money;
    public function getUnallocatedAmount(): Money;
    
    // Integration
    public function getExternalReference(): ?string;  // Gateway transaction ID
    public function getGlJournalId(): ?string;        // Posted journal entry
    
    // Timing
    public function getInitiatedAt(): \DateTimeImmutable;
    public function getScheduledDate(): ?\DateTimeImmutable;
    public function getProcessedAt(): ?\DateTimeImmutable;
    public function getCompletedAt(): ?\DateTimeImmutable;
    
    // Audit
    public function getCreatedBy(): string;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getUpdatedAt(): \DateTimeImmutable;
}
```

### 2.3 Extension Packages

Following the Progressive Disclosure pattern, extension packages add specialized capabilities:

#### Extension 1: `Nexus\PaymentRails`

**Purpose:** Payment rail implementations (ACH, Wire, Check, RTGS, SEPA, FPS).

**When to Use:**
- Business needs to send payments via specific banking rails
- Requirement for automated payment execution
- Need for payment method selection based on rules

**Capabilities:**
- Payment rail strategy pattern (existing code in ProcurementOperations)
- Rail selection engine (cost optimization, speed requirements)
- Rail-specific validation rules
- Processing time calculations
- Fee estimation per rail

**Key Interfaces:**
```php
interface PaymentRailInterface
{
    public function getRail(): PaymentRail;
    public function supports(PaymentRequest $request): bool;
    public function validate(PaymentRequest $request): array;
    public function execute(PaymentRequest $request): PaymentRailResult;
    public function getProcessingDays(): int;
    public function calculateFee(Money $amount): Money;
    public function supportsInternational(): bool;
    public function supportsSameDay(): bool;
}

interface PaymentRailSelectorInterface
{
    public function selectOptimalRail(PaymentRequest $request): PaymentRailInterface;
    public function getAvailableRails(PaymentRequest $request): array;
}
```

---

#### Extension 2: `Nexus\PaymentGateway`

**Purpose:** Online payment gateway integrations (Stripe, PayPal, Square, Adyen, etc.).

**When to Use:**
- E-commerce payment acceptance
- Credit/debit card processing
- Digital wallet support (Apple Pay, Google Pay)
- PCI compliance requirements

**Capabilities:**
- Gateway abstraction (adapter pattern)
- Payment intent / authorization
- Capture / Void / Refund
- Tokenization integration
- Webhook handling contracts

**Key Interfaces:**
```php
interface PaymentGatewayInterface
{
    public function authorize(AuthorizationRequest $request): AuthorizationResult;
    public function capture(CaptureRequest $request): CaptureResult;
    public function charge(ChargeRequest $request): ChargeResult;
    public function refund(RefundRequest $request): RefundResult;
    public function void(VoidRequest $request): VoidResult;
}

interface TokenizationInterface
{
    public function tokenize(CardDetails $card): PaymentToken;
    public function detokenize(PaymentToken $token): CardDetails;
    public function validate(PaymentToken $token): bool;
}
```

---

#### Extension 3: `Nexus\PaymentBank`

**Purpose:** Bank file generation (NACHA, SWIFT MT101, ISO 20022, Positive Pay).

**When to Use:**
- Batch payment processing via bank portals
- Treasury operations
- Corporate banking integration
- Positive Pay fraud prevention

**Capabilities:**
- Multi-format bank file generation (existing code in ProcurementOperations)
- File validation and checksum
- Bank-specific formatting rules
- Transmission protocol contracts

**Key Interfaces:**
```php
interface BankFileGeneratorInterface
{
    public function getFormat(): BankFileFormat;
    public function supports(PaymentBatch $batch): bool;
    public function validate(PaymentBatch $batch): array;
    public function generate(PaymentBatch $batch): BankFileResult;
}

interface BankFileTransmitterInterface
{
    public function transmit(BankFileResult $file, TransmissionConfig $config): TransmissionResult;
}
```

---

#### Extension 4: `Nexus\PaymentWallet`

**Purpose:** Digital wallet integrations (GrabPay, Touch 'n Go, DuitNow, Alipay, WeChat Pay).

**When to Use:**
- Regional e-wallet acceptance
- QR code payment generation
- Wallet-to-wallet transfers

**Capabilities:**
- Wallet provider adapters
- QR code generation
- Real-time payment notification
- Wallet balance inquiry (where supported)

---

#### Extension 5: `Nexus\PaymentRecurring`

**Purpose:** Subscription and recurring payment management.

**When to Use:**
- SaaS subscription billing
- Installment payments
- Standing orders

**Capabilities:**
- Recurring schedule management
- Automatic retry on failure
- Dunning management integration
- Subscription lifecycle events

---

#### Extension 6: `Nexus\PaymentRegionMY` / `Nexus\PaymentRegionSG` / etc.

**Purpose:** Regional payment regulations and methods.

**When to Use:**
- Country-specific payment rails (DuitNow MY, PayNow SG, UPI India)
- Regulatory compliance (Central Bank reporting)
- Regional bank format requirements

---

## 3. Integration Strategy

### 3.1 How Existing Packages Integrate

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          INTEGRATION FLOW                                 │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌───────────────┐    ┌───────────────┐    ┌───────────────┐           │
│  │Nexus\Receivable│   │Nexus\Payable   │   │Nexus\Payroll   │           │
│  │               │    │               │    │               │           │
│  │ Creates       │    │ Creates       │    │ Creates       │           │
│  │ Payment with  │    │ Payment with  │    │ Payment with  │           │
│  │ Direction:    │    │ Direction:    │    │ Direction:    │           │
│  │ INBOUND       │    │ OUTBOUND      │    │ OUTBOUND      │           │
│  │ Purpose:      │    │ Purpose:      │    │ Purpose:      │           │
│  │ INVOICE       │    │ INVOICE       │    │ PAYROLL       │           │
│  └───────┬───────┘    └───────┬───────┘    └───────┬───────┘           │
│          │                    │                    │                    │
│          └────────────────────┼────────────────────┘                    │
│                               ▼                                         │
│                    ┌───────────────────┐                               │
│                    │  Nexus\Payment    │                               │
│                    │  (Core Package)   │                               │
│                    │                   │                               │
│                    │ • Unified entity  │                               │
│                    │ • Status FSM      │                               │
│                    │ • Allocation      │                               │
│                    │ • Validation      │                               │
│                    └─────────┬─────────┘                               │
│                              │                                          │
│         ┌────────────────────┼────────────────────┐                    │
│         ▼                    ▼                    ▼                    │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐             │
│  │PaymentRails  │    │PaymentGateway│    │PaymentBank   │             │
│  │              │    │              │    │              │             │
│  │ Execute via  │    │ Execute via  │    │ Generate     │             │
│  │ ACH/Wire/etc │    │ Stripe/etc   │    │ bank files   │             │
│  └──────────────┘    └──────────────┘    └──────────────┘             │
│                                                                        │
└──────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Refactoring Path

**Phase 1: Create Core Package (2 weeks)**
1. Create `Nexus\Payment` with unified interfaces
2. Implement core enums, value objects, events
3. Implement allocation strategies
4. Add comprehensive tests

**Phase 2: Create Extensions (3 weeks)**
1. Extract payment rail strategies from ProcurementOperations → `Nexus\PaymentRails`
2. Extract bank file generators from ProcurementOperations → `Nexus\PaymentBank`
3. Create gateway abstraction → `Nexus\PaymentGateway`

**Phase 3: Integrate Existing Packages (2 weeks)**
1. Refactor `Nexus\Receivable` to depend on `Nexus\Payment`
2. Refactor `Nexus\Payable` to depend on `Nexus\Payment`
3. Refactor `ProcurementOperations` to use extensions
4. Update `Nexus\HRM\PayrollCore` to use payment interfaces

**Phase 4: Deprecation (1 week)**
1. Deprecate duplicate enums/interfaces in Receivable/Payable
2. Add migration guides
3. Update documentation

---

## 4. Dependency Analysis

### 4.1 Core Package Dependencies

```
Nexus\Payment (Core)
├── azaharizaman/nexus-common (Money VO, TenantId VO)
├── psr/log
└── psr/event-dispatcher
```

### 4.2 Extension Dependencies

```
Nexus\PaymentRails
├── azaharizaman/nexus-payment (core)
└── azaharizaman/nexus-common

Nexus\PaymentGateway
├── azaharizaman/nexus-payment (core)
├── azaharizaman/nexus-connector (for gateway adapters)
└── azaharizaman/nexus-crypto (for tokenization)

Nexus\PaymentBank
├── azaharizaman/nexus-payment (core)
├── azaharizaman/nexus-common
└── azaharizaman/nexus-crypto (for file encryption)

Nexus\PaymentWallet
├── azaharizaman/nexus-payment (core)
└── azaharizaman/nexus-connector

Nexus\PaymentRecurring
├── azaharizaman/nexus-payment (core)
├── azaharizaman/nexus-scheduler
└── azaharizaman/nexus-notifier (for dunning)
```

### 4.3 Packages That Will Depend on Payment

| Package | Dependency Type | Notes |
|---------|-----------------|-------|
| `Nexus\Receivable` | Core | Uses Payment for customer receipts |
| `Nexus\Payable` | Core + Rails + Bank | Uses Payment for vendor payments |
| `Nexus\HRM\PayrollCore` | Core + Bank | Uses Payment for salary disbursement |
| `Nexus\CashManagement` | Core | Reconciles Payment entities |
| `ProcurementOperations` | Core + Rails + Bank | Orchestrates vendor payment flows |
| `HumanResourceOperations` | Core + Bank | Orchestrates payroll disbursement |

---

## 5. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **Breaking changes to existing packages** | High | High | Semantic versioning, deprecation notices, migration period |
| **Circular dependencies** | Medium | High | Payment depends only on Common; never on Receivable/Payable |
| **Scope creep** | Medium | Medium | Strict adherence to Progressive Disclosure; extensions for advanced features |
| **PCI compliance complexity** | Medium | High | PaymentGateway extension handles tokenization; core never stores PAN |
| **Regional variations** | Low | Medium | Regional extensions (PaymentRegionMY, etc.) |

---

## 6. Benefits Summary

| Benefit | Description |
|---------|-------------|
| **Reduced Duplication** | Single source of truth for PaymentMethod, PaymentStatus enums |
| **Unified Payment View** | Track all payments (AR, AP, payroll) in one domain |
| **Flexible Extension** | Add payment rails/gateways without modifying core |
| **Improved Testability** | Isolated payment logic with mockable interfaces |
| **Compliance Ready** | Centralized PCI/security controls in extensions |
| **Framework Agnostic** | Pure PHP core, framework adapters in adapters/ |

---

## 7. Recommendation

**The creation of `Nexus\Payment` is HIGHLY RECOMMENDED.**

**Rationale:**
1. Significant duplication exists across Receivable, Payable, and orchestrators
2. Payment is a distinct domain that crosses AR/AP/Payroll boundaries
3. Progressive Disclosure pattern allows incremental adoption
4. Follows established architecture patterns (Inventory → InventoryLot/Serial)
5. Enables future capabilities (real-time payments, cross-border, crypto)

**Suggested Priority:**
1. **P0 (Core):** `Nexus\Payment` - 2 weeks
2. **P1:** `Nexus\PaymentRails` - 1 week (extract from ProcurementOperations)
3. **P1:** `Nexus\PaymentBank` - 1 week (extract from ProcurementOperations)
4. **P2:** `Nexus\PaymentGateway` - 2 weeks
5. **P3:** `Nexus\PaymentRecurring`, `Nexus\PaymentWallet`, Regional - As needed

---

## 8. Next Steps

1. **Review and Approve** - Architecture team reviews this proposal
2. **Create Package Skeleton** - Initialize `packages/Payment/` with 13 mandatory files
3. **Define Contracts** - Finalize interfaces with stakeholders
4. **Implement Core** - Build core package with tests
5. **Extract Extensions** - Migrate code from ProcurementOperations
6. **Integration Testing** - Validate with Receivable/Payable
7. **Documentation** - Update NEXUS_PACKAGES_REFERENCE.md

---

**Prepared by:** Nexus Architecture Team  
**Review Date:** December 18, 2025  
**Approval Status:** Pending

