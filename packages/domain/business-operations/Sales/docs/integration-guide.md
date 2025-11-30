# Integration Guide: Sales

This guide shows how to integrate the Sales package into your application.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/sales:"*@dev"
```

### Step 2: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('customer_id', 26)->index();
            $table->string('number')->unique();
            $table->string('status');
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3);
            $table->timestamps();
        });
        
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('sales_order_id', 26)->index();
            $table->string('product_id', 26);
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();
        });
    }
};
```

### Step 3: Create Repository Implementation

```php
<?php

namespace App\Repositories;

use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use App\Models\SalesOrder;

final readonly class EloquentSalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function findById(string $id): SalesOrderInterface
    {
        return SalesOrder::findOrFail($id);
    }
    
    public function save(SalesOrderInterface $order): SalesOrderInterface
    {
        $order->save();
        return $order;
    }
}
```

### Step 4: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\SalesOrderManagerInterface;
use Nexus\Sales\Services\SalesOrderManager;
use App\Repositories\EloquentSalesOrderRepository;

class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SalesOrderRepositoryInterface::class,
            EloquentSalesOrderRepository::class
        );
        
        $this->app->bind(
            SalesOrderManagerInterface::class,
            SalesOrderManager::class
        );
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/sales:"*@dev"
```

### Step 2: Configure Services

`config/services.yaml`:

```yaml
services:
    Nexus\Sales\Contracts\SalesOrderRepositoryInterface:
        class: App\Repository\SalesOrderRepository
        
    Nexus\Sales\Contracts\SalesOrderManagerInterface:
        class: Nexus\Sales\Services\SalesOrderManager
        arguments:
            $repository: '@Nexus\Sales\Contracts\SalesOrderRepositoryInterface'
```

---

## Common Patterns

### Pattern 1: Stock Reservation

Always implement `StockReservationInterface` to connect with your inventory system:

```php
public function reserve(string $sku, float $quantity, string $reference): void
{
    // Call Inventory package
    $this->inventoryManager->reserveStock($sku, $quantity, $reference);
}
```

### Pattern 2: Credit Limit Check

Implement `CreditLimitCheckerInterface` to prevent orders exceeding credit:

```php
public function checkCredit(string $customerId, float $amount): bool
{
    // Check Receivable package
    return $this->receivableManager->checkCredit($customerId, $amount);
}
```
