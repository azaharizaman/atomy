# ProcurementOperations Orchestrator Implementation Plan

**Document Version:** 1.1  
**Created:** December 6, 2025  
**Last Updated:** December 6, 2025  
**Target Orchestrator:** `Nexus\ProcurementOperations`  
**Branch Prefix:** `feature/procurement-operations`

---

## Executive Summary

This document tracks the implementation progress for the `ProcurementOperations` orchestrator, which coordinates the complete Procure-to-Pay (P2P) cycle across multiple atomic packages.

### Scope

The orchestrator coordinates:
- `Nexus\Procurement` (requisition → PO → goods receipt)
- `Nexus\Payable` (invoice → matching → payment)
- `Nexus\Inventory` (stock receipt)
- `Nexus\JournalEntry` (GL posting)
- `Nexus\Budget` (commitment tracking)
- `Nexus\Workflow` (approval workflows)
- `Nexus\Notifier` (notifications)

---

## Phase Overview

| Phase | Description | Status | Branch | PR |
|-------|-------------|--------|--------|-----|
| **Phase 0** | Add Domain Events to Procurement & Payable | ✅ Complete | `feature/procurement-operations-phase-0` | TBD |
| **Phase 1** | Foundation (Contracts, DTOs, Exceptions) | ⏳ Not Started | - | - |
| **Phase 2** | Requisition Workflow | ⏳ Not Started | - | - |
| **Phase 3** | Purchase Order Workflow | ⏳ Not Started | - | - |
| **Phase 4** | Goods Receipt & GL Integration | ⏳ Not Started | - | - |
| **Phase 5** | Three-Way Matching | ⏳ Not Started | - | - |
| **Phase 6** | Payment Processing | ⏳ Not Started | - | - |
| **Phase 7** | Stateful Workflows (Sagas) | ⏳ Not Started | - | - |

**Status Legend:**
- ✅ Complete
- 🔄 In Progress
- ⏳ Not Started
- ❌ Blocked

---

## Phase 0: Add Domain Events to Atomic Packages

### Objective

Add domain event value objects to `Nexus\Procurement` and `Nexus\Payable` packages, enabling the orchestrator to react to business state changes.

### Tasks

#### 0.1 Procurement Domain Events

**Location:** `packages/Procurement/src/Events/`

| Task | File | Status |
|------|------|--------|
| Create `RequisitionCreatedEvent` | `RequisitionCreatedEvent.php` | ✅ |
| Create `RequisitionApprovedEvent` | `RequisitionApprovedEvent.php` | ✅ |
| Create `RequisitionRejectedEvent` | `RequisitionRejectedEvent.php` | ✅ |
| Create `PurchaseOrderCreatedEvent` | `PurchaseOrderCreatedEvent.php` | ✅ |
| Create `PurchaseOrderSentEvent` | `PurchaseOrderSentEvent.php` | ✅ |
| Create `PurchaseOrderAmendedEvent` | `PurchaseOrderAmendedEvent.php` | ✅ |
| Create `GoodsReceiptCreatedEvent` | `GoodsReceiptCreatedEvent.php` | ✅ |
| Create `GoodsReceiptCompletedEvent` | `GoodsReceiptCompletedEvent.php` | ✅ |

#### 0.2 Payable Domain Events

**Location:** `packages/Payable/src/Events/`

| Task | File | Status |
|------|------|--------|
| Create `VendorBillReceivedEvent` | `VendorBillReceivedEvent.php` | ✅ |
| Create `InvoiceMatchedEvent` | `InvoiceMatchedEvent.php` | ✅ |
| Create `InvoiceMatchFailedEvent` | `InvoiceMatchFailedEvent.php` | ✅ |
| Create `InvoiceApprovedForPaymentEvent` | `InvoiceApprovedForPaymentEvent.php` | ✅ |
| Create `PaymentScheduledEvent` | `PaymentScheduledEvent.php` | ✅ |
| Create `PaymentExecutedEvent` | `PaymentExecutedEvent.php` | ✅ |
| Create `PaymentFailedEvent` | `PaymentFailedEvent.php` | ✅ |

### Deliverables

- [x] 8 domain events in `Nexus\Procurement`
- [x] 7 domain events in `Nexus\Payable`
- [x] All events are pure PHP 8.3+ readonly classes
- [x] All events follow PSR-14 compatibility

### Estimated Effort

4-6 hours

---

## Phase 1: Foundation (Core Orchestrator Structure)

### Objective

Create the orchestrator package scaffolding with core contracts, DTOs, and exceptions.

### Tasks

#### 1.1 Package Initialization

| Task | Status |
|------|--------|
| Create `orchestrators/ProcurementOperations/` directory | ⏳ |
| Create `composer.json` | ⏳ |
| Create `README.md` | ⏳ |
| Create `LICENSE` (MIT) | ⏳ |
| Create mandatory documentation files (13 files) | ⏳ |

#### 1.2 Core Contracts

**Location:** `orchestrators/ProcurementOperations/src/Contracts/`

| Contract | Purpose | Status |
|----------|---------|--------|
| `RequisitionCoordinatorInterface` | Requisition workflow coordination | ⏳ |
| `PurchaseOrderCoordinatorInterface` | PO workflow coordination | ⏳ |
| `GoodsReceiptCoordinatorInterface` | GR workflow coordination | ⏳ |
| `InvoiceMatchingCoordinatorInterface` | 3-way matching coordination | ⏳ |
| `PaymentProcessingCoordinatorInterface` | Payment workflow coordination | ⏳ |
| `ThreeWayMatchingServiceInterface` | Matching service contract | ⏳ |
| `AccrualServiceInterface` | Accrual calculation contract | ⏳ |

#### 1.3 Core DTOs

**Location:** `orchestrators/ProcurementOperations/src/DTOs/`

| Category | DTOs | Status |
|----------|------|--------|
| Requests | `CreateRequisitionRequest`, `CreatePurchaseOrderRequest`, `RecordGoodsReceiptRequest`, `MatchInvoiceRequest`, `ProcessPaymentRequest` | ⏳ |
| Results | `RequisitionResult`, `PurchaseOrderResult`, `GoodsReceiptResult`, `MatchingResult`, `PaymentResult` | ⏳ |
| Contexts | `RequisitionContext`, `PurchaseOrderContext`, `GoodsReceiptContext`, `ThreeWayMatchContext`, `PaymentBatchContext` | ⏳ |

#### 1.4 Core Exceptions

**Location:** `orchestrators/ProcurementOperations/src/Exceptions/`

| Exception | Purpose | Status |
|-----------|---------|--------|
| `ProcurementOperationsException` | Base exception | ⏳ |
| `RequisitionException` | Requisition errors | ⏳ |
| `PurchaseOrderException` | PO errors | ⏳ |
| `GoodsReceiptException` | GR errors | ⏳ |
| `MatchingException` | Matching errors | ⏳ |
| `ThreeWayMatchFailedException` | Match failure | ⏳ |
| `ToleranceExceededException` | Tolerance exceeded | ⏳ |
| `DuplicatePaymentException` | Duplicate payment | ⏳ |
| `PaymentException` | Payment errors | ⏳ |

### Deliverables

- [ ] Complete package scaffolding
- [ ] 7 coordinator interfaces
- [ ] 15 DTOs (5 requests, 5 results, 5 contexts)
- [ ] 9 exception classes
- [ ] All mandatory documentation files

### Estimated Effort

4-6 hours

---

## Phase 2: Requisition Workflow

### Objective

Implement requisition creation, validation, and approval workflow coordination.

### Tasks

#### 2.1 DataProviders

| Component | Purpose | Status |
|-----------|---------|--------|
| `RequisitionContextProvider` | Aggregate requisition data from multiple packages | ⏳ |

#### 2.2 Rules

| Rule | Purpose | Status |
|------|---------|--------|
| `BudgetAvailableRule` | Check budget availability | ⏳ |
| `ApprovalLimitRule` | Validate approval limits | ⏳ |
| `PreferredVendorRule` | Check preferred vendor | ⏳ |
| `RequisitionRuleRegistry` | Rule composition | ⏳ |

#### 2.3 Coordinator

| Component | Purpose | Status |
|-----------|---------|--------|
| `RequisitionCoordinator` | Orchestrate requisition workflow | ⏳ |

#### 2.4 Listeners

| Listener | Reacts To | Status |
|----------|-----------|--------|
| `CommitBudgetOnRequisitionCreated` | `RequisitionCreatedEvent` | ⏳ |
| `CreatePOOnRequisitionApproved` | `RequisitionApprovedEvent` | ⏳ |

### Estimated Effort

8-10 hours

---

## Phase 3: Purchase Order Workflow

### Objective

Implement PO creation, vendor notification, and amendment handling.

### Tasks

#### 3.1 DataProviders

| Component | Purpose | Status |
|-----------|---------|--------|
| `PurchaseOrderContextProvider` | Aggregate PO data | ⏳ |

#### 3.2 Rules

| Rule | Purpose | Status |
|------|---------|--------|
| `VendorActiveRule` | Validate vendor status | ⏳ |
| `ContractValidRule` | Validate contract terms | ⏳ |
| `PriceVarianceRule` | Check price variance | ⏳ |
| `PurchaseOrderRuleRegistry` | Rule composition | ⏳ |

#### 3.3 Coordinator

| Component | Purpose | Status |
|-----------|---------|--------|
| `PurchaseOrderCoordinator` | Orchestrate PO workflow | ⏳ |

#### 3.4 Listeners

| Listener | Reacts To | Status |
|----------|-----------|--------|
| `NotifyVendorOnPOSent` | `PurchaseOrderSentEvent` | ⏳ |

### Estimated Effort

6-8 hours

---

## Phase 4: Goods Receipt & GL Integration

### Objective

Implement goods receipt recording, inventory updates, and GL accrual posting.

### Tasks

#### 4.1 DataProviders

| Component | Purpose | Status |
|-----------|---------|--------|
| `GoodsReceiptContextProvider` | Aggregate GR data | ⏳ |

#### 4.2 Rules

| Rule | Purpose | Status |
|------|---------|--------|
| `QuantityToleranceRule` | Validate quantity tolerance | ⏳ |
| `QualityCheckPassedRule` | Validate quality check | ⏳ |
| `ExpiryDateValidRule` | Validate lot expiry | ⏳ |
| `GoodsReceiptRuleRegistry` | Rule composition | ⏳ |

#### 4.3 Services

| Service | Purpose | Status |
|---------|---------|--------|
| `AccrualCalculationService` | Calculate GR-IR accrual | ⏳ |

#### 4.4 Coordinator

| Component | Purpose | Status |
|-----------|---------|--------|
| `GoodsReceiptCoordinator` | Orchestrate GR workflow | ⏳ |

#### 4.5 Listeners

| Listener | Reacts To | Status |
|----------|-----------|--------|
| `UpdateInventoryOnGoodsReceipt` | `GoodsReceiptCreatedEvent` | ⏳ |
| `PostAccrualOnGoodsReceipt` | `GoodsReceiptCreatedEvent` | ⏳ |
| `ReleaseBudgetCommitmentOnGoodsReceipt` | `GoodsReceiptCreatedEvent` | ⏳ |

### Estimated Effort

8-10 hours

---

## Phase 5: Three-Way Matching

### Objective

Implement PO-GR-Invoice matching with tolerance handling and variance workflows.

### Tasks

#### 5.1 DataProviders

| Component | Purpose | Status |
|-----------|---------|--------|
| `ThreeWayMatchContextProvider` | Aggregate PO, GR, Invoice data | ⏳ |

#### 5.2 Rules

| Rule | Purpose | Status |
|------|---------|--------|
| `PriceMatchRule` | Validate price match | ⏳ |
| `QuantityMatchRule` | Validate quantity match | ⏳ |
| `ToleranceThresholdRule` | Check tolerance threshold | ⏳ |
| `InvoiceMatchingRuleRegistry` | Rule composition | ⏳ |

#### 5.3 Services

| Service | Purpose | Status |
|---------|---------|--------|
| `ThreeWayMatchingService` | Execute 3-way matching logic | ⏳ |

#### 5.4 Coordinator

| Component | Purpose | Status |
|-----------|---------|--------|
| `InvoiceMatchingCoordinator` | Orchestrate matching workflow | ⏳ |

#### 5.5 Listeners

| Listener | Reacts To | Status |
|----------|-----------|--------|
| `TriggerMatchingOnInvoiceReceived` | `VendorBillReceivedEvent` | ⏳ |
| `ReverseAccrualOnInvoiceMatched` | `InvoiceMatchedEvent` | ⏳ |
| `NotifyApproversOnVarianceDetected` | `InvoiceMatchFailedEvent` | ⏳ |

### Estimated Effort

10-12 hours

---

## Phase 6: Payment Processing

### Objective

Implement payment scheduling, batch processing, and vendor ledger updates.

### Tasks

#### 6.1 DataProviders

| Component | Purpose | Status |
|-----------|---------|--------|
| `PaymentBatchContextProvider` | Aggregate payment batch data | ⏳ |
| `VendorSpendContextProvider` | Aggregate vendor spend data | ⏳ |

#### 6.2 Rules

| Rule | Purpose | Status |
|------|---------|--------|
| `PaymentTermsMetRule` | Validate payment terms | ⏳ |
| `DuplicatePaymentRule` | Check duplicate payments | ⏳ |
| `BankDetailsVerifiedRule` | Validate bank details | ⏳ |
| `PaymentRuleRegistry` | Rule composition | ⏳ |

#### 6.3 Services

| Service | Purpose | Status |
|---------|---------|--------|
| `PaymentBatchBuilder` | Build payment batches | ⏳ |

#### 6.4 Coordinator

| Component | Purpose | Status |
|-----------|---------|--------|
| `PaymentProcessingCoordinator` | Orchestrate payment workflow | ⏳ |

#### 6.5 Listeners

| Listener | Reacts To | Status |
|----------|-----------|--------|
| `SchedulePaymentOnInvoiceApproved` | `InvoiceApprovedForPaymentEvent` | ⏳ |
| `UpdateVendorLedgerOnPayment` | `PaymentExecutedEvent` | ⏳ |
| `NotifyVendorOnPaymentExecuted` | `PaymentExecutedEvent` | ⏳ |

### Estimated Effort

8-10 hours

---

## Phase 7: Stateful Workflows (Sagas)

### Objective

Implement long-running stateful workflows with compensation logic.

### Tasks

#### 7.1 Requisition Approval Workflow

| Component | Purpose | Status |
|-----------|---------|--------|
| `RequisitionApprovalWorkflow` | Workflow definition | ⏳ |
| States: `DraftState`, `PendingApprovalState`, `ApprovedState`, `RejectedState` | State definitions | ⏳ |
| Steps: `ValidateRequisitionStep`, `RouteForApprovalStep`, `ConvertToPOStep` | Workflow steps | ⏳ |

#### 7.2 Invoice to Payment Workflow

| Component | Purpose | Status |
|-----------|---------|--------|
| `InvoiceToPaymentWorkflow` | Workflow definition | ⏳ |
| States: `PendingMatchState`, `MatchedState`, `ApprovedForPaymentState`, `ScheduledState`, `PaidState` | State definitions | ⏳ |
| Steps: `PerformThreeWayMatchStep`, `ApproveForPaymentStep`, `SchedulePaymentStep`, `ExecutePaymentStep` | Workflow steps | ⏳ |

### Estimated Effort

10-12 hours

---

## Total Implementation Summary

| Phase | Estimated Hours |
|-------|-----------------|
| Phase 0 | 4-6 hours |
| Phase 1 | 4-6 hours |
| Phase 2 | 8-10 hours |
| Phase 3 | 6-8 hours |
| Phase 4 | 8-10 hours |
| Phase 5 | 10-12 hours |
| Phase 6 | 8-10 hours |
| Phase 7 | 10-12 hours |
| **Total** | **58-74 hours (~8-10 days)** |

---

## Dependencies

### Required Atomic Packages (All Ready ✅)

| Package | Status | Key Interfaces |
|---------|--------|----------------|
| `Nexus\Procurement` | ✅ Complete | `ProcurementManagerInterface`, `RequisitionRepositoryInterface`, `PurchaseOrderRepositoryInterface`, `GoodsReceiptRepositoryInterface` |
| `Nexus\Payable` | ✅ Complete | `PayableManagerInterface`, `ThreeWayMatcherInterface`, `PaymentSchedulerInterface`, `VendorBillRepositoryInterface` |
| `Nexus\Inventory` | ✅ Complete | `StockManagerInterface`, `LotManagerInterface` |
| `Nexus\JournalEntry` | ✅ Complete | `JournalEntryManagerInterface` |
| `Nexus\Budget` | ✅ Complete | `BudgetManagerInterface` |
| `Nexus\Workflow` | ✅ Complete | `WorkflowManagerInterface`, `TaskManagerInterface` |
| `Nexus\Party` | ✅ Complete | `PartyManagerInterface` |
| `Nexus\Period` | ✅ Complete | `PeriodValidatorInterface` |
| `Nexus\Currency` | ✅ Complete | `CurrencyManagerInterface` |
| `Nexus\Notifier` | ✅ Complete | `NotificationManagerInterface` |
| `Nexus\AuditLogger` | ✅ Complete | `AuditLogManagerInterface` |
| `Nexus\Setting` | ✅ Complete | `SettingsManagerInterface` |

### Required Enhancements (Phase 0)

| Package | Enhancement | Status |
|---------|-------------|--------|
| `Nexus\Procurement` | Add 8 domain events | 🔄 In Progress |
| `Nexus\Payable` | Add 7 domain events | 🔄 In Progress |

---

## Progress Log

### December 6, 2025

- Created implementation plan document
- Identified Phase 0 requirements (domain events)
- Started Phase 0 implementation on branch `feature/procurement-operations-phase-0`

---

## Notes

- All domain events are pure PHP 8.3+ readonly classes
- Events are value objects containing only immutable data
- Atomic packages dispatch events but are unaware of listeners
- Orchestrator defines listeners that react to events and coordinate cross-package operations
- Follow Advanced Orchestrator Pattern v1.1 (reference: `AccountingOperations`, `HumanResourceOperations`)

---

**Last Updated:** December 6, 2025  
**Maintained By:** Nexus Architecture Team
