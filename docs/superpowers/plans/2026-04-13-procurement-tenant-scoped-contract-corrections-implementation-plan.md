# Implementation Plan: ProcurementOperations Tenant-Scoped Contract Corrections

## Scope

Implement contract and orchestrator corrections required to resolve blocked findings in ProcurementOperations:

- Missing Layer 1 contracts (`GoodsReceiptQueryInterface`, `GoodsReceiptPersistInterface`, `VendorBillQueryInterface`).
- Tenant-scoped vendor bill and goods receipt read paths in Layer 2.
- Failing unit tests that currently cannot compile/execute due to contract drift.

## Preconditions

1. Work in this order: Layer 1 contracts -> Layer 2 orchestrator updates -> tests.
2. Do not introduce framework dependencies into `packages/*` or `orchestrators/*`.
3. Keep backward compatibility where practical by extending existing repository interfaces.
4. See `docs/project/ARCHITECTURE.md` for architectural rules.
5. See `docs/project/NEXUS_PACKAGES_REFERENCE.md` for package reference.

## Phase 1: Layer 1 Contract Corrections

### 1.1 Procurement contracts

Files:
- `packages/Procurement/src/Contracts/GoodsReceiptQueryInterface.php` (new)
- `packages/Procurement/src/Contracts/GoodsReceiptPersistInterface.php` (new)
- `packages/Procurement/src/Contracts/GoodsReceiptRepositoryInterface.php` (update)

Tasks:
1. Create `GoodsReceiptQueryInterface` with tenant-scoped read signatures used by Layer 2.
2. Create `GoodsReceiptPersistInterface` with mutation signatures actually used by Layer 2, or explicitly trim unsupported methods from Layer 2 if not part of package truth.
3. Update `GoodsReceiptRepositoryInterface` to extend query and persist interfaces.

Verification:
- `composer -d packages/Procurement install`
- `./vendor/bin/phpunit` in `packages/Procurement`
- Check adapter compatibility: `composer -d adapters/Laravel install` then `./vendor/bin/phpunit -c adapters/Laravel/phpunit.xml`

### 1.2 Payable contracts

Files:
- `packages/Payable/src/Contracts/VendorBillQueryInterface.php` (new)
- `packages/Payable/src/Contracts/VendorBillRepositoryInterface.php` (update)

Tasks:
1. Add tenant-scoped `findByTenantAndId(string $tenantId, string $id)`.
2. Move read methods into `VendorBillQueryInterface`.
3. Make repository interface extend query interface and keep persist methods.
4. If retaining `findById(string $id)`, mark deprecated and avoid in Layer 2.

Verification:
- `composer -d packages/Payable install`
- `./vendor/bin/phpunit` in `packages/Payable` (or targeted suites if needed).

## Phase 2: Layer 2 ProcurementOperations Updates

Files:
- `orchestrators/ProcurementOperations/src/DataProviders/ThreeWayMatchDataProvider.php`
- `orchestrators/ProcurementOperations/src/DataProviders/GoodsReceiptContextProvider.php`
- `orchestrators/ProcurementOperations/src/Coordinators/ReturnToVendorCoordinator.php`
- Any other compile-failing imports currently referencing missing contracts.

Tasks:
1. Update imports/type hints to newly created Layer 1 interfaces.
2. Change three-way match invoice fetch to tenant-scoped query:
   - `vendorBillQuery->findByTenantAndId($tenantId, $vendorBillId)`.
3. Replace ID-only goods receipt reads with tenant-scoped reads where tenant is available.
4. Keep behavior unchanged aside from tenant hardening and contract alignment.

Verification:
- `composer -d orchestrators/ProcurementOperations install`
- `./vendor/bin/phpunit tests/Unit/DataProviders/ThreeWayMatchDataProviderTest.php`
- `./vendor/bin/phpunit tests/Unit/Coordinators/GoodsReceiptCoordinatorTest.php`
- `./vendor/bin/phpunit tests/Unit/Coordinators/ServiceReceiptCoordinatorTest.php`

## Phase 3: Test and Regression Hardening

Files:
- `orchestrators/ProcurementOperations/tests/Unit/Coordinators/GoodsReceiptCoordinatorTest.php`
- `orchestrators/ProcurementOperations/tests/Unit/DataProviders/ThreeWayMatchDataProviderTest.php`
- Additional tests as needed for context provider and RTV coordinator tenant scoping.

Tasks:
1. Ensure tests assert tenant parameter is passed on all tenant-owned entity reads.
2. Remove/replace impossible mocks tied to non-existent interfaces after contracts are introduced.
3. Add one regression test per corrected path:
   - vendor bill tenant lookup,
   - goods receipt tenant lookup,
   - PO tenant lookup.

Verification:
- Run targeted tests above.
- Optional: run full ProcurementOperations unit suite if runtime permits.

## Phase 4: Documentation and Change Closure

Files:
- `orchestrators/ProcurementOperations/IMPLEMENTATION_SUMMARY.md`
- `packages/Procurement/IMPLEMENTATION_SUMMARY.md` (if contract changes are substantial)
- `packages/Payable/IMPLEMENTATION_SUMMARY.md` (if contract changes are substantial)

Tasks:
1. Document exact contract additions/changes and migration notes.
2. Explicitly record tenant-scoped lookup hardening and affected methods.
3. Note any intentionally deferred follow-ups.

Verification:
- `git diff --name-only` confirms summaries updated.

## Execution Checklist

- [ ] Add Procurement query/persist interfaces.
- [ ] Add Payable vendor bill query interface with tenant-scoped lookup.
- [ ] Update repository interfaces to extend query/persist contracts.
- [ ] Update ProcurementOperations imports/type hints.
- [ ] Replace ID-only vendor bill lookup in three-way match.
- [ ] Replace ID-only goods receipt lookups where tenant context is present.
- [ ] Fix and pass targeted coordinator/data-provider tests.
- [ ] Verify adapter implementations against updated interfaces (Layer 3).
- [ ] Update implementation summaries with concrete method-level details.

## Risks to Watch During Implementation

1. Adapter implementations may fail interface compatibility.
- Resolve by staged signature updates and minimal adapter shims.

2. Hidden ID-only lookup call sites in ProcurementOperations.
- Use `rg "findById\(" orchestrators/ProcurementOperations/src` and review each for tenant scope requirements.

3. Over-expanding persist interfaces with methods not owned by Procurement package.
- Keep contracts aligned with package truth; do not encode Layer 2 assumptions without backing implementation.

## Definition of Done

1. ProcurementOperations compiles without missing-contract errors.
2. Tenant-scoped lookups are used for vendor bill and goods receipt reads in corrected paths.
3. Targeted tests for goods receipt coordinator and three-way match pass.
4. Implementation summaries are updated documenting contract and orchestrator corrections:
    - orchestrators/ProcurementOperations/IMPLEMENTATION_SUMMARY.md
    - packages/Procurement/IMPLEMENTATION_SUMMARY.md
    - packages/Payable/IMPLEMENTATION_SUMMARY.md
