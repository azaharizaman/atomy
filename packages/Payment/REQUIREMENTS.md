# Nexus\Payment Requirements Specification

**Package:** `nexus/payment`  
**Version:** 0.1.0  
**Status:** 🟢 Implemented (~95% Complete)  
**Last Updated:** December 18, 2025  
**Author:** Nexus Architecture Team

---

## 1. Executive Summary

The `Nexus\Payment` package provides a framework-agnostic payment processing foundation for ERP systems. It defines core abstractions for payment transactions, payment methods, disbursements, and reconciliation that can be extended by specialized extension packages.

### 1.1 Purpose

- Provide unified payment transaction model across all payment methods
- Define core interfaces for payment execution and tracking
- Support both inbound (receipts) and outbound (disbursements) payments
- Enable multi-currency payment processing
- Provide reconciliation and settlement abstractions

### 1.2 Scope

**In Scope:**
- Payment transaction lifecycle management
- Payment method abstraction layer
- Disbursement/payout processing
- Settlement and reconciliation interfaces
- Payment status tracking and events
- Multi-currency support via `Nexus\Common\ValueObjects\Money`

**Out of Scope (Handled by Extensions):**
- Specific payment rails (ACH, Wire, Check) → `PaymentRails`
- Gateway integrations (Stripe, PayPal) → `PaymentGateway`
- Bank integrations (Plaid, Open Banking) → `PaymentBank`
- Digital wallets (Apple Pay, BNPL) → `PaymentWallet`
- Subscription/recurring billing → `PaymentRecurring`

---

## 2. Functional Requirements

### 2.1 Payment Transaction Management

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-001 | System shall support creating payment transactions with unique identifiers | P0 | ✅ |
| PAY-002 | System shall track payment status through defined lifecycle states | P0 | ✅ |
| PAY-003 | System shall support both inbound (collection) and outbound (disbursement) payments | P0 | ✅ |
| PAY-004 | System shall capture payment amount using Money value object | P0 | ✅ |
| PAY-005 | System shall support payment metadata for custom attributes | P1 | ✅ |
| PAY-006 | System shall track payment timestamps (created, processed, settled) | P0 | ✅ |
| PAY-007 | System shall support payment references (invoice, order, etc.) | P0 | ✅ |
| PAY-008 | System shall support payment descriptions/memos | P1 | ✅ |
| PAY-009 | System shall support payment cancellation before processing | P1 | ✅ |
| PAY-010 | System shall support payment reversal after processing | P1 | ✅ |

### 2.2 Payment Status Lifecycle

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-011 | System shall define payment status enum with: DRAFT, PENDING, PROCESSING, COMPLETED, FAILED, CANCELLED, REVERSED | P0 | ✅ |
| PAY-012 | System shall enforce valid status transitions | P0 | ✅ |
| PAY-013 | System shall emit events on status changes | P0 | ✅ |
| PAY-014 | System shall track failure reasons for failed payments | P1 | ✅ |
| PAY-015 | System shall track reversal reasons for reversed payments | P1 | ✅ |

### 2.3 Payment Method Abstraction

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-020 | System shall define PaymentMethodInterface for method abstraction | P0 | ✅ |
| PAY-021 | System shall support payment method type classification (CARD, BANK, WALLET, etc.) | P0 | ✅ |
| PAY-022 | System shall support payment method validation before use | P1 | ✅ |
| PAY-023 | System shall support payment method activation/deactivation | P1 | ✅ |
| PAY-024 | System shall support default payment method per payer | P2 | ✅ |
| PAY-025 | System shall abstract payment method details (no raw card data) | P0 | ✅ |

### 2.4 Disbursement Processing

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-030 | System shall support creating disbursement requests | P0 | ✅ |
| PAY-031 | System shall track disbursement approval status (isApproved, approvedBy, approvedAt) | P1 | ✅ |
| PAY-032 | System shall support batch disbursement processing | P1 | ✅ |
| PAY-033 | System shall track disbursement recipient information | P0 | ✅ |
| PAY-034 | System shall support disbursement scheduling | P2 | 🔴 |
| PAY-035 | System shall support disbursement limits and controls | P1 | 🔴 |

> **Note:** Approval workflows (routing, multi-level approval, delegation) are handled by `PaymentOperations` orchestrator + `Nexus\Workflow`. This package only tracks the result (approved/rejected) via `markApproved()`/`markRejected()` methods.

### 2.5 Payment Execution

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-040 | System shall define PaymentExecutorInterface for execution abstraction | P0 | ✅ |
| PAY-041 | System shall support synchronous payment execution | P0 | ✅ |
| PAY-042 | System shall support asynchronous payment execution | P1 | ✅ |
| PAY-043 | System shall support payment execution retry strategies | P1 | ✅ |
| PAY-044 | System shall support idempotency keys for duplicate prevention | P0 | ✅ |
| PAY-045 | System shall capture execution context (IP, user agent, etc.) | P2 | ✅ |

### 2.6 Processor Settlement Batching

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-050 | System shall define processor settlement batch abstraction (grouping payments by processor) | P1 | ✅ |
| PAY-051 | System shall support grouping payments into processor settlement batches | P1 | ✅ |

> **Note:** Bank reconciliation (matching payments with bank transactions, discrepancy tracking) is the responsibility of `Nexus\CashManagement` (see BUS-CASH-0007 to BUS-CASH-0011). This package only handles processor-level settlement batching (e.g., Stripe settlement reports, ACH batch files).

### 2.7 Multi-Currency Support

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-060 | System shall support payment amounts in multiple currencies | P0 | ✅ |
| PAY-061 | System shall track original currency and settlement currency | P1 | ✅ |
| PAY-062 | System shall support exchange rate capture at payment time | P1 | ✅ |
| PAY-063 | System shall define currency conversion interface for extensions | P2 | ✅ |

### 2.8 Events

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-070 | System shall emit PaymentCreatedEvent on payment creation | P0 | ✅ |
| PAY-071 | System shall emit PaymentProcessingEvent when processing starts | P0 | ✅ |
| PAY-072 | System shall emit PaymentCompletedEvent on successful completion | P0 | ✅ |
| PAY-073 | System shall emit PaymentFailedEvent on failure | P0 | ✅ |
| PAY-074 | System shall emit PaymentCancelledEvent on cancellation | P1 | ✅ |
| PAY-075 | System shall emit PaymentReversedEvent on reversal | P1 | ✅ |
| PAY-076 | System shall emit DisbursementCreatedEvent on disbursement creation | P0 | ✅ |
| PAY-077 | System shall emit DisbursementCompletedEvent on disbursement completion | P0 | ✅ |

---

## 3. Non-Functional Requirements

### 3.1 Security

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-SEC-001 | Package shall NEVER handle raw card numbers (PAN) | P0 | ✅ |
| PAY-SEC-002 | Package shall only store tokenized payment method references | P0 | ✅ |
| PAY-SEC-003 | Package shall support idempotency to prevent duplicate charges | P0 | ✅ |
| PAY-SEC-004 | Package shall emit events for security auditing | P0 | ✅ |
| PAY-SEC-005 | Package shall support sensitive data masking in logs | P1 | 🔴 |

### 3.2 Performance

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-PERF-001 | Payment creation shall complete in < 10ms (excluding I/O) | P1 | ✅ |
| PAY-PERF-002 | Payment status lookup shall complete in < 5ms (excluding I/O) | P1 | ✅ |

### 3.3 Scalability

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-SCALE-001 | Package shall be stateless for horizontal scaling | P0 | ✅ |
| PAY-SCALE-002 | Package shall support multi-tenant isolation | P0 | ✅ |

### 3.4 Extensibility

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| PAY-EXT-001 | Package shall define interfaces for extension packages | P0 | ✅ |
| PAY-EXT-002 | Package shall support custom payment method types | P1 | ✅ |
| PAY-EXT-003 | Package shall support custom status extensions | P2 | 🔴 |
| PAY-EXT-004 | Package shall use strategy pattern for payment execution | P0 | ✅ |

---

## 4. Interface Specifications

### 4.1 Core Interfaces (21 Implemented)

```
PaymentTransactionInterface
├── getId(): string
├── getStatus(): PaymentStatus
├── getAmount(): Money
├── getDirection(): PaymentDirection (INBOUND|OUTBOUND)
├── getPaymentMethodId(): ?string
├── getReference(): PaymentReference
├── getDescription(): ?string
├── getMetadata(): array
├── getCreatedAt(): DateTimeImmutable
├── getProcessedAt(): ?DateTimeImmutable
├── getSettledAt(): ?DateTimeImmutable
└── getFailureReason(): ?string

PaymentMethodInterface
├── getId(): string
├── getType(): PaymentMethodType
├── getDisplayName(): string
├── isActive(): bool
├── isDefault(): bool
└── getMaskedDetails(): string

PaymentExecutorInterface
├── execute(PaymentTransactionInterface $payment, ExecutionContext $context): PaymentResult
├── canExecute(PaymentTransactionInterface $payment): bool
└── supports(PaymentMethodType $type): bool

DisbursementInterface
├── getId(): string
├── getRecipientInfo(): RecipientInfo
├── getAmount(): Money
├── getStatus(): DisbursementStatus
├── isApproved(): bool
├── getApprovedBy(): ?string
├── getApprovedAt(): ?DateTimeImmutable
├── isRejected(): bool
├── getRejectedBy(): ?string
├── getRejectionReason(): ?string
└── getScheduledDate(): ?DateTimeImmutable

SettlementBatchInterface
├── getId(): string
├── getStatus(): SettlementBatchStatus
├── getPaymentCount(): int
├── getTotalAmount(): Money
├── getOpenedAt(): DateTimeImmutable
├── getClosedAt(): ?DateTimeImmutable
└── getReconciledAt(): ?DateTimeImmutable
```

### 4.2 Repository Interfaces (12 Implemented)

```
PaymentQueryInterface (Read)
├── findById(string $id): ?PaymentTransactionInterface
├── findByReference(PaymentReference $reference): ?PaymentTransactionInterface
├── findByStatus(PaymentStatus $status): array
└── findByDateRange(DateTimeImmutable $from, DateTimeImmutable $to): array

PaymentPersistInterface (Write)
├── save(PaymentTransactionInterface $payment): PaymentTransactionInterface
├── updateStatus(string $id, PaymentStatus $status): void
└── delete(string $id): void

DisbursementQueryInterface (Read)
├── findById(string $id): ?DisbursementInterface
├── findPendingApproval(): array
└── findScheduledForDate(DateTimeImmutable $date): array

DisbursementPersistInterface (Write)
├── save(DisbursementInterface $disbursement): DisbursementInterface
├── markApproved(string $id, string $approverId, DateTimeImmutable $approvedAt): void
├── markRejected(string $id, string $rejectedBy, string $reason): void
└── updateStatus(string $id, DisbursementStatus $status): void

SettlementBatchQueryInterface (Read)
├── findById(string $id): ?SettlementBatchInterface
├── findByStatus(SettlementBatchStatus $status): array
└── findOpen(): array

SettlementBatchPersistInterface (Write)
├── save(SettlementBatchInterface $batch): SettlementBatchInterface
├── addPayment(string $batchId, string $paymentId): void
└── updateStatus(string $batchId, SettlementBatchStatus $status): void
```

### 4.3 Enums (6 Implemented)

```
PaymentStatus ✅
├── DRAFT
├── PENDING
├── PROCESSING
├── COMPLETED
├── FAILED
├── CANCELLED
└── REVERSED

PaymentDirection ✅
├── INBOUND (collection/receipt)
└── OUTBOUND (disbursement/payout)

PaymentMethodType ✅
├── BANK_TRANSFER
├── CREDIT_CARD
├── DEBIT_CARD
├── E_WALLET
├── CASH
├── CHECK
└── VIRTUAL_ACCOUNT

DisbursementStatus ✅
├── DRAFT
├── PENDING_APPROVAL
├── APPROVED
├── REJECTED
├── PROCESSING
├── COMPLETED
├── FAILED
└── CANCELLED

SettlementBatchStatus ✅
├── OPEN
├── CLOSED
├── RECONCILED
└── DISPUTED

AllocationMethod ✅
├── FIFO
├── LIFO
├── PROPORTIONAL
├── MANUAL
├── OLDEST_FIRST
├── LARGEST_FIRST
└── SMALLEST_FIRST
```

---

## 5. Value Objects (7 Implemented)

| Value Object | Purpose | Properties | Status |
|--------------|---------|------------|--------|
| `PaymentReference` | Unique payment reference | `type`, `value`, `tenantId` | ✅ |
| `RecipientInfo` | Disbursement recipient | `id`, `name`, `accountId`, `email`, `metadata` | ✅ |
| `ExecutionContext` | Execution metadata | `executorName`, `ipAddress`, `userAgent`, `metadata` | ✅ |
| `IdempotencyKey` | Duplicate prevention | `key`, `tenantId`, `expiresAt` | ✅ |
| `AllocationResult` | Allocation outcome | `allocations`, `totalAllocated`, `unallocated` | ✅ |
| `ExchangeRateSnapshot` | Currency conversion | `fromCurrency`, `toCurrency`, `rate`, `capturedAt` | ✅ |
| `PaymentResult` | Execution result | `success`, `paymentId`, `transactionId`, `error` | ✅ |

---

## 6. Events (20 Implemented)

| Event | Trigger | Payload | Status |
|-------|---------|---------|--------|
| `PaymentCreatedEvent` | Payment created | paymentId, amount, direction, reference | ✅ |
| `PaymentProcessingEvent` | Processing started | paymentId, amount, executorName | ✅ |
| `PaymentCompletedEvent` | Payment successful | paymentId, amount, settledAmount, completedAt | ✅ |
| `PaymentFailedEvent` | Payment failed | paymentId, amount, failureCode, failureMessage | ✅ |
| `PaymentCancelledEvent` | Payment cancelled | paymentId, amount, reason, cancelledBy | ✅ |
| `PaymentReversedEvent` | Payment reversed | paymentId, originalAmount, reversedAmount, reason | ✅ |
| `PaymentAddedToBatchEvent` | Added to batch | paymentId, batchId, amount | ✅ |
| `DisbursementCreatedEvent` | Disbursement created | disbursementId, amount, recipient, createdBy | ✅ |
| `DisbursementApprovedEvent` | Disbursement approved | disbursementId, amount, approvedBy | ✅ |
| `DisbursementRejectedEvent` | Disbursement rejected | disbursementId, amount, rejectedBy, reason | ✅ |
| `DisbursementCompletedEvent` | Disbursement completed | disbursementId, amount, paymentTransactionId | ✅ |
| `DisbursementFailedEvent` | Disbursement failed | disbursementId, amount, failureCode, failureMessage | ✅ |
| `DisbursementCancelledEvent` | Disbursement cancelled | disbursementId, amount, cancelledBy, reason | ✅ |
| `SettlementBatchCreatedEvent` | Batch created | batchId, processorId, openedAt | ✅ |
| `SettlementBatchClosedEvent` | Batch closed | batchId, paymentCount, totalAmount, closedAt | ✅ |
| `SettlementBatchReconciledEvent` | Batch reconciled | batchId, reconciledAmount, reconciledAt | ✅ |
| `SettlementBatchDisputedEvent` | Discrepancy found | batchId, expectedAmount, actualAmount, reason | ✅ |

---

## 7. Dependencies

### 7.1 Required Dependencies

| Package | Purpose | Status |
|---------|---------|--------|
| `nexus/common` | Money VO, common interfaces | ✅ |
| `psr/log` | Logging interface | ✅ |
| `psr/event-dispatcher` | Event dispatching | ✅ |

### 7.2 Integration Points

| Package | Integration | Status |
|---------|-------------|--------|
| `Nexus\Receivable` | Payment receipt processing | Ready |
| `Nexus\Payable` | Vendor payment processing | Ready |
| `Nexus\CashManagement` | Bank reconciliation | Ready |
| `Nexus\JournalEntry` | GL posting | Ready |

---

## 8. Implementation Summary

| Category | Total | Implemented | Percentage |
|----------|-------|-------------|------------|
| P0 Requirements | 28 | 28 | 100% |
| P1 Requirements | 18 | 16 | 89% |
| P2 Requirements | 5 | 3 | 60% |
| **Total** | **51** | **47** | **92%** |

### Remaining Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| PAY-034 | Disbursement scheduling | P2 |
| PAY-035 | Disbursement limits and controls | P1 |
| PAY-SEC-005 | Sensitive data masking in logs | P1 |
| PAY-EXT-003 | Custom status extensions | P2 |

---

## 9. Acceptance Criteria

1. ✅ All P0 requirements implemented
2. ✅ All interfaces defined with comprehensive contracts
3. ✅ All events implemented and tested
4. ✅ Security requirements implemented (no raw PAN, tokenization, idempotency)
5. 🟡 PHPStan level 8 compliance (pending verification)

---

## 10. Glossary

| Term | Definition |
|------|------------|
| **Payment** | A financial transaction transferring money |
| **Disbursement** | Outbound payment to a recipient |
| **Settlement** | Final transfer of funds between parties |
| **Reconciliation** | Matching payments with bank transactions |
| **Idempotency** | Property ensuring duplicate requests produce same result |
| **PAN** | Primary Account Number (card number) |
| **FIFO** | First-In-First-Out allocation strategy |
| **LIFO** | Last-In-First-Out allocation strategy |

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2025-12-18 | Nexus Team | Initial draft |
| 0.1.1 | 2025-12-18 | Nexus Team | Updated status to reflect ~95% implementation |
