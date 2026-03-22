# Idempotency Laravel Adapter - Design Specification

**Date:** 2026-03-22  
**Package:** `nexus/laravel-idempotency-adapter`  
**Layer:** 3 (Adapter)  
**Status:** Draft

---

## 1. Overview

Provides Laravel-specific implementations for the `Nexus\Idempotency` Layer 1 package, enabling API request deduplication in Atomy-Q SaaS.

## 2. Goals

- Prevent duplicate API calls from causing duplicate database operations (payments, orders, etc.)
- Support both database and Redis storage backends
- Provide HTTP middleware for `Idempotency-Key` header extraction
- Follow existing Laravel adapter patterns in the codebase

## 3. Non-Goals

- Admin UI for viewing idempotency records (handled by Atomy-Q directly)
- Layer 2 orchestration (future consideration)
- Non-Laravel framework adapters

---

## 4. Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Atomy-Q Application                     │
│  (HTTP Request with Idempotency-Key header)                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              IdempotencyMiddleware                            │
│  - Extracts Idempotency-Key header                           │
│ - Resolves tenant from request                              │
│ - Calls IdempotencyService::begin()                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              IdempotencyService (L1)                         │
│  - begin() / complete() / fail()                           │
│  - Depends on IdempotencyStoreInterface                     │
└─────────────────────────────────────────────────────────────┘
                              │
            ┌─────────────────┴─────────────────┐
            ▼                                   ▼
┌─────────────────────────┐     ┌─────────────────────────┐
│ DatabaseIdempotencyStore│     │   RedisIdempotencyStore  │
│ (Eloquent + MySQL/PG)   │     │   (Predis/PhpRedis)      │
└─────────────────────────┘     └─────────────────────────┘
```

---

## 5. Components

### 5.1 Database Schema

```sql
CREATE TABLE nexus_idempotency_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    operation_ref VARCHAR(255) NOT NULL,
    client_key VARCHAR(255) NOT NULL,
    request_fingerprint TEXT NOT NULL,
    attempt_token VARCHAR(36) NOT NULL,
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    result_envelope JSONB,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_tenant_operation_client (tenant_id, operation_ref, client_key),
    INDEX idx_expires_at (expires_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.2 Eloquent Model

**Namespace:** `Nexus\Laravel\Idempotency\Models\IdempotencyRecord`

- Maps to `nexus_idempotency_records` table
- Uses `HasFactory` trait
- Casts `result_envelope` to array
- Casts `expires_at` to datetime

### 5.3 Store Implementations

| Class | Interface | Description |
|-------|-----------|-------------|
| `DatabaseIdempotencyStore` | `IdempotencyStoreInterface` | MySQL/PostgreSQL implementation using Eloquent |
| `RedisIdempotencyStore` | `IdempotencyStoreInterface` | Redis implementation for high-throughput |
| `IdempotencyStoreManager` | - | Resolves store based on config (db/redis) |

### 5.4 HTTP Middleware

**Class:** `Nexus\Laravel\Idempotency\Http\IdempotencyMiddleware`

- Extracts `Idempotency-Key` header (required)
- Resolves `tenant_id` from Laravel auth or header
- Extracts `operation_ref` from route or request (e.g., `POST /api/invoices`)
- Calls `$idempotencyService->begin()` before controller
- Returns 409 Conflict if duplicate in progress
- Attaches `Idempotency-Key` to response headers

### 5.5 Service Provider

**Class:** `Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider`

- Registers store implementations
- Registers middleware alias
- Publishes migration file
- Config: `config/nexus-idempotency.php`

### 5.6 Configuration

```php
// config/nexus-idempotency.php
return [
    'store' => env('IDEMPOTENCY_STORE', 'database'), // 'database' | 'redis'
    
    'default_ttl_seconds' => env('IDEMPOTENCY_TTL', 86400), // 24 hours
    
    'redis' => [
        'connection' => env('IDEMPOTENCY_REDIS_CONNECTION', 'default'),
        'prefix' => 'nexus:idempotency:',
    ],
    
    'middleware' => [
        'enabled' => true,
        'header_name' => 'Idempotency-Key',
        'tenant_header' => 'X-Tenant-ID', // or null for auth-based
    ],
];
```

---

## 6. Usage

### 6.1 Installation

```bash
composer require nexus/laravel-idempotency-adapter
php artisan migrate
php artisan vendor:publish --provider="Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider"
```

### 6.2 Route Registration

```php
// routes/api.php
Route::middleware(['api', 'idempotency'])->group(function () {
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::post('/payments', [PaymentController::class, 'store']);
});
```

### 6.3 Controller Usage

```php
class InvoiceController extends Controller
{
    public function store(Request $request, IdempotencyService $idempotency)
    {
        // Idempotency middleware already called begin()
        // $idempotency->complete($result) on success
        // $idempotency->fail($reason) on error
        
        $invoice = Invoice::create($request->validated());
        
        $idempotency->complete(['invoice_id' => $invoice->id]);
        
        return response()->json(['invoice' => $invoice], 201);
    }
}
```

---

## 7. Error Handling

| Scenario | HTTP Status | Response |
|----------|-------------|----------|
| Missing Idempotency-Key | 400 | `{"error": "Idempotency-Key header required"}` |
| Duplicate in progress | 409 | `{"error": "Duplicate request in progress"}` with Retry-After |
| Record expired | 201 | Normal response (new execution) |
| Invalid tenant | 400 | `{"error": "Tenant identification failed"}` |

---

## 8. Testing Strategy

- Unit tests for store implementations
- Integration tests for middleware flow
- Feature tests for duplicate detection
- Redis store tests (if Redis available)

---

## 9. Deferred / Future

- Redis store implementation (Phase 2)
- Layer 2 orchestration with PolicyEngine
- Automatic idempotency for Laravel resources
- Metrics/monitoring dashboard

---

## 10. Dependencies

```json
{
    "require": {
        "php": "^8.3",
        "illuminate/support": "^11.0|^12.0",
        "illuminate/database": "^11.0|^12.0",
        "nexus/idempotency": "*@dev",
        "psr/log": "^3.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "mockery/mockery": "^1.6"
    }
}
```

---

## 11. File Structure

```
adapters/Laravel/Idempotency/
├── composer.json
├── config/
│   └── nexus-idempotency.php
├── database/
│   └── migrations/
│       └── 2026_03_22_000001_create_nexus_idempotency_records_table.php
├── src/
│   ├── Adapters/
│   │   ├── DatabaseIdempotencyStore.php
│   │   └── RedisIdempotencyStore.php
│   ├── Http/
│   │   └── IdempotencyMiddleware.php
│   ├── Models/
│   │   └── IdempotencyRecord.php
│   ├── Providers/
│   │   └── IdempotencyAdapterServiceProvider.php
│   └── Support/
│       └── IdempotencyStoreManager.php
└── tests/
    ├── Unit/
    │   └── DatabaseIdempotencyStoreTest.php
    └── Integration/
        └── IdempotencyMiddlewareTest.php
```
