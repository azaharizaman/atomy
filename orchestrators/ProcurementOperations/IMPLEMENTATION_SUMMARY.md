# ProcurementOperations Implementation Summary

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
