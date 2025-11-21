# Nexus\Inventory

Framework-agnostic inventory and stock management package for Nexus ERP.

## Features

- **Multi-valuation support**: FIFO, Weighted Average, Standard Cost
- **Lot tracking**: FEFO (First-Expiry-First-Out) enforcement
- **Serial number tracking**: Unique serial allocation per tenant
- **Stock reservations**: Auto-expiry with configurable TTL
- **Stock transfers**: Multi-warehouse support with FSM workflow
- **Event-driven GL integration**: Publishes domain events for consumption by Finance package
- **Optional Event Sourcing**: Full stock replay capability via `nexus/event-stream`
- **Demand forecasting**: Optional integration with `nexus/intelligence`

## Installation

```bash
composer require nexus/inventory:"*@dev"
```

## Progressive Disclosure

This package has **optional dependencies** for advanced features:

```bash
# Event Sourcing (recommended for large enterprises)
composer require nexus/event-stream:"*@dev"

# Demand Forecasting (requires 90+ days of historical data)
composer require nexus/intelligence:"*@dev"
```

Core inventory features work without these dependencies.

## Architecture

**Package Layer (Framework-Agnostic):**
- All business logic in services
- All persistence via repository interfaces
- Domain events for cross-package integration
- Zero Laravel dependencies

**Application Layer (Atomy):**
- Eloquent models implementing package interfaces
- Database migrations
- Repository implementations
- Event listeners for GL integration

## Quick Start

```php
use Nexus\Inventory\Contracts\StockManagerInterface;

$stockManager = app(StockManagerInterface::class);

// Receive stock
$stockManager->receiveStock(
    productId: 'prod_123',
    warehouseId: 'wh_001',
    quantity: 100,
    unitCost: Money::of(25.50, 'MYR'),
    grnId: 'grn_001'
);

// Issue stock
$stockManager->issueStock(
    productId: 'prod_123',
    warehouseId: 'wh_001',
    quantity: 10,
    reason: IssueReason::SALE,
    referenceId: 'so_001'
);
```

## Documentation

See `docs/INVENTORY_WAREHOUSE_IMPLEMENTATION_SUMMARY.md` for complete implementation guide.

## License

MIT License
