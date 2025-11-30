# Getting Started with Nexus Period

## Overview

Nexus Period is a **fiscal period management package** for Nexus ERP. It provides robust period lifecycle management, posting validation, and multi-type period support for Accounting, Inventory, Payroll, and Manufacturing processes.

**Critical Principle:** All transaction posting in a financial system MUST validate against open periods. This package provides the central authority for period validation.

---

## Prerequisites

- **PHP 8.3 or higher** (native enums, readonly properties, constructor property promotion)
- **Composer** for package management
- **Cache system** (Redis/Memcached recommended for production - mandatory for <5ms validation performance)

### Recommended
- **Nexus\AuditLogger** for audit trail integration
- **Nexus\Tenant** for multi-tenant deployments

---

## When to Use This Package

This package is designed for:

✅ **ERP systems** requiring fiscal period management  
✅ **Accounting modules** needing open/closed period validation  
✅ **Inventory management** with period-controlled stock adjustments  
✅ **Payroll systems** with payroll period management  
✅ **Manufacturing** with production period tracking  
✅ **Compliance requirements** requiring period lock/unlock audit trails  

Do NOT use this package for:

❌ **Simple date range filtering** - Use native PHP DateTimeImmutable  
❌ **Calendar scheduling** - Use `Nexus\Scheduler` instead  
❌ **Non-financial applications** - This is specifically for fiscal period management  

---

## Core Concepts

### Concept 1: Period Types Are Independent

Each period type operates **independently** with its own lifecycle:

```
┌─────────────────────────────────────────────────────────┐
│                    Period Types                          │
├──────────────┬──────────────┬──────────────┬────────────┤
│  Accounting  │  Inventory   │   Payroll    │ Manufacturing│
│   (FIN-001)  │  (INV-001)   │  (PAY-001)   │  (MFG-001)   │
│      ✅ Open │      ✅ Open │     🔒 Closed │      ⏳ Pending│
└──────────────┴──────────────┴──────────────┴────────────┘
```

- **Accounting Period** can be open while **Payroll Period** is closed
- Each type has its own open/closed status
- Changes to one type don't affect others

### Concept 2: Period Status Lifecycle

Periods follow a strict state machine:

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ Pending  │ ──▶ │   Open   │ ──▶ │  Closed  │ ──▶ │  Locked  │
└──────────┘     └──────────┘     └──────────┘     └──────────┘
                      ▲                │
                      └────────────────┘
                      (reopen - authorized)
```

- **Pending**: Period created but not yet active
- **Open**: Transactions can be posted to this period
- **Closed**: No new transactions; can be reopened by authorized users
- **Locked**: Permanently sealed; cannot be reopened (year-end close)

### Concept 3: Transaction Validation (< 5ms)

The `isPostingAllowed()` method is a **critical performance path**:

```php
// This must execute in < 5ms - uses caching internally
$canPost = $periodManager->isPostingAllowed(
    date: new DateTimeImmutable('2024-01-15'),
    type: PeriodType::Accounting
);
```

The package uses internal caching to ensure sub-5ms response times for validation.

### Concept 4: Framework Agnosticism

All dependencies are interfaces. Your application provides implementations:

```php
// Package defines the contract
interface PeriodRepositoryInterface {
    public function findById(string $id): PeriodInterface;
}

// Your Laravel app provides the implementation
final readonly class EloquentPeriodRepository implements PeriodRepositoryInterface {
    public function findById(string $id): PeriodInterface {
        return Period::findOrFail($id);
    }
}
```

### Concept 5: Authorization for Sensitive Operations

Reopening a closed period requires explicit authorization:

```php
// AuthorizationInterface must be implemented by your app
interface AuthorizationInterface {
    public function canReopenPeriod(string $userId): bool;
}
```

Only authorized users (typically CFO, Controller) can reopen closed periods.

---

## Installation

```bash
composer require nexus/period:"*@dev"
```

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The package requires 4 interface implementations:

#### 1.1 Period Repository

```php
namespace App\Repositories;

use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Enums\PeriodStatus;
use App\Models\Period;

final readonly class EloquentPeriodRepository implements PeriodRepositoryInterface
{
    public function findById(string $id): PeriodInterface
    {
        return Period::findOrFail($id);
    }

    public function findOpenByType(PeriodType $type): ?PeriodInterface
    {
        return Period::where('type', $type->value)
            ->where('status', PeriodStatus::Open->value)
            ->first();
    }

    public function findByDate(\DateTimeImmutable $date, PeriodType $type): ?PeriodInterface
    {
        return Period::where('type', $type->value)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    public function findByType(PeriodType $type): array
    {
        return Period::where('type', $type->value)
            ->orderBy('start_date')
            ->get()
            ->all();
    }

    public function save(PeriodInterface $period): void
    {
        $period->save();
    }
}
```

#### 1.2 Cache Repository

```php
namespace App\Repositories;

use Nexus\Period\Contracts\CacheRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final readonly class LaravelCacheRepository implements CacheRepositoryInterface
{
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        Cache::put($key, $value, $ttl);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
```

#### 1.3 Authorization

```php
namespace App\Services;

use Nexus\Period\Contracts\AuthorizationInterface;

final readonly class PeriodAuthorization implements AuthorizationInterface
{
    public function canReopenPeriod(string $userId): bool
    {
        // Check if user has 'period.reopen' permission
        $user = User::find($userId);
        return $user?->hasPermission('period.reopen') ?? false;
    }
}
```

#### 1.4 Audit Logger

```php
namespace App\Services;

use Nexus\Period\Contracts\AuditLoggerInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;

final readonly class PeriodAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private AuditLogManagerInterface $auditLogger
    ) {}

    public function log(string $entityId, string $action, string $description): void
    {
        $this->auditLogger->log($entityId, $action, $description);
    }
}
```

### Step 2: Bind Interfaces in Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Period\Contracts\{
    PeriodRepositoryInterface,
    CacheRepositoryInterface,
    AuthorizationInterface,
    AuditLoggerInterface,
    PeriodManagerInterface
};
use Nexus\Period\Services\PeriodManager;
use App\Repositories\{
    EloquentPeriodRepository,
    LaravelCacheRepository
};
use App\Services\{
    PeriodAuthorization,
    PeriodAuditLogger
};

class PeriodServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->singleton(PeriodRepositoryInterface::class, EloquentPeriodRepository::class);
        $this->app->singleton(CacheRepositoryInterface::class, LaravelCacheRepository::class);
        
        // Service bindings
        $this->app->singleton(AuthorizationInterface::class, PeriodAuthorization::class);
        $this->app->singleton(AuditLoggerInterface::class, PeriodAuditLogger::class);
        
        // Main service (auto-wired)
        $this->app->singleton(PeriodManagerInterface::class, PeriodManager::class);
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PeriodServiceProvider::class,
],
```

### Step 3: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('type'); // accounting, inventory, payroll, manufacturing
            $table->string('status'); // pending, open, closed, locked
            $table->date('start_date');
            $table->date('end_date');
            $table->string('fiscal_year', 10);
            $table->string('name'); // e.g., "JAN-2024", "2024-Q1"
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'type', 'status']);
            $table->index(['tenant_id', 'type', 'start_date', 'end_date']);
            
            // Ensure no overlapping periods per type
            $table->unique(['tenant_id', 'type', 'start_date']);
            $table->unique(['tenant_id', 'type', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
```

---

## Your First Integration

### Example 1: Validate Transaction Posting

```php
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\NoOpenPeriodException;
use Nexus\Period\Exceptions\PostingPeriodClosedException;

class JournalEntryService
{
    public function __construct(
        private readonly PeriodManagerInterface $periodManager
    ) {}

    public function post(array $journalData): void
    {
        $transactionDate = new \DateTimeImmutable($journalData['date']);
        
        // Validate period is open for posting (executes in < 5ms)
        try {
            if (!$this->periodManager->isPostingAllowed($transactionDate, PeriodType::Accounting)) {
                throw new PostingPeriodClosedException(
                    "Cannot post journal entry: Accounting period is not open for {$transactionDate->format('Y-m-d')}"
                );
            }
        } catch (NoOpenPeriodException $e) {
            throw new \RuntimeException(
                'No accounting period is currently open. Please contact your administrator.'
            );
        }
        
        // Proceed with posting...
    }
}
```

### Example 2: Close a Period

```php
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Exceptions\PeriodNotFoundException;
use Nexus\Period\Exceptions\InvalidPeriodStatusException;

class PeriodController
{
    public function __construct(
        private readonly PeriodManagerInterface $periodManager
    ) {}

    public function close(Request $request, string $periodId)
    {
        try {
            $this->periodManager->closePeriod(
                periodId: $periodId,
                reason: $request->input('reason'),
                userId: $request->user()->id
            );
            
            return response()->json(['message' => 'Period closed successfully']);
            
        } catch (PeriodNotFoundException $e) {
            return response()->json(['error' => 'Period not found'], 404);
            
        } catch (InvalidPeriodStatusException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
```

### Example 3: Create Next Period

```php
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\OverlappingPeriodException;

class PeriodSetupService
{
    public function __construct(
        private readonly PeriodManagerInterface $periodManager
    ) {}

    public function createNextAccountingPeriod(): void
    {
        try {
            $newPeriod = $this->periodManager->createNextPeriod(PeriodType::Accounting);
            
            echo "Created: {$newPeriod->getName()} ({$newPeriod->getStartDate()->format('Y-m-d')} to {$newPeriod->getEndDate()->format('Y-m-d')})";
            
        } catch (OverlappingPeriodException $e) {
            throw new \RuntimeException('Cannot create period: would overlap with existing period');
        }
    }
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check the [Integration Guide](integration-guide.md) for Laravel and Symfony examples
- Review [Basic Usage Example](examples/basic-usage.php) for common patterns
- Review [Advanced Usage Example](examples/advanced-usage.php) for year-end close and complex scenarios

---

## Troubleshooting

### Common Issues

**Issue 1: "No open period found for type: Accounting"**
- **Cause:** No period with status `Open` exists for the Accounting type
- **Solution:** Create a period and set its status to `Open`

**Issue 2: Performance is slow (>5ms for validation)**
- **Cause:** Cache is not properly configured
- **Solution:** Ensure `CacheRepositoryInterface` is bound to Redis/Memcached, not file/array cache

**Issue 3: "Period reopening unauthorized"**
- **Cause:** User doesn't have `period.reopen` permission
- **Solution:** Ensure `AuthorizationInterface::canReopenPeriod()` returns true for authorized users

**Issue 4: "Cannot transition from Locked to Open"**
- **Cause:** Locked periods are permanently sealed
- **Solution:** Locked periods cannot be reopened; this is by design for compliance
