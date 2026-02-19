# Nexus Laravel Sales Adapter

This adapter provides Laravel-specific implementations for the Sales package, enabling integration with other Nexus packages.

## Purpose

The Sales package is an atomic package that must remain independently publishable. This adapter layer provides the concrete implementations that integrate Sales with other packages like:

- **Nexus\Receivable** - For credit limit checking and invoice generation
- **Nexus\Inventory** - For stock reservation and availability checking

## Installation

```bash
composer require nexus/laravel-sales
```

## Adapters Provided

### ReceivableCreditLimitCheckerAdapter

Implements `Nexus\Sales\Contracts\CreditLimitCheckerInterface` by delegating to `Nexus\Receivable\Contracts\CreditLimitCheckerInterface`.

### InventoryStockReservationAdapter

Implements `Nexus\Sales\Contracts\StockReservationInterface` by delegating to `Nexus\Inventory\Contracts\ReservationManagerInterface`.

### ReceivableInvoiceManagerAdapter

Implements `Nexus\Sales\Contracts\InvoiceManagerInterface` by delegating to `Nexus\Receivable\Contracts\ReceivableManagerInterface`.

## Service Provider

The `SalesAdapterServiceProvider` automatically binds the Sales interfaces to their adapter implementations when all required packages are available.

## Architecture

This follows the Nexus Three-Layer Architecture:

1. **Atomic Layer** (`packages/Sales`) - Pure business logic, no external dependencies
2. **Adapter Layer** (`adapters/Laravel/Sales`) - Framework-specific implementations
3. **Application Layer** - Uses adapters through interfaces

## Dependencies

- `nexus/sales` - The atomic Sales package
- `nexus/receivable` - For credit checking and invoice generation
- `nexus/inventory` - For stock reservation
- `illuminate/support` - Laravel framework components
- `illuminate/database` - Laravel database components
