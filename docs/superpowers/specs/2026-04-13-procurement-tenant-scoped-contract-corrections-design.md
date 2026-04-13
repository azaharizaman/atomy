# ProcurementOperations Tenant-Scoped Contract Corrections Design

## Context

A review of `orchestrators/ProcurementOperations` identified that some fixes were blocked because Layer 2 depends on missing or insufficient Layer 1 interfaces:

- `Nexus\Procurement\Contracts\GoodsReceiptQueryInterface` does not exist.
- `Nexus\Procurement\Contracts\GoodsReceiptPersistInterface` does not exist.
- `Nexus\Payable\Contracts\VendorBillQueryInterface` does not exist.
- `Nexus\Payable\Contracts\VendorBillRepositoryInterface::findById(string $id)` is not tenant-scoped.

This prevents tenant-safe lookup corrections in `ThreeWayMatchDataProvider` and blocks valid test compilation for `GoodsReceiptCoordinator` and related data providers/coordinators.

## Findings Analysis

### 1) `IMPLEMENTATION_SUMMARY.md` formatting and wording findings

Status: Fixed.

Assessment:
- No architecture impact.
- Keep as-is.

### 2) `GoodsReceiptCoordinatorTest` partially fixed but failing

Status: Blocked by contract availability.

Root cause:
- Coordinator/data provider constructor type hints reference Layer 1 interfaces not present in `packages/Procurement/src/Contracts`.
- This is not a test-only issue; it is a contract drift issue between Layer 1 and Layer 2.

Architecture implication:
- Layer 2 must depend on explicit Layer 1 interfaces. Missing contracts violate interface-first composition.

### 3) `ThreeWayMatchDataProvider` tenant scoping cannot be completed

Status: Blocked by Payable Layer 1 query contract.

Root cause:
- `VendorBillRepositoryInterface::findById(string $id)` cannot enforce tenant scoping.
- `ThreeWayMatchDataProvider` currently receives `tenantId` but cannot use it for vendor bill lookup.

Security implication:
- ID-only lookup increases cross-tenant read risk.

## Problem Statement

`ProcurementOperations` currently has a Layer 2 contract dependency mismatch and incomplete tenant-scoped query capability in upstream Layer 1 packages. Until Layer 1 contracts are corrected, several Layer 2 fixes cannot be implemented safely or compiled consistently.

## Goals

1. Introduce missing Layer 1 query/persist contracts in Procurement to satisfy Layer 2 dependencies.
2. Introduce tenant-scoped bill query contract in Payable for Layer 2 usage.
3. Update `ProcurementOperations` to consume tenant-scoped query APIs for three-way matching and goods receipt context paths.
4. Preserve backward compatibility for existing Layer 1 consumers where possible.
5. Add regression tests proving tenant-scoped call signatures are used.

## Non-Goals

- Refactoring unrelated orchestrators/packages not touched by ProcurementOperations findings.
- Framework/adapters migration beyond interface wiring required for compilation/tests.
- Business-rule changes in matching tolerance or posting logic.

## Architecture Alignment

This design follows the monorepo standards:

- Three-layer rule: Layer 1 defines contracts; Layer 2 orchestrates using contracts only.
- Interface-first: introduce contracts before changing orchestrator behavior.
- CQRS preference: separate query and persist responsibilities where introduced.
- Multi-tenancy hardening: tenant context must be included in lookup methods that retrieve tenant-owned resources.

## Proposed Contract Changes

### A) `packages/Procurement`

Add new contracts in `packages/Procurement/src/Contracts/`:

- `GoodsReceiptQueryInterface`
  - `findByTenantAndId(string $tenantId, string $id): ?GoodsReceiptNoteInterface`
  - `findByNumber(string $tenantId, string $grnNumber): ?GoodsReceiptNoteInterface`
  - `findByPurchaseOrder(string $purchaseOrderId, string $tenantId): array`
  - `findByTenantId(string $tenantId, array $filters): array`
  - `findLineByReference(string $tenantId, string $lineReference): ?GoodsReceiptLineInterface`

- `GoodsReceiptPersistInterface`
  - `create(string $tenantId, string $purchaseOrderId, string $receiverId, array $data): GoodsReceiptNoteInterface|string`
  - `authorizePayment(string $tenantId, string $grnId, string $authorizerId): GoodsReceiptNoteInterface|void`
  - `save(GoodsReceiptNoteInterface $grn): void`
  - Any existing mutation methods already assumed by Layer 2 (`reverse`, `createReturn`, `updateReturnStatus`) should be explicitly dispositioned:
    - either formalized in this interface, or
    - removed from Layer 2 contract usage if not supported by Procurement package.

Update `GoodsReceiptRepositoryInterface` to extend both interfaces for compatibility:
- `interface GoodsReceiptRepositoryInterface extends GoodsReceiptQueryInterface, GoodsReceiptPersistInterface`

### B) `packages/Payable`

Add `VendorBillQueryInterface` in `packages/Payable/src/Contracts/`:

- `findByTenantAndId(string $tenantId, string $id): ?VendorBillInterface`
- `findByBillNumber(string $tenantId, string $billNumber): ?VendorBillInterface`
- `getByVendor(string $vendorId, array $filters = []): array`
- `getByStatus(string $tenantId, string $status): array`
- `getPendingMatching(string $tenantId): array`
- `getReadyForPosting(string $tenantId): array`
- `getForAgingReport(string $tenantId, \DateTimeInterface $asOfDate): array`

`VendorBillRepositoryInterface` should extend `VendorBillQueryInterface` and keep persist methods:
- `save(VendorBillInterface $bill): VendorBillInterface`
- `delete(string $id): bool`

Deprecation:
- Keep `findById(string $id)` temporarily if external consumers require it, but mark deprecated and route Layer 2 to tenant-scoped `findByTenantAndId`.

## Proposed Layer 2 Changes

### `orchestrators/ProcurementOperations`

1. Replace imports/usages of non-existent contracts with the newly added Layer 1 contracts.
2. `ThreeWayMatchDataProvider::buildContext(...)`:
   - use `vendorBillQuery->findByTenantAndId($tenantId, $vendorBillId)`.
3. `GoodsReceiptContextProvider` and `ReturnToVendorCoordinator`:
   - replace ID-only goods receipt reads with tenant-scoped query methods (`findByTenantAndId`).
4. Ensure no Layer 2 code path performs tenant-owned resource retrieval without tenant-scoped query capability.

## Data/Behavior Compatibility

- Compatibility strategy is additive-first:
  - Add new query/persist interfaces.
  - Have existing repository interfaces extend them.
  - Update orchestrators to depend on new query/persist contracts.
- Existing adapters implementing repository interfaces remain source-compatible if signatures match.
- Where signatures differ (e.g. `create` return type assumptions), align Layer 2 to the package contract instead of ad-hoc array-based mutation assumptions.

## Testing Strategy

### Layer 1 (packages)

- Contract compilation checks for newly added interfaces.
- Unit tests for repository adapters implementing new tenant-scoped methods.

### Layer 2 (orchestrators)

- Fix and run:
  - `tests/Unit/Coordinators/GoodsReceiptCoordinatorTest.php`
  - `tests/Unit/DataProviders/ThreeWayMatchDataProviderTest.php`
- Add/adjust tests to assert tenant is passed into:
  - goods receipt lookups,
  - vendor bill lookups,
  - PO lookups.

### Regression focus

- No fallback to ID-only lookup in three-way matching and goods-receipt context flows.

## Risks and Mitigations

1. Contract ripple across adapters.
- Mitigation: interface extension strategy and staged migration.

2. Signature mismatch for persist methods already assumed by Layer 2.
- Mitigation: explicit contract reconciliation before implementation; avoid implicit methods.

3. Tenant-scoped method adoption gaps.
- Mitigation: grep-based callsite sweep for `findById(` on tenant-owned entities in ProcurementOperations.

## Acceptance Criteria

1. Missing interfaces exist in Layer 1 (`Procurement`, `Payable`) and compile.
2. ProcurementOperations no longer imports non-existent interfaces.
3. `ThreeWayMatchDataProvider` uses tenant-scoped vendor bill lookup.
4. Goods receipt query call paths in orchestrator/data provider are tenant-scoped.
5. Target tests pass in ProcurementOperations package.
6. `orchestrators/ProcurementOperations/IMPLEMENTATION_SUMMARY.md` is updated with concrete contract and tenant-scoping corrections.
