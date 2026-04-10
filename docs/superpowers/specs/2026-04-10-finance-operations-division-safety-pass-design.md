# FinanceOperations Division-Safety Pass Design

## Context
`orchestrators/FinanceOperations` contains multiple arithmetic paths where denominators can reach `0` (or invalid values) and cause runtime failures or undefined financial output. This violates the project hardening rule that all division operations must be guarded.

Current hotspots include:
- `Services/CostAllocationService::calculateAllocations` (`$count`, `$totalWeight`, `$totalAmount` usage)
- `Services/DepreciationRunService::calculateDepreciation` (`$usefulLifeMonths`, `$years`, sum-of-years denominator)
- `Services/DepreciationRunService::calculateSchedulePeriods` (`$usefulLifeYears`)

## Problem Statement
Financial orchestration logic currently assumes valid positive denominator inputs in several places. If adapters or callers pass unexpected values (e.g., empty targets, zero weights, useful life `0` or `< 12` months in sum-of-years mode), the package can fail with generic runtime errors instead of deterministic domain exceptions.

## Goals
1. Enforce explicit denominator guards for all production division sites in the identified arithmetic paths.
2. Replace implicit runtime failures with domain-specific exceptions carrying stable, actionable messages.
3. Add regression tests that prove failure behavior for invalid denominator conditions and successful behavior for valid inputs.
4. Keep this pass narrowly scoped to arithmetic safety only (no architecture refactor in this slice).

## Non-Goals
- Reworking duplicate DTO namespace structure in this package.
- Replacing remaining `object`-typed dependencies in rules/providers.
- Monetary precision overhaul (float -> decimal VO conversion).
- Broad contract normalization across all package tests.

## Brainstormed Approaches

### Approach A: Minimal inline guards at each division site
Add direct `if` checks immediately before divisions and throw domain exceptions.

Pros:
- Fastest to deliver.
- Very low blast radius.
- Easy test targeting.

Cons:
- Guard logic may be repeated across methods.

### Approach B: Centralized denominator guard helper methods inside each service
Introduce internal private helpers (e.g., `assertPositiveIntDenominator`, `assertPositiveFloatDenominator`) and use them before each division.

Pros:
- Consistent behavior/messages.
- Easier future extension and auditability.

Cons:
- Slightly larger refactor than pure inline checks.

### Approach C: Input validation only at DTO constructors
Constrain values during DTO construction so divisions never receive invalid denominators.

Pros:
- Strong early contract boundaries.

Cons:
- Not sufficient alone because denominators also derive from runtime data (`weights`, derived years, data-provider payloads).
- Larger compatibility impact to callers/tests.

## Recommended Approach
Use **Approach B** now: add focused service-local guard helpers and call them at every denominator use in `CostAllocationService` and `DepreciationRunService`.

This provides consistent, testable hardening with low risk and avoids introducing broader contract changes.

## Detailed Design

### 1. CostAllocationService hardening
#### Target file
`orchestrators/FinanceOperations/src/Services/CostAllocationService.php`

#### Changes
- Guard `equal` method denominator:
  - Ensure `count($targetCostCenterIds) > 0` before `100 / $count` and `$totalAmount / $count`.
  - `targetCount` is the cached caller-level value derived from `count($targetCostCenterIds)`. Keep the early `targetCount === 0` short-circuit for performance, but do not rely on it as the only validation.
  - `calculateAllocations` must still re-validate `count($targetCostCenterIds) > 0` and throw `CostAllocationException::allocationFailed(...)` (for example: `Cannot allocate to zero cost centers`) before any division as defense-in-depth.
- Guard `proportional` method denominator:
  - Ensure `array_sum($weights) > 0` before percentage/amount math.
  - Throw `CostAllocationException::allocationFailed(...)` with reason like `Invalid allocation weights: total weight must be > 0`.
- Guard `manual` method percentage denominator:
  - Keep the existing ternary guard `($totalAmount > 0 ? ($amount / $totalAmount) * 100 : 0)`, which explicitly short-circuits division when `$totalAmount` is zero.
- Convert current `\InvalidArgumentException` usage for bad method/options into `CostAllocationException::allocationFailed(...)` to keep domain exception contract at service boundary.

### 2. DepreciationRunService hardening
#### Target file
`orchestrators/FinanceOperations/src/Services/DepreciationRunService.php`

#### Changes
- In `calculateDepreciation`:
  - Validate `$usefulLifeMonths > 0` before any division (straight-line and declining-balance branches).
  - In `sum_of_years`, validate:
    - `$years > 0` (derived from months),
    - `$sumOfYears > 0`,
    - the computed denominator `($depreciableAmount / $years)` is non-zero before it is used as a denominator to compute `$currentYear`.
  - On invalid denominator conditions, throw `DepreciationCoordinationException::runFailed(...)` with clear, stable reason messages.
- In `calculateSchedulePeriods`:
  - Validate `$usefulLifeYears > 0` before annual and monthly division.
  - Throw `DepreciationCoordinationException::scheduleGenerationFailed(...)` for invalid life values.

### 3. Exception and message policy
- Use existing domain exceptions only:
  - `CostAllocationException`
  - `DepreciationCoordinationException`
- Messages must be stable and non-sensitive.
- Include key identifiers (`tenantId`, `periodId`, `assetId`, `sourceCostPoolId`) in exception factory parameters where supported.

### 4. Tests
#### Target tests
- `orchestrators/FinanceOperations/tests/Unit/Services/CostAllocationServiceTest.php`
- `orchestrators/FinanceOperations/tests/Unit/Services/DepreciationRunServiceTest.php`

#### New test cases
- Cost allocation:
  - proportional weights sum to zero -> throws `CostAllocationException`.
  - invalid allocation method -> throws `CostAllocationException` (not generic `InvalidArgumentException`).
- Depreciation:
  - useful life months set to `0` -> run fails with `DepreciationCoordinationException`.
  - sum-of-years path with derived years `0` (e.g., months `< 12`) -> run fails with `DepreciationCoordinationException`.
  - schedule request with useful life years `0` -> schedule generation fails with `DepreciationCoordinationException`.

## Risks and Mitigations
- Risk: Existing tests currently have broad red baseline unrelated to this slice.
  - Mitigation: run focused service tests for touched classes as verification gate.
- Risk: tighter exception contracts may require minor test expectation updates.
  - Mitigation: update only tests directly tied to touched behavior.

## Verification Strategy
- Focused unit tests:
  - `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/CostAllocationServiceTest.php`
  - `./vendor/bin/phpunit -c phpunit.xml tests/Unit/Services/DepreciationRunServiceTest.php`
- Optional static check for touched files:
  - `./vendor/bin/phpstan analyse src/Services/CostAllocationService.php src/Services/DepreciationRunService.php --level=max`

## Acceptance Criteria
- No remaining unguarded denominator in the scoped arithmetic sites.
- Invalid denominator scenarios throw domain exceptions, never PHP warnings/errors.
- New tests cover denominator guard failures and pass in focused runs.
- Existing happy-path behavior remains unchanged for valid inputs.
