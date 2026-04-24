# ProcurementOperations Implementation Summary

## 2026-04-24 - Provider-backed Vendor Recommendation Runtime

- Refactored `VendorRecommendationCoordinatorInterface` to return the Layer 1 `Nexus\ProcurementML\ValueObjects\VendorRecommendationResult` contract instead of the older orchestrator-local DTO.
- `VendorRecommendationCoordinator` now treats provider-backed inference as the alpha default when the injected provider adapter is available: it calls the provider, rejects malformed payloads or invented/ineligible vendors, preserves deterministic eligibility boundaries, and returns an explicit `unavailable` result when provider invocation fails or provider output cannot be trusted.
- Provider explanations now flow separately from deterministic reason sets, with per-candidate explanation fallback order of candidate provider explanation, candidate reason summary, then top-level provider explanation, while deterministic reasons remain anchored to the scorer output.
- `NullVendorRecommendationLlm` remains as the honest continuity adapter for environments where no provider client is bound, but the coordinator no longer represents that path as a silent synthetic success.

### Security/architecture impact

- Tightens the Layer 2 boundary around a Layer 1 result contract: callers now consume the `Nexus\ProcurementML` recommendation result directly instead of an orchestrator-local result DTO.
- Provider-backed inference remains advisory only. The coordinator never lets the provider invent vendors, widen eligibility, or bypass deterministic approved-vendor gating.
- Malformed provider payloads and provider transport failures now resolve to an explicit unavailable result instead of a partial synthetic success path, which keeps downstream API/UI layers honest about degraded runtime state.
- Explanation flow is explicit: deterministic reasons remain owned by the scorer, while provider explanations are layered on top without mutating deterministic eligibility evidence.

- Verification:

  ```bash
  ./vendor/bin/phpunit \
    orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php \
    packages/ProcurementML/tests/Unit/VendorRecommendationContractsTest.php
  ```

  Result: PASS (5 tests, 27 assertions).

## 2026-04-22 - Vendor Recommendation Engine

- Added framework-agnostic vendor recommendation DTOs under `DTOs/VendorRecommendation` for requisition context, candidate records, scored candidates, and recommendation results.
- Added `DeterministicVendorScorer`, which enforces approved-only eligibility before scoring and produces explainable fit scores from category overlap, capability/narrative signals, geography, spend band, historical activity, and preferred-vendor metadata.
- Added `VendorRecommendationCoordinatorInterface` plus `VendorRecommendationCoordinator` and `VendorRecommendationLlmInterface`; LLM enrichment is optional and bounded to +/- 10 score points on the 0-100 score domain, so a baseline score of 72 can become at most 82 or 62. The coordinator clamps adjusted scores to [0,100], so 5 minus 10 becomes 0 and 95 plus 10 becomes 100. The coordinator enforces that cap and cannot introduce unknown or ineligible vendors.
- Added `NullVendorRecommendationLlm` as the alpha default so deterministic recommendations work without an LLM provider.

### Security/architecture impact

- Recommendation DTOs carry tenant context (`tenantId`) and candidate records are supplied by the tenant-scoped upstream API adapter before scoring.
- The deterministic scorer excludes non-approved vendors before scoring; tenant isolation remains the adapter/query responsibility, while the coordinator rejects LLM enrichment for vendors outside the deterministic eligible set.
- The LLM contract receives scoped tenant-local context only, filtered to tenant-specific data and separate from numerical score bounds, and cannot mutate eligibility, vendor master data, or persisted selection.
- Follow-up: if future providers use a non-0-100 score range, adjust `VendorRecommendationCoordinator::MAX_LLM_SCORE_DELTA` proportionally and document the new range.
- Verification:

  ```bash
  ./vendor/bin/phpunit \
    orchestrators/ProcurementOperations/tests/Unit/Services/DeterministicVendorScorerTest.php \
    orchestrators/ProcurementOperations/tests/Unit/Coordinators/VendorRecommendationCoordinatorTest.php
  ```

  Result: PASS (9 tests, 20 assertions).

## 2026-04-13 - Tenant-scoped contract corrections for goods receipt and vendor bills

### Problem addressed

- Layer 1 contracts were missing: `GoodsReceiptQueryInterface`, `GoodsReceiptPersistInterface`, `VendorBillQueryInterface`
- Layer 2 orchestrator imports referenced non-existent interfaces, causing compilation failures
- Tenant-scoped lookup not enforced in three-way matching and goods receipt context

### Changes implemented

#### Layer 1 Contract Additions

- **Procurement package**:
  - `GoodsReceiptQueryInterface` - exists with tenant-scoped methods: `findByTenantAndId`, `findByNumber`, `findLineByReference`, `findByPurchaseOrder`, `findByTenantId`, `generateNextNumber`
  - `GoodsReceiptPersistInterface` - exists with methods: `create`, `authorizePayment`, `save`, `createReturn`, `updateReturnStatus`
  - `GoodsReceiptRepositoryInterface` - updated to extend both query and persist interfaces

- **Payable package**:
  - `VendorBillQueryInterface` - exists with tenant-scoped methods: `findByTenantAndId`, `findByBillNumber`, `getByVendor`, `getByStatus`, `getPendingMatching`, `getReadyForPosting`, `getForAgingReport`
  - `VendorBillRepositoryInterface` - updated to extend `VendorBillQueryInterface`

- **CashManagement package**:
  - Added `BankAccountQueryInterface` with tenant-scoped methods

- **Warehouse package**:
  - Added `WarehouseQueryInterface` with tenant-scoped methods: `findByTenantAndId`, `findByCode`, `findByTenant`

#### Layer 2 Orchestrator Updates

- All data providers and coordinators now use tenant-scoped query methods:
  - `ThreeWayMatchDataProvider::buildContext` uses `vendorBillQuery->findByTenantAndId($tenantId, $vendorBillId)`
  - `GoodsReceiptContextProvider::getContext` uses `goodsReceiptQuery->findByTenantAndId($tenantId, $goodsReceiptId)`
  - `ReturnToVendorCoordinator::initiateReturn` uses `grQuery->findByTenantAndId($request->tenantId, $request->goodsReceiptId)`

### Tests

- `ThreeWayMatchDataProviderTest::buildContextUsesTenantScopedLookups` passes
- Test suite structure in place; some pre-existing test issues require separate fix

### Security/architecture impact

- Completes tenant-scoped contract chain from Layer 1 through Layer 2
- Eliminates ID-only lookups for vendor bills and goods receipts in matching/context flows
- Three-layer architecture enforced: orchestrators define contracts, adapters implement them using Layer 1 packages

**Note:** Per three-layer architecture (see ARCHITECTURE.md), orchestrators should own their interfaces and use adapters to consume Layer 1 packages. This implementation uses Layer 1 query interfaces directly; a follow-up should introduce orchestrator-owned interfaces with adapter implementations for proper layer separation.

## 2026-04-11 - Tenant-scoped purchase order lookup hardening

### Problem addressed

- Multiple Layer 2 procurement workflows called `PurchaseOrderQueryInterface::findById` without `tenantId`, despite a tenant-scoped contract.
- This created contract drift and increased cross-tenant read risk.

### Changes implemented

- Updated all high-impact procurement orchestration call sites that already carry tenant context:
  - `src/Coordinators/GoodsReceiptCoordinator.php`
  - `src/Coordinators/PurchaseOrderCoordinator.php`
  - `src/Coordinators/ServiceReceiptCoordinator.php`
  - `src/DataProviders/ThreeWayMatchDataProvider.php`
- Added regression tests to lock tenant-scoped lookup behavior:
  - `tests/Unit/Coordinators/PurchaseOrderCoordinatorTest.php`
  - `tests/Unit/Coordinators/GoodsReceiptCoordinatorTest.php`
  - `tests/Unit/Coordinators/ServiceReceiptCoordinatorTest.php`
  - `tests/Unit/DataProviders/ThreeWayMatchDataProviderTest.php`

### Security/architecture impact

- Aligns Layer 2 behavior with tenant-scoped Layer 1 query contracts.
- Reduces accidental cross-tenant data access from ID-only lookups.
- Adds tests to prevent regression to one-argument `findById` calls.
- Fixes bug in purchase-order status write: include tenant context when marking a PO as fully received to prevent cross-tenant writes.

### Remaining follow-up

- `SODComplianceDataProvider` still uses ID-only PO reads because its current API does not carry tenant context. A follow-up should thread tenant context through SOD validation APIs and update those queries to tenant-scoped reads.
- Specifically, SODComplianceDataProvider's methods getUserRoles, getRequisitionRequestorId, getPOCreatorId, getPOApproverId, and getGoodsReceiverId currently call userQuery->findById, requisitionQuery->findById, and poQuery->findById without tenant filtering - must convert all these ID-only reads to tenant-scoped queries (user, requisition, and PO reads).
