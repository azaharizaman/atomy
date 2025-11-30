# Nexus Laravel Inventory Adapter

Laravel adapter for the Nexus Inventory package, providing Eloquent implementations of repository interfaces and service provider bindings.

## Overview

This package bridges the framework-agnostic `nexus/inventory` package with Laravel by providing:

- Eloquent Models for Inventory entities
- Eloquent Repository implementations
- Service Provider for dependency injection bindings
- Database migrations for Inventory tables

## Installation

```bash
composer require nexus/laravel-inventory
```

The package uses Laravel's auto-discovery, so the service provider will be registered automatically.

## Publishing Migrations

To publish the migrations to your application:

```bash
php artisan vendor:publish --tag=nexus-inventory-migrations
```

Then run the migrations:

```bash
php artisan migrate
```

## Components

### Models

- `StockLevel` - Current stock quantities per product/warehouse
- `StockMovement` - Stock movement history with full audit trail
- `Lot` - Lot/batch tracking with expiry management
- `Serial` - Serial number tracking with status management
- `Reservation` - Stock reservations for orders

### Repositories

- `EloquentStockLevelRepository` - Implements `StockLevelRepositoryInterface`
- `EloquentStockMovementRepository` - Implements `StockMovementRepositoryInterface`
- `EloquentInventoryAnalyticsRepository` - Implements `InventoryAnalyticsRepositoryInterface`

### Service Provider

The `InventoryServiceProvider` automatically binds:

```php
StockLevelRepositoryInterface::class => EloquentStockLevelRepository::class
StockMovementRepositoryInterface::class => EloquentStockMovementRepository::class
InventoryAnalyticsRepositoryInterface::class => EloquentInventoryAnalyticsRepository::class
```

## Usage

Once installed, you can inject the Inventory interfaces into your controllers or services:

```php
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\StockMovementRepositoryInterface;
use Nexus\Inventory\Enums\MovementType;

class StockController extends Controller
{
    public function __construct(
        private readonly StockLevelRepositoryInterface $stockLevelRepo,
        private readonly StockMovementRepositoryInterface $stockMovementRepo
    ) {}

    public function getCurrentStock(string $productId, string $warehouseId)
    {
        $quantity = $this->stockLevelRepo->getCurrentLevel($productId, $warehouseId);
        $reserved = $this->stockLevelRepo->getReservedQuantity($productId, $warehouseId);
        
        return response()->json([
            'quantity' => $quantity,
            'reserved' => $reserved,
            'available' => $quantity - $reserved,
        ]);
    }

    public function recordReceipt(Request $request)
    {
        $movementId = $this->stockMovementRepo->recordMovement(
            productId: $request->product_id,
            warehouseId: $request->warehouse_id,
            type: MovementType::RECEIPT,
            quantity: $request->quantity,
            unitCost: $request->unit_cost,
            referenceId: $request->reference_id,
        );
        
        return response()->json(['movement_id' => $movementId]);
    }
}
```

## Database Schema

### inv_stock_levels

| Column            | Type          | Description                     |
|-------------------|---------------|---------------------------------|
| id                | ULID          | Primary key                     |
| product_id        | ULID          | Product identifier              |
| warehouse_id      | ULID          | Warehouse identifier            |
| quantity          | DECIMAL(20,4) | Current stock quantity          |
| reserved_quantity | DECIMAL(20,4) | Reserved quantity               |
| created_at        | TIMESTAMP     | Creation timestamp              |
| updated_at        | TIMESTAMP     | Last update timestamp           |

### inv_lots

| Column            | Type          | Description                     |
|-------------------|---------------|---------------------------------|
| id                | ULID          | Primary key                     |
| product_id        | ULID          | Product identifier              |
| lot_number        | VARCHAR(100)  | Unique lot/batch number         |
| manufacture_date  | DATE          | Manufacturing date              |
| expiry_date       | DATE          | Expiration date                 |
| initial_quantity  | DECIMAL(20,4) | Initial quantity received       |
| remaining_quantity| DECIMAL(20,4) | Remaining quantity              |
| unit_cost         | DECIMAL(20,4) | Unit cost for this lot          |
| supplier_id       | ULID          | Supplier identifier             |
| batch_number      | VARCHAR(100)  | Supplier batch number           |
| attributes        | JSON          | Additional lot attributes       |
| created_at        | TIMESTAMP     | Creation timestamp              |
| updated_at        | TIMESTAMP     | Last update timestamp           |

### inv_serials

| Column            | Type          | Description                     |
|-------------------|---------------|---------------------------------|
| id                | ULID          | Primary key                     |
| product_id        | ULID          | Product identifier              |
| serial_number     | VARCHAR(100)  | Unique serial number            |
| warehouse_id      | ULID          | Current warehouse               |
| lot_id            | ULID          | Associated lot (optional)       |
| status            | VARCHAR(20)   | available, reserved, sold, etc. |
| cost              | DECIMAL(20,4) | Item cost                       |
| current_owner_id  | ULID          | Current owner (if sold)         |
| current_owner_type| VARCHAR(50)   | Owner type (customer, etc.)     |
| attributes        | JSON          | Additional serial attributes    |
| created_at        | TIMESTAMP     | Creation timestamp              |
| updated_at        | TIMESTAMP     | Last update timestamp           |

### inv_stock_movements

| Column         | Type          | Description                        |
|----------------|---------------|------------------------------------|
| id             | ULID          | Primary key                        |
| product_id     | ULID          | Product identifier                 |
| warehouse_id   | ULID          | Warehouse identifier               |
| movement_type  | VARCHAR(30)   | receipt, issue, adjustment, etc.   |
| quantity       | DECIMAL(20,4) | Movement quantity                  |
| unit_cost      | DECIMAL(20,4) | Unit cost at time of movement      |
| total_cost     | DECIMAL(20,4) | Total cost (quantity × unit_cost)  |
| reference_id   | ULID          | Reference document ID              |
| reference_type | VARCHAR(50)   | Reference type (PO, SO, etc.)      |
| lot_id         | ULID          | Associated lot (optional)          |
| serial_number  | VARCHAR(100)  | Serial number (if applicable)      |
| created_at     | TIMESTAMP     | Creation timestamp                 |
| updated_at     | TIMESTAMP     | Last update timestamp              |

### inv_reservations

| Column         | Type          | Description                        |
|----------------|---------------|------------------------------------|
| id             | ULID          | Primary key                        |
| product_id     | ULID          | Product identifier                 |
| warehouse_id   | ULID          | Warehouse identifier               |
| quantity       | DECIMAL(20,4) | Reserved quantity                  |
| reference_id   | ULID          | Reference document ID              |
| reference_type | VARCHAR(50)   | Reference type (SO, etc.)          |
| status         | VARCHAR(20)   | active, fulfilled, cancelled       |
| expires_at     | TIMESTAMP     | Reservation expiry time            |
| created_at     | TIMESTAMP     | Creation timestamp                 |
| updated_at     | TIMESTAMP     | Last update timestamp              |

## Movement Types

The package supports these movement types:

- `RECEIPT` - Goods received into stock
- `ISSUE` - Goods issued out of stock
- `ADJUSTMENT` - Stock count adjustment
- `TRANSFER_OUT` - Transfer to another warehouse
- `TRANSFER_IN` - Transfer from another warehouse
- `RESERVATION` - Stock reserved for order (reservation state change)
- `RESERVATION_RELEASE` - Reservation released (reservation state change)

> **Note:**  
> `RESERVATION` and `RESERVATION_RELEASE` record reservation state changes for audit and traceability.  
> These do **not** represent physical stock movements; actual stock quantity changes occur via `RECEIPT`, `ISSUE`, or other movement types.

## License

MIT License
