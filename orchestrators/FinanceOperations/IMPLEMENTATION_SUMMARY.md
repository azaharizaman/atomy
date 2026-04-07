# FinanceOperations implementation summary

## 2026-04-07 - Layer 2 hardening pass (type-safe provider boundaries)

### Why this slice was selected
- `orchestrators/FinanceOperations` had concentrated high-impact gaps: critical data providers in cash-flow, GL reconciliation, and cost accounting accepted generic `object` dependencies.
- Generic dependency typing weakens contract safety and can hide adapter mismatches until runtime in financial workflows.

### What was improved
- Replaced generic constructor dependencies in:
  - `TreasuryDataProvider`
  - `GLReconciliationProvider`
  - `CostAccountingDataProvider`
- Added explicit contracts for orchestration query boundaries:
  - `TreasuryManagerQueryInterface`
  - `ReceivableQueryInterface`
  - `PayableQueryInterface`
  - `CostAccountingManagerQueryInterface`
- Hardened existing contracts to match real provider usage:
  - `LedgerQueryInterface` now includes account and cost-center balance/read methods used by providers.
  - `BudgetQueryInterface` now includes `getCostCenterBudget(...)`.
  - `AssetQueryInterface` now includes reconciliation query methods (`getNetBookValueTotal`, `getControlAccountCode`, `getUnpostedDepreciation`).

### Regression tests added
- `tests/Unit/DataProviders/TreasuryDataProviderTest.php`
- `tests/Unit/DataProviders/GLReconciliationProviderTest.php`
- `tests/Unit/DataProviders/CostAccountingDataProviderTest.php`

These tests verify mapping correctness and key financial calculations around totals/variance under the new typed contracts.
