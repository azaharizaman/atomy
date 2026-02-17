# Task: Implement ProcurementOperations Gaps

## 📋 Objectives
- [ ] Implement `RequisitionCoordinator` to close structural gaps.
- [ ] Implement `PurchaseOrderCoordinator` to close structural gaps.
- [ ] Implement `SourceToContractCoordinator` and RFQ foundation.
- [ ] Implement `ReturnToVendorCoordinator` (RTV).
- [ ] Implement `ServiceReceiptCoordinator`.
- [ ] Implement `PaymentRunCoordinator` and `PaymentVoidCoordinator`.

## 🕵️ Analysis & Discovery
- **Impacted Packages**: `orchestrators/ProcurementOperations`
- **Existing Rules**: `BudgetAvailabilityRule`, `SpendLimitRule`, `VendorActiveRule`.
- **Key Files**: 
    - `RequisitionCoordinatorInterface.php`
    - `PurchaseOrderCoordinatorInterface.php`
    - `GAP_ANALYSIS_PROCUREMENT_OPERATIONS.md`

## 🏗️ Implementation Plan
1. **Phase 1: Entry Points** (Now)
   - `RequisitionCoordinator`: Link `CreateRequisitionRequest` to `ProcureToPayWorkflow` or individual steps.
   - `PurchaseOrderCoordinator`: Link to `CreatePurchaseOrderStep`.
2. **Phase 2: Receipts & Returns**
   - `ReturnToVendorCoordinator`: Reverse fulfillment logic.
   - `ServiceReceiptCoordinator`: Non-inventory acceptance.
3. **Phase 3: Sourcing & RFQ**
   - Define `SourceToContractCoordinatorInterface`.
   - Implement `QuoteComparisonService`.
4. **Phase 4: Payment Extensions**
   - `PaymentRunCoordinator`: Batch processing.
   - `PaymentVoidCoordinator`: Reversals.

## 🧪 Verification Strategy
- [ ] Unit Tests for new Coordinators.
- [ ] Integration Verification via dummy requests.
- [ ] Documentation Update in `IMPLEMENTATION_SUMMARY.md`.

## 📝 Session Progress
- [2026-02-17 22:35] Started gap implementation task. Branched out.
