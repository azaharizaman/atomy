# Tasks: Dynamic Base Currency in Sales

## 1. Contracts

- [x] 1.1 Create `Nexus\Sales\Contracts\SalesSettingInterface`
- [x] 1.2 Update `SalesOrderManager` to inject `SalesSettingInterface`

## 2. Implementation

- [x] 2.1 Update `SalesOrderManager::confirmOrder` to use `SalesSettingInterface::getBaseCurrency()`

## 3. Verify

- [x] 3.1 Verify that the `confirmOrder` method now uses the retrieved base currency for exchange rate calculations.
