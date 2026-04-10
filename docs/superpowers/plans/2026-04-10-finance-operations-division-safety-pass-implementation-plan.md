# FinanceOperations Division-Safety Pass Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden `FinanceOperations` arithmetic paths so all denominator-based calculations fail safely with domain exceptions instead of runtime division errors.

**Architecture:** Keep changes scoped to two services (`CostAllocationService`, `DepreciationRunService`) plus their unit tests. Add service-local denominator guards and map invalid arithmetic states to existing domain exceptions. Preserve all valid-path behavior.

**Tech Stack:** PHP 8.3, PHPUnit 11, BCMath helpers, Nexus orchestrator exception contracts.

---

## File Map
- Modify: `orchestrators/FinanceOperations/src/Services/CostAllocationService.php`
- Modify: `orchestrators/FinanceOperations/src/Services/DepreciationRunService.php`
- Modify: `orchestrators/FinanceOperations/tests/Unit/Services/CostAllocationServiceTest.php`
- Modify: `orchestrators/FinanceOperations/tests/Unit/Services/DepreciationRunServiceTest.php`
- Modify: `orchestrators/FinanceOperations/IMPLEMENTATION_SUMMARY.md`

### Task 1: Harden CostAllocation denominator paths

**Files:**
- Modify: `orchestrators/FinanceOperations/src/Services/CostAllocationService.php`
- Test: `orchestrators/FinanceOperations/tests/Unit/Services/CostAllocationServiceTest.php`

- [ ] **Step 1: Write failing tests for invalid denominators and method mapping**

```php
public function testAllocateWithProportionalMethodThrowsWhenTotalWeightIsZero(): void
{
    $request = new CostAllocationRequest(
        tenantId: 'tenant-001',
        periodId: '2026-01',
        sourceCostPoolId: 'pool-001',
        targetCostCenterIds: ['cc-001', 'cc-002'],
        allocationMethod: 'proportional',
        options: ['weights' => [0, 0]],
    );

    $this->expectException(CostAllocationException::class);
    $this->service->allocate($request);
}

public function testAllocateWithUnknownMethodThrowsDomainException(): void
{
    $request = new CostAllocationRequest(
        tenantId: 'tenant-001',
        periodId: '2026-01',
        sourceCostPoolId: 'pool-001',
        targetCostCenterIds: ['cc-001'],
        allocationMethod: 'unknown',
    );

    $this->expectException(CostAllocationException::class);
    $this->service->allocate($request);
}
```

- [ ] **Step 2: Run focused test file and confirm new tests fail**

Run: `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/CostAllocationServiceTest.php`
Expected: FAIL on new tests (current behavior allows runtime/generic exception path).

- [ ] **Step 3: Implement denominator guards in service**

```php
// Insert into existing allocate() method - relies on $count, $requestTenantId, $requestSourcePoolId
case 'proportional':
    $weights = $options['weights'] ?? array_fill(0, $count, 1);
    $totalWeight = (float) array_sum($weights);

    if ($totalWeight <= 0.0) {
        throw CostAllocationException::allocationFailed(
            $requestTenantId,
            $requestSourcePoolId,
            'Invalid allocation weights: total weight must be greater than zero'
        );
    }
```

```php
// Insert into existing allocate() method - relies on $method, $requestTenantId, $requestSourcePoolId
default:
    throw CostAllocationException::allocationFailed(
        $requestTenantId,
        $requestSourcePoolId,
        sprintf('Unknown allocation method: %s', $method)
    );
```

Implementation note:
- Prefer passing `tenantId` and `sourceCostPoolId` into `calculateAllocations(...)` so inline domain throws preserve context.
- Keep existing output shape and rounding unchanged.

- [ ] **Step 4: Re-run focused tests for cost allocation service**

Run: `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/CostAllocationServiceTest.php`
Expected: PASS for new and existing tests in this file.

- [ ] **Step 5: Commit Task 1 changes**

```bash
git add orchestrators/FinanceOperations/src/Services/CostAllocationService.php \
  orchestrators/FinanceOperations/tests/Unit/Services/CostAllocationServiceTest.php
git commit -m "hardening: guard cost allocation division denominators"
```

### Task 2: Harden Depreciation denominator paths

**Files:**
- Modify: `orchestrators/FinanceOperations/src/Services/DepreciationRunService.php`
- Test: `orchestrators/FinanceOperations/tests/Unit/Services/DepreciationRunServiceTest.php`

- [ ] **Step 1: Write failing depreciation denominator tests**

```php
public function testExecuteRunFailsWhenUsefulLifeMonthsIsZero(): void
{
    $assetData = [[
        'asset_id' => 'asset-001',
        'book_value' => 1000,
        'original_cost' => 1000,
        'salvage_value' => 100,
        'accumulated_depreciation' => 0,
        'useful_life_months' => 0,
        'depreciation_method' => 'straight_line',
    ]];

    $this->dataProviderMock->method('getAssetBookValues')->willReturn(['assets' => $assetData]);

    $this->expectException(DepreciationCoordinationException::class);
    $this->service->executeRun(new DepreciationRunRequest('tenant-001', '2026-01', ['asset-001']));
}

public function testGenerateScheduleFailsWhenUsefulLifeYearsIsZero(): void
{
    $request = new DepreciationScheduleRequest(
        tenantId: 'tenant-001',
        assetId: 'asset-001',
        depreciationMethod: 'straight_line',
        usefulLifeYears: 0,
        salvageValue: '100'
    );

    $this->expectException(DepreciationCoordinationException::class);
    $this->service->generateSchedule($request);
}
```

- [ ] **Step 2: Run focused depreciation test file and confirm failures**

Run: `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/DepreciationRunServiceTest.php`
Expected: FAIL on new denominator safety tests.

- [ ] **Step 3: Add denominator guards and domain exception mapping**

```php
if ($usefulLifeMonths <= 0) {
    throw DepreciationCoordinationException::runFailed(
        $tenantId,
        $periodId,
        'Invalid useful life months: must be greater than zero'
    );
}
```

```php
$years = (int) ($usefulLifeMonths / 12);
if ($years <= 0) {
    throw DepreciationCoordinationException::runFailed(
        $tenantId,
        $periodId,
        'Invalid sum-of-years depreciation input: derived years must be greater than zero'
    );
}
```

```php
if ($request->usefulLifeYears <= 0) {
    throw DepreciationCoordinationException::scheduleGenerationFailed(
        $request->tenantId,
        $request->assetId,
        'Invalid useful life years: must be greater than zero'
    );
}
```

Implementation note:
- Prefer threading `tenantId`/`periodId` into internal calc method to preserve domain exception context.
- Keep valid formula behavior unchanged.

- [ ] **Step 4: Re-run focused depreciation tests**

Run: `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/DepreciationRunServiceTest.php`
Expected: PASS for denominator-guard tests and existing relevant tests.

- [ ] **Step 5: Commit Task 2 changes**

```bash
git add orchestrators/FinanceOperations/src/Services/DepreciationRunService.php \
  orchestrators/FinanceOperations/tests/Unit/Services/DepreciationRunServiceTest.php
git commit -m "hardening: guard depreciation division denominators"
```

### Task 3: Documentation and summary sync

**Files:**
- Modify: `orchestrators/FinanceOperations/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Add entry for division-safety hardening**

```markdown
## 2026-04-10 Division-Safety Hardening
- Added explicit denominator guards in cost allocation and depreciation arithmetic paths.
- Invalid denominator states now throw domain exceptions (`CostAllocationException`, `DepreciationCoordinationException`).
- Added regression tests for zero/invalid denominator inputs.
```

- [ ] **Step 2: Verify summary references match actual changed files**

Run: `rg -n "Division-Safety Hardening|denominator guards" orchestrators/FinanceOperations/IMPLEMENTATION_SUMMARY.md`
Expected: New section found once with correct date and scope.

- [ ] **Step 3: Commit summary update**

```bash
git add orchestrators/FinanceOperations/IMPLEMENTATION_SUMMARY.md
git commit -m "docs: record finance operations division-safety hardening"
```

### Task 4: Final verification gate

**Files:**
- Verify only (no new file): `orchestrators/FinanceOperations/src/Services/*.php`
- Verify only (no new file): `orchestrators/FinanceOperations/tests/Unit/Services/*.php`

- [ ] **Step 1: Run focused service test files**

Run in order:
1. `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/CostAllocationServiceTest.php`
2. `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/DepreciationRunServiceTest.php`

Expected: PASS for both focused suites.

- [ ] **Step 2: Run targeted static analysis on touched services**

Run: `./vendor/bin/phpstan analyse src/Services/CostAllocationService.php src/Services/DepreciationRunService.php --level=max`
Expected: ` [OK] No errors ` for touched files.

- [ ] **Step 3: Capture verification evidence in final PR description**

```markdown
Verification:
- phpunit (CostAllocationServiceTest): PASS
- phpunit (DepreciationRunServiceTest): PASS
- phpstan (touched services): PASS
```

- [ ] **Step 4: Create final integration commit (if squashing in branch workflow)**

```bash
git add -A
git commit -m "hardening: finance operations division-safety pass"
```

## Plan Self-Review
- Spec coverage: all scoped arithmetic hotspots are mapped to tasks.
- Placeholder scan: no TODO/TBD placeholders remain.
- Type consistency: exception classes and target file paths are consistent with spec.
- Scope check: single subsystem (`FinanceOperations` arithmetic hardening) and independently shippable.

## Execution Handoff
Plan complete and saved to `docs/superpowers/plans/2026-04-10-finance-operations-division-safety-pass-implementation-plan.md`. Two execution options:

1. Subagent-Driven (recommended) - I dispatch a fresh subagent per task, review between tasks, fast iteration
2. Inline Execution - Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?
