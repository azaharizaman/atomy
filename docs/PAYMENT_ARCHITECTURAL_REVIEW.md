# Payment Suite: Architectural Review & Corrections

**Date:** December 18, 2025  
**Version:** 1.1  
**Status:** ✅ CORRECTIONS APPLIED  
**Reviewer:** Nexus Architecture Team

---

## Executive Summary

### Core Package Violations (Payment)

Upon review, **4 architectural violations** were identified in the `Nexus\Payment` package requirements:

| Issue # | Violation | Severity | Resolution | Status |
|---------|-----------|----------|------------|--------|
| 1 | Dual responsibility (Inbound/Outbound) | ⚠️ Medium | Keep unified; monitor thresholds | ✅ ACCEPTABLE |
| 2 | Approval workflow in atomic package | 🔴 Critical | Changed to status tracking only | ✅ CORRECTED |
| 3 | PAY-052 to PAY-054 duplicate CashManagement | 🔴 Critical | Requirements REMOVED | ✅ CORRECTED |
| 4 | Settlement scope unclear | ⚠️ Medium | Renamed to "Processor Settlement Batching" | ✅ CORRECTED |

### Extension Package Analysis

All **5 extension packages** were analyzed using the same architectural fundamentals:

| Package | Requirements | Violations Found | Status |
|---------|--------------|------------------|--------|
| PaymentRails | 86 | 0 | ✅ COMPLIANT |
| PaymentGateway | 96 | 0 | ✅ COMPLIANT |
| PaymentBank | 78 | 0 | ✅ COMPLIANT |
| PaymentWallet | 116 | 0 | ✅ COMPLIANT |
| PaymentRecurring | 130 | 0 | ✅ COMPLIANT |

---

## Extension Package Compliance Analysis

### Architectural Fundamentals Checked

1. **No approval workflows** - Workflows belong in orchestrator + `Nexus\Workflow`
2. **No reconciliation** - Reconciliation is `Nexus\CashManagement`'s responsibility
3. **Atomic principle** - Packages define WHAT (entities, statuses), not HOW (workflow orchestration)

### PaymentRails (86 Requirements) ✅ COMPLIANT

**Content:** NACHA ACH files, SWIFT MT103, ISO 20022, check processing, virtual cards

**Analysis:**
- ✅ No approval workflows - File generation and validation only
- ✅ No reconciliation - ACH return files are status updates, not bank matching
- ✅ Atomic - Defines file formats and rail capabilities

**Key Observation:** ACH return file processing (RAILS-030 to RAILS-035) correctly tracks payment status changes, does not perform reconciliation.

### PaymentGateway (96 Requirements) ✅ COMPLIANT

**Content:** Stripe, PayPal, Square, Adyen integrations; tokenization; webhooks; chargebacks

**Analysis:**
- ✅ No approval workflows - Webhook handling receives external events
- ✅ No reconciliation - Settlement reports are gateway-level, not bank-level
- ✅ Atomic - Defines gateway abstraction and transaction lifecycle

**Key Observation:** Chargeback handling (GATEWAY-050 to GATEWAY-058) tracks dispute status lifecycle, does not implement workflow logic.

### PaymentBank (78 Requirements) ✅ COMPLIANT

**Content:** Open Banking (PSD2), Plaid, micro-deposit verification, consent management

**Analysis:**
- ✅ No approval workflows - Consent management is user authorization, not internal approval
- ✅ No reconciliation - Account data retrieval, not matching
- ✅ Atomic - Defines bank connectivity abstraction

**Key Observation:** Consent management (BANK-020 to BANK-030) is external user consent to bank access, not internal approval workflows.

### PaymentWallet (116 Requirements) ✅ COMPLIANT

**Content:** Apple Pay, Google Pay, Samsung Pay, GrabPay, Touch 'n Go, Klarna, Afterpay, Affirm, Atome

**Analysis:**
- ✅ No approval workflows - Wallet authorization is external provider flow
- ✅ No reconciliation - Settlement handled by wallet providers
- ✅ Atomic - Defines wallet abstraction including BNPL

**Key Observation:** BNPL order tracking (WALLET-095 to WALLET-110) correctly tracks lifecycle status (pending, approved, shipped, delivered), not internal approval workflows.

### PaymentRecurring (130 Requirements) ✅ COMPLIANT

**Content:** Subscription management, usage-based billing, dunning management, proration, coupons

**Analysis:**
- ✅ No approval workflows - Dunning is automated domain logic, not manual approval
- ✅ No reconciliation - Billing cycles, not bank matching
- ✅ Atomic - Defines subscription and billing abstractions

**Key Observation:** Dunning management (REC-090 to REC-098) defines WHAT happens on payment failure (retry logic, escalation rules, status transitions), which is domain-specific business logic, NOT cross-package workflow orchestration. This correctly belongs in the atomic package.

---

## Issue 1: Dual Responsibility (Inbound vs Outbound)

### Current State

`Nexus\Payment` handles both:
- **Inbound payments** (collections, receipts from customers)
- **Outbound payments** (disbursements, payments to vendors)

### Analysis

Per ARCHITECTURE.md atomicity principles:

> "An atomic package MUST be domain-specific - addresses ONE business domain/capability"

**Interface Count Assessment:**

| Concern | Interfaces | Methods |
|---------|------------|---------|
| Inbound | 3 (PaymentTransaction, PaymentMethod, PaymentExecutor) | ~15 |
| Outbound | 2 (Disbursement, DisbursementQuery/Persist) | ~12 |
| Shared | 2 (PaymentStatus, PaymentMethodType) | ~8 |
| **Total** | **7 core interfaces** | **~35 methods** |

**Threshold Check (from ARCHITECTURE.md):**
- Public Service Classes: <15 ✅
- Total Interface Methods: <40 ✅
- Lines of Code: TBD

### Decision

**ACCEPTABLE AS UNIFIED PACKAGE** if:
1. Inbound and Outbound share common value objects (Money, PaymentReference)
2. Combined interface count stays under thresholds
3. Single `PaymentDirection` enum cleanly separates concerns

**Recommendation:** Keep unified `Nexus\Payment` with clear internal separation via `PaymentDirection` enum. Monitor complexity during implementation.

### Alternative (If Complexity Grows)

If implementation reveals complexity exceeding thresholds:

```
Nexus\Payment (Core)         - Shared abstractions only
├── Nexus\PaymentInbound     - Collection/receipt processing
└── Nexus\PaymentOutbound    - Disbursement/payout processing
```

---

## Issue 2: Approval Workflow in Atomic Package 🔴 CRITICAL

### Current State

**Violation in PAY-031:**
```
PAY-031 | System shall support disbursement approval workflows | P1
```

**Violation in DisbursementPersistInterface:**
```
├── approve(string $id, string $approverId): void
└── reject(string $id, string $rejecterId, string $reason): void
```

**Violation in Enums:**
```
ApprovalStatus
├── PENDING
├── APPROVED
├── REJECTED
└── NOT_REQUIRED
```

### Why This Violates Architecture

Per ARCHITECTURE.md:

> "Atomic packages define WHAT, orchestrators define HOW"

Approval workflows involve:
- Multi-level approval routing → **Workflow concern**
- Escalation on timeout → **Workflow concern**
- Delegation rules → **Workflow concern**
- Approval history → **Workflow concern**

An atomic package should:
- Define the **entity** (Disbursement)
- Define **statuses** that result from workflow (e.g., APPROVED, REJECTED)
- **NOT** implement approval logic

### Resolution

**REMOVE from Payment package:**
- `PAY-031` (approval workflows)
- `approve()` and `reject()` methods from `DisbursementPersistInterface`
- `ApprovalStatus` enum (or move to Workflow package)

**ADD to Payment package:**
```php
// Disbursement only tracks the RESULT of approval, not the process
DisbursementInterface
├── isApproved(): bool
├── getApprovedBy(): ?string
├── getApprovedAt(): ?DateTimeImmutable
└── getRejectionReason(): ?string
```

**Workflow orchestrator will:**
1. Listen to `DisbursementCreatedEvent`
2. Route to appropriate approvers via `Nexus\Workflow`
3. On approval, call `DisbursementPersistInterface::updateApprovalStatus()`
4. Emit `DisbursementApprovedEvent`

### Corrected Interface

```php
// Payment package defines WHAT
DisbursementPersistInterface
├── save(DisbursementInterface $disbursement): DisbursementInterface
├── updateStatus(string $id, DisbursementStatus $status): void
├── markApproved(string $id, string $approverId, DateTimeImmutable $approvedAt): void
├── markRejected(string $id, string $reason, string $rejectedBy): void
└── delete(string $id): void

// Workflow orchestrator defines HOW (in PaymentOperations orchestrator)
DisbursementApprovalCoordinator
├── submitForApproval(string $disbursementId): void
├── handleApprovalDecision(WorkflowDecision $decision): void
└── handleEscalation(string $disbursementId): void
```

---

## Issue 3: PAY-052 to PAY-054 Duplicate CashManagement 🔴 CRITICAL

### Current State

**Payment package requirements:**
```
PAY-052 | System shall define reconciliation interfaces | P1
PAY-053 | System shall support matching payments with bank transactions | P2
PAY-054 | System shall support reconciliation discrepancy tracking | P2
```

**CashManagement package requirements (already implemented):**
```
BUS-CASH-0007 | System MUST automatically reconcile bank transactions with ERP records
BUS-CASH-0010 | System MUST match bank deposits to customer payment receipts
BUS-CASH-0011 | System MUST match bank withdrawals to vendor payments
FUN-CASH-0004 | Provide method to reconcile bank statement automatically
```

### Analysis

**Reconciliation is clearly a CashManagement responsibility:**

| Capability | CashManagement | Payment (proposed) | Conflict? |
|------------|----------------|-------------------|-----------|
| Bank statement import | ✅ BUS-CASH-0003 | ❌ | No |
| Match bank deposits to receipts | ✅ BUS-CASH-0010 | ❌ (should be) | **YES - PAY-053** |
| Match bank withdrawals to payments | ✅ BUS-CASH-0011 | ❌ (should be) | **YES - PAY-053** |
| Reconciliation engine | ✅ ReconciliationEngineInterface | ❌ (should be) | **YES - PAY-052** |
| Discrepancy tracking | ✅ PendingAdjustmentInterface | ❌ (should be) | **YES - PAY-054** |

### Resolution

**REMOVE from Payment package:**
- `PAY-052` (reconciliation interfaces)
- `PAY-053` (matching with bank transactions)
- `PAY-054` (discrepancy tracking)

**CashManagement remains the owner of reconciliation.**

**Payment package responsibility clarified:**
- Payment provides `PaymentTransactionInterface` that CashManagement matches against
- Payment emits `PaymentCompletedEvent` that CashManagement listens to
- Payment does NOT perform reconciliation

---

## Issue 4: Settlement Scope Unclear

### Current State

**Payment package requirements:**
```
PAY-050 | System shall define settlement batch abstraction | P1
PAY-051 | System shall support grouping payments into settlement batches | P1
```

**CashManagement has:**
- Bank account management
- Cash position tracking
- Bank statement reconciliation

### Analysis

**Settlement** can mean:
1. **Payment Settlement** - Grouping transactions for batch submission to payment processor
2. **Bank Settlement** - Receiving funds in bank account

| Settlement Type | Owner | Rationale |
|-----------------|-------|-----------|
| Payment batch to processor | `Payment` or Extension | Specific to payment rail/gateway |
| Funds received in bank | `CashManagement` | Bank account management |

### Resolution

**Clarify PAY-050 and PAY-051:**

These requirements are about **processor settlement batching**, NOT bank reconciliation:
- ACH files are batched before submission → `PaymentRails`
- Gateway transactions settle in batches → `PaymentGateway`

**Rename for clarity:**
```
PAY-050 | System shall define SettlementBatchInterface for processor batching | P1
PAY-051 | System shall support grouping payments into processor settlement batches | P1
```

**Add clarification note:**
> Settlement in Payment context means batching transactions for submission to payment processors. Bank reconciliation (matching settled transactions to bank statements) is handled by `Nexus\CashManagement`.

---

## Corrected Requirements Summary

### Requirements to REMOVE from Payment

| ID | Requirement | Reason |
|----|-------------|--------|
| PAY-031 | Disbursement approval workflows | Workflow concern → orchestrator |
| PAY-052 | Reconciliation interfaces | Duplicate of CashManagement |
| PAY-053 | Matching payments with bank transactions | Duplicate of CashManagement |
| PAY-054 | Reconciliation discrepancy tracking | Duplicate of CashManagement |

### Requirements to MODIFY in Payment

| ID | Original | Modified |
|----|----------|----------|
| PAY-050 | Settlement batch abstraction | **Processor** settlement batch abstraction |
| PAY-051 | Grouping payments into settlement batches | Grouping payments into **processor** settlement batches |

### Interfaces to MODIFY

**DisbursementPersistInterface:**
```php
// REMOVE:
- approve(string $id, string $approverId): void
- reject(string $id, string $rejecterId, string $reason): void

// ADD (result tracking only, not workflow):
+ markApproved(string $id, string $approverId, DateTimeImmutable $approvedAt): void
+ markRejected(string $id, string $reason, string $rejectedBy): void
```

**ApprovalStatus enum:**
```php
// REMOVE entirely from Payment package
// If needed, move to Nexus\Workflow or PaymentOperations orchestrator
```

### Interfaces to REMOVE from Payment

```php
// REMOVE - CashManagement responsibility:
- ReconciliationInterface
- ReconciliationResultInterface
```

---

## Boundary Clarification

### Payment vs CashManagement Boundary

```
┌─────────────────────────────────────────────────────────────────────┐
│                         PAYMENT DOMAIN                               │
│                                                                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐      │
│  │ Payment         │  │ PaymentMethod   │  │ Disbursement    │      │
│  │ Transaction     │  │ Abstraction     │  │ Request         │      │
│  │                 │  │                 │  │ (no workflow)   │      │
│  └────────┬────────┘  └─────────────────┘  └────────┬────────┘      │
│           │                                          │               │
│           │ PaymentCompletedEvent                    │               │
│           │ DisbursementCreatedEvent                 │               │
│           ▼                                          ▼               │
└───────────┼──────────────────────────────────────────┼───────────────┘
            │                                          │
            │                                          │
            ▼                                          ▼
┌───────────────────────────────────────────────────────────────────────┐
│                    ORCHESTRATOR LAYER                                  │
│                                                                        │
│  ┌─────────────────────────┐    ┌─────────────────────────────────┐  │
│  │ PaymentOperations       │    │ Disbursement Approval via       │  │
│  │ Orchestrator            │    │ Nexus\Workflow integration      │  │
│  └────────────┬────────────┘    └────────────────┬────────────────┘  │
│               │                                   │                   │
└───────────────┼───────────────────────────────────┼───────────────────┘
                │                                   │
                ▼                                   ▼
┌───────────────────────────────────────────────────────────────────────┐
│                    CASH MANAGEMENT DOMAIN                              │
│                                                                        │
│  ┌─────────────────────────┐    ┌─────────────────────────────────┐  │
│  │ Bank Reconciliation     │    │ Cash Position                   │  │
│  │ (matches payments to    │    │ (tracks settled funds)          │  │
│  │  bank transactions)     │    │                                 │  │
│  └─────────────────────────┘    └─────────────────────────────────┘  │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### Payment vs Workflow Boundary

```
┌─────────────────────────────────────────────────────────────────────┐
│                 PAYMENT PACKAGE (Atomic)                             │
│                                                                       │
│  Defines WHAT a disbursement IS:                                     │
│  - DisbursementInterface (entity)                                    │
│  - DisbursementStatus (DRAFT, PROCESSING, COMPLETED, FAILED)         │
│  - isApproved(), getApprovedBy(), getApprovedAt()                    │
│                                                                       │
│  Does NOT define HOW approval works.                                 │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                 WORKFLOW PACKAGE (Atomic)                            │
│                                                                       │
│  Defines WHAT a workflow IS:                                         │
│  - ApprovalWorkflowInterface                                         │
│  - ApprovalStepInterface                                             │
│  - ApprovalDecision enum (APPROVED, REJECTED, ESCALATED)             │
│                                                                       │
│  Does NOT know about specific domain entities.                       │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                 PAYMENTOPERATIONS ORCHESTRATOR                       │
│                                                                       │
│  Defines HOW disbursement approval works:                            │
│  - Listens to DisbursementCreatedEvent                               │
│  - Uses Workflow package to route approvals                          │
│  - On decision, updates Disbursement via Payment package             │
│                                                                       │
│  This is the ONLY place where Payment + Workflow are combined.       │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Reconciliation Ownership Decision

### Options Considered

| Option | Description | Recommendation |
|--------|-------------|----------------|
| A | Keep in CashManagement | ✅ **RECOMMENDED** |
| B | Move to Payment | ❌ Violates atomic boundaries |
| C | Create PaymentReconciliation extension | ❌ Unnecessary complexity |

### Rationale for Option A

1. **CashManagement already has complete implementation** (56/58 requirements complete)
2. **Reconciliation is inherently bank-centric** (bank statements → ERP records)
3. **Payment's job is done when transaction completes**
4. **CashManagement consumes payment events** for matching

### Clear Boundary

| Package | Responsibility |
|---------|---------------|
| `Payment` | Transaction lifecycle until completion |
| `CashManagement` | Bank account management, reconciliation, cash position |
| `Receivable` | Customer invoice → payment receipt → GL posting |
| `Payable` | Vendor bill → payment → GL posting |

---

## Action Items

### Immediate (Before Development)

1. ✅ **Update Payment REQUIREMENTS.md:**
   - ✅ Changed PAY-031 to status tracking only
   - ✅ Renamed section 2.6 to "Processor Settlement Batching"
   - ✅ Removed PAY-052, PAY-053, PAY-054 (CashManagement responsibility)
   - ✅ Updated DisbursementInterface with explicit approval methods
   - ✅ Updated DisbursementPersistInterface (markApproved/markRejected)
   - ✅ Removed ApprovalStatus enum

2. 🔄 **Update cross-package analysis document:**
   - Update requirement counts
   - Remove reconciliation overlap section

3. 📋 **Pending: PaymentOperations orchestrator plan:**
   - Add DisbursementApprovalCoordinator
   - Integrate with Nexus\Workflow

4. 📋 **Pending: Document boundary clarifications:**
   - Payment vs CashManagement
   - Payment vs Workflow
   - Add to NEXUS_PACKAGES_REFERENCE.md

### Future Consideration

- If Payment complexity grows beyond thresholds during implementation, split into:
  - `Nexus\Payment` (shared)
  - `Nexus\PaymentInbound`
  - `Nexus\PaymentOutbound`

---

## Corrections Applied Summary

### Requirements Changed in Payment/REQUIREMENTS.md

| Action | ID/Element | Before | After |
|--------|------------|--------|-------|
| MODIFIED | PAY-031 | "approval workflows" | "track disbursement approval status (isApproved, approvedBy, approvedAt)" |
| RENAMED | Section 2.6 | "Settlement & Batching" | "Processor Settlement Batching" |
| REMOVED | PAY-052 | Reconciliation interfaces | N/A (CashManagement) |
| REMOVED | PAY-053 | Payment-bank matching | N/A (CashManagement) |
| REMOVED | PAY-054 | Discrepancy tracking | N/A (CashManagement) |
| MODIFIED | DisbursementInterface | `getApprovalStatus(): ApprovalStatus` | `isApproved()`, `getApprovedBy()`, `getApprovedAt()`, `isRejected()`, etc. |
| MODIFIED | DisbursementPersistInterface | `approve()`, `reject()` | `markApproved()`, `markRejected()`, `updateStatus()` |
| REMOVED | ApprovalStatus | Enum with PENDING, APPROVED, REJECTED, NOT_REQUIRED | N/A (use boolean methods) |

### Extension Packages - No Changes Required

All 5 extension packages are architecturally compliant:
- PaymentRails ✅
- PaymentGateway ✅
- PaymentBank ✅
- PaymentWallet ✅
- PaymentRecurring ✅

---

**Document Updated:** December 2025  
**Status:** ✅ CORRECTIONS APPLIED  
**Next Step:** Awaiting user approval to proceed with development

