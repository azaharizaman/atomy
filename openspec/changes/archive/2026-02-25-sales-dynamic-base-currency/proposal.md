# Proposal: Dynamic Base Currency in Sales

## Why
Currently, the `SalesOrderManager` in the `Sales` package hardcodes 'MYR' as the base currency when confirming orders. This prevents the system from supporting tenants with different base currencies, leading to incorrect exchange rate calculations and reporting for international users.

## What Changes
- Introduce a dependency on a setting/configuration interface in `SalesOrderManager`.
- Update the `confirmOrder` method to retrieve the base currency dynamically based on the tenant's settings.

## Capabilities

### Modified Capabilities
- `SalesOrder Confirmation`: Now correctly handles exchange rate locking by using the tenant's specific base currency instead of a hardcoded default.

## Impact
- `packages/Sales/src/Services/SalesOrderManager.php`: Constructor updated to inject setting service; `confirmOrder` logic updated.
