# Design: Dynamic Base Currency in Sales

## Context
The `SalesOrderManager` currently uses a hardcoded 'MYR' string as the base currency for all tenants. This needs to be replaced with a dynamic lookup.

## Goals
- Decouple `SalesOrderManager` from hardcoded currency.
- Adhere to the Three-Layer Rule by defining a local interface.

## Decisions

### Decision 1: Local Setting Interface
To avoid a direct dependency on the `Setting` package (which would violate the Three-Layer Rule), we will define `Nexus\Sales\Contracts\SalesSettingInterface`.

```php
namespace Nexus\Sales\Contracts;

interface SalesSettingInterface
{
    public function getBaseCurrency(string $tenantId): string;
}
```

### Decision 2: Constructor Injection
`SalesOrderManager` will receive an implementation of `SalesSettingInterface` via its constructor.

### Decision 3: Update `confirmOrder`
The `confirmOrder` method will call `$this->salesSetting->getBaseCurrency($order->getTenantId())` to get the base currency.
