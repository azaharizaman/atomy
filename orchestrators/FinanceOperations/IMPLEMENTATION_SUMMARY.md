# FinanceOperations Hardening Summary

## Scope

Hardened Layer 2 rule dependencies in `orchestrators/FinanceOperations` by replacing generic constructor dependency types (`object`) with explicit orchestrator-owned contracts.

## Why this package was selected

`FinanceOperations` had one of the highest concentrations of weak typing in core rule classes, creating avoidable coupling and runtime ambiguity in a high-impact orchestration domain.

## Changes implemented

### 1) Added explicit Layer-2 contracts

- `src/Contracts/PeriodLookupInterface.php`
- `src/Contracts/SubledgerClosureQueryInterface.php`
- `src/Contracts/BudgetAvailabilityQueryInterface.php`
- `src/Contracts/CostCenterQueryInterface.php`
- `src/Contracts/GLAccountQueryInterface.php`
- `src/Contracts/GLMappingRepositoryInterface.php`

### 2) Replaced generic dependency types in rules

- `src/Rules/PeriodOpenRule.php`
  - `object $periodManager` -> `PeriodLookupInterface $periodManager`
- `src/Rules/SubledgerClosedRule.php`
  - `object $periodManager` -> `SubledgerClosureQueryInterface $periodManager`
- `src/Rules/BudgetAvailableRule.php`
  - `object $budgetQuery` -> `BudgetAvailabilityQueryInterface $budgetQuery`
- `src/Rules/CostCenterActiveRule.php`
  - `object $costCenterQuery` -> `CostCenterQueryInterface $costCenterQuery`
- `src/Rules/GLAccountMappingRule.php`
  - `object $chartOfAccountQuery` -> `GLAccountQueryInterface $chartOfAccountQuery`
  - `object $mappingRepository` -> `GLMappingRepositoryInterface $mappingRepository`

### 3) Updated unit tests to new contracts

- `tests/Unit/Rules/PeriodOpenRuleTest.php`
- `tests/Unit/Rules/SubledgerClosedRuleTest.php`
- `tests/Unit/Rules/BudgetAvailableRuleTest.php`
- `tests/Unit/Rules/CostCenterActiveRuleTest.php`
- `tests/Unit/Rules/GLAccountMappingRuleTest.php`

Anonymous test doubles now implement the corresponding contracts.

## Validation

Executed:

```bash
./vendor/bin/phpunit \
  tests/Unit/Rules/PeriodOpenRuleTest.php \
  tests/Unit/Rules/SubledgerClosedRuleTest.php \
  tests/Unit/Rules/BudgetAvailableRuleTest.php \
  tests/Unit/Rules/CostCenterActiveRuleTest.php \
  tests/Unit/Rules/GLAccountMappingRuleTest.php
```

Result:

- 57 tests
- 90 assertions
- All passing

## Impact

- Reduces weak typing in core Layer 2 rule dependencies.
- Improves contract clarity and adapter boundary enforcement.
- Preserves existing rule behavior and test coverage.
