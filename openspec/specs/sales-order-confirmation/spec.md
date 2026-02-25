# sales-order-confirmation Specification

## Purpose
TBD - created by archiving change sales-dynamic-base-currency. Update Purpose after archive.
## Requirements
### Requirement: Dynamic Base Currency for Sales Order Confirmation

The system **MUST** retrieve the tenant's base currency from the configuration settings during the sales order confirmation process. This currency is used to lock the exchange rate for foreign currency orders.

#### Scenario: Order Confirmation with Foreign Currency

- **GIVEN** An order with currency 'USD' for a tenant whose base currency is 'MYR'.
- **WHEN** The order is confirmed.
- **THEN** The system retrieves 'MYR' from the tenant settings.
- **AND** The exchange rate from 'USD' to 'MYR' is fetched and locked on the order.

#### Scenario: Order Confirmation with Base Currency

- **GIVEN** An order with currency 'MYR' for a tenant whose base currency is 'MYR'.
- **WHEN** The order is confirmed.
- **THEN** The system retrieves 'MYR' from the tenant settings.
- **AND** No exchange rate locking is required since the currencies match.

