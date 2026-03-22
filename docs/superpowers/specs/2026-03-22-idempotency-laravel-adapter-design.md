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

**Critical:** The unique constraint MUST be enforced at database level for atomic `claimPending()` to work correctly.

```sql
CREATE TABLE nexus_idempotency_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    operation_ref VARCHAR(255) NOT NULL,
    client_key VARCHAR(255) NOT NULL,
    request_fingerprint TEXT NOT NULL,
    attempt_token VARCHAR(36) NOT NULL,
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    result_envelope JSON,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_operation_client (tenant_id, operation_ref, client_key),
    INDEX idx_expires_at (expires_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note:** Uses `JSON` for MySQL compatibility (PostgreSQL uses `JSONB` in separate migration).

### 5.2 Eloquent Model

**Namespace:** `Nexus\Laravel\Idempotency\Models\IdempotencyRecord`

- Maps to `nexus_idempotency_records` table
- Uses `HasFactory` trait
- Casts `result_envelope` to array
- Casts `expires_at` to datetime
- Implements tenant scope via global scope to prevent cross-tenant queries

### 5.3 Clock Implementation (Required Dependency)

**Class:** `Nexus\Laravel\Idempotency\Clock\LaravelIdempotencyClock`

- Implements `IdempotencyClockInterface`
- Returns `DateTimeImmutable` via `CarbonImmutable::now()`
- Registered as singleton in service provider

### 5.4 Policy Factory

**Class:** `Nexus\Laravel\Idempotency\Support\IdempotencyPolicyFactory`

- Creates `IdempotencyPolicy` from config
- Uses config values:
  - `pending_ttl_seconds` (default: 604800 = 7 days)
  - `allow_retry_after_fail` (default: true)
  - `expire_completed_after_seconds` (default: null = never)

### 5.5 Store Implementations

| Class | Interface | Description |
|-------|-----------|-------------|
| `DatabaseIdempotencyStore` | `IdempotencyStoreInterface` | MySQL/PostgreSQL implementation using Eloquent |
| `RedisIdempotencyStore` | `IdempotencyStoreInterface` | Redis implementation for high-throughput (Phase 2) |
| `IdempotencyStoreManager` | - | Resolves store based on config (db/redis) |

**Atomic claimPending() Implementation:**
- Database: Uses `INSERT ... ON DUPLICATE KEY UPDATE` for MySQL
- Redis: Uses `SETNX` + pipeline for atomic claim

**VO Serialization:**
The store converts Layer 1 VOs to/from database columns:
- `tenant_id`, `operation_ref`, `client_key`, `request_fingerprint`, `attempt_token` → VARCHAR/TEXT
- `result_envelope` → JSON column (reconstructed via constructor)
- `expires_at` → TIMESTAMP

### 5.6 HTTP Middleware

**Class:** `Nexus\Laravel\Idempotency\Http\IdempotencyMiddleware`

- Extracts `Idempotency-Key` header (required)
- Resolves `tenant_id` from Laravel auth or header
- Extracts `operation_ref` from route (e.g., `POST /api/invoices`)
- Computes `RequestFingerprint` from request body/method/uri
- Calls `$idempotencyService->begin()` before controller

**Request Flow (Critical):**
1. Middleware calls `begin()` → returns `BeginDecision`
2. Middleware stores `IdempotencyRequest` (with all VOs) in request attributes:
   - `$request->attributes->set('idempotency_request', new IdempotencyRequest(...))`
3. Controller retrieves via `Idempotency` trait: `$this->getIdempotencyRequest($request)`
4. Controller calls `complete()` or `fail()` using the IdempotencyRequest VOs

**Response Handling:**
- Returns 409 Conflict if `BeginOutcome::InProgress`
- Returns cached result if `BeginOutcome::Replay`
- Returns 201 with result if `BeginOutcome::FirstExecution`
- Adds `Idempotency-Key` to response headers

### 5.7 Service Provider

**Class:** `Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider`

- Registers `LaravelIdempotencyClock` as singleton implementing `IdempotencyClockInterface`
- Registers `IdempotencyPolicyFactory` 
- Registers `DatabaseIdempotencyStore` (or resolves via manager)
- Registers `IdempotencyService` with all dependencies:
  - `IdempotencyQueryInterface` → `DatabaseIdempotencyStore`
  - `IdempotencyPersistInterface` → `DatabaseIdempotencyStore`
  - `IdempotencyClockInterface` → `LaravelIdempotencyClock`
  - `IdempotencyPolicy` → from factory
- Registers middleware alias
- Publishes migration file
- Config: `config/nexus-idempotency.php`

### 5.8 Configuration

```php
// config/nexus-idempotency.php
return [
    'store' => env('IDEMPOTENCY_STORE', 'database'), // 'database' | 'redis'
    
    // Policy settings
    'policy' => [
        'pending_ttl_seconds' => (int) env('IDEMPOTENCY_PENDING_TTL', 604800), // 7 days
        'allow_retry_after_fail' => env('IDEMPOTENCY_ALLOW_RETRY', true),
        'expire_completed_after_seconds' => (int) env('IDEMPOTENCY_COMPLETED_TTL', 86400), // 24 hours
    ],
    
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
use Nexus\Idempotency\ValueObjects\ResultEnvelope;

class InvoiceController extends Controller
{
    public function store(Request $request, IdempotencyServiceInterface $idempotency)
    {
        // Middleware already called begin() and stored decision in request
        $decision = $request->attributes->get('idempotency_decision');
        
        // If replay, return cached result immediately
        if ($decision->outcome === BeginOutcome::Replay) {
            return response()->json($decision->resultEnvelope->value, 200);
        }
        
        try {
            $invoice = Invoice::create($request->validated());
            
            // Build result envelope
            $result = new ResultEnvelope(['invoice_id' => $invoice->id]);
            $idempotency->complete(
                $decision->record->tenantId,
                $decision->record->operationRef,
                $decision->record->clientKey,
                $decision->record->fingerprint,
                $decision->record->attemptToken,
                $result
            );
            
            return response()->json(['invoice' => $invoice], 201);
        } catch (\Throwable $e) {
            $idempotency->fail(
                $decision->record->tenantId,
                $decision->record->operationRef,
                $decision->record->clientKey,
                $decision->record->fingerprint,
                $decision->record->attemptToken,
            );
            throw $e;
        }
    }
}
```

**Note:** The controller needs access to domain VOs (`TenantId`, `OperationRef`, etc.). The adapter should provide a trait or helper to extract these from the request attributes.

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

## 9. Cleanup Strategy

### 9.1 Database Cleanup

A Laravel artisan command for scheduled cleanup:

```bash
php artisan idempotency:cleanup
```

- Deletes records where `expires_at < NOW()`
- Uses chunked deletion for large datasets
- Can be run via Laravel Scheduler daily

### 9.2 Redis TTL

Redis keys automatically expire based on `expires_at` timestamp:
- Set key with TTL on write: `SET key value EX ttl_seconds`
- TTL refreshed on each access

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
│       ├── 2026_03_22_000001_create_nexus_idempotency_records_table.php (MySQL)
│       └── 2026_03_22_000001_create_nexus_idempotency_records_table.php (PostgreSQL)
├── src/
│   ├── Adapters/
│   │   ├── DatabaseIdempotencyStore.php
│   │   └── RedisIdempotencyStore.php (Phase 2)
│   ├── Clock/
│   │   └── LaravelIdempotencyClock.php
│   ├── Http/
│   │   └── IdempotencyMiddleware.php
│   ├── Models/
│   │   └── IdempotencyRecord.php
│   ├── Providers/
│   │   └── IdempotencyAdapterServiceProvider.php
│   ├── Support/
│   │   ├── IdempotencyStoreManager.php
│   │   └── IdempotencyPolicyFactory.php
│   └── Contracts/
│       └── IdempotencyServiceBinderInterface.php (for controller helpers)
├── database/migrations/
│   └── 2026_03_22_000001_create_nexus_idempotency_records_table.php
├── src/Console/
│   └── Commands/
│       └── IdempotencyCleanupCommand.php
└── tests/
    ├── Unit/
    │   ├── DatabaseIdempotencyStoreTest.php
    │   └── LaravelIdempotencyClockTest.php
    └── Integration/
        └── IdempotencyMiddlewareTest.php
```

---

## 12. Controller Helper - Resolved

To bridge between middleware (stores strings) and IdempotencyService (requires VOs), we provide a trait and request object:

### 12.1 IdempotencyRequest Object

**Class:** `Nexus\Laravel\Idempotency\Http\IdempotencyRequest`

```php
final readonly class IdempotencyRequest
{
    public function __construct(
        public TenantId $tenantId,
        public OperationRef $operationRef,
        public ClientKey $clientKey,
        public RequestFingerprint $fingerprint,
        public AttemptToken $attemptToken,
    ) {}
}
```

### 12.2 Trait for Controllers

**Trait:** `Nexus\Laravel\Idempotency\Http\Idempotency`

```php
trait Idempotency
{
    protected function getIdempotencyRequest(Request $request): ?IdempotencyRequest
    {
        return $request->attributes->get('idempotency_request');
    }
}
```

### 12.3 Middleware Update

Middleware creates and stores `IdempotencyRequest` in attributes:

```php
$request->attributes->set('idempotency_request', new IdempotencyRequest(
    $tenantId,           // from auth/header
    $operationRef,       // from route
    $clientKey,          // from Idempotency-Key header
    $fingerprint,        // computed from request
    $decision->record->attemptToken
));
```

### 12.4 Controller Usage (Updated)

```php
use Nexus\Laravel\Idempotency\Http\Idempotency;

class InvoiceController extends Controller
{
    use Idempotency;
    
    public function store(Request $request, IdempotencyServiceInterface $idempotency)
    {
        $idempRequest = $this->getIdempotencyRequest($request);
        
        // Handle replay
        if ($idempRequest === null) {
            return response()->json(['error' => 'No idempotency context'], 400);
        }
        
        // ... business logic ...
        
        $result = new ResultEnvelope(['invoice_id' => $invoice->id]);
        $idempotency->complete(
            $idempRequest->tenantId,
            $idempRequest->operationRef,
            $idempRequest->clientKey,
            $idempRequest->fingerprint,
            $idempRequest->attemptToken,
            $result
        );
    }
}
```

---

## 13. Open Questions (Resolved)

1. ✅ **Controller Helper:** Resolved with `IdempotencyRequest` object + trait
2. ✅ **Redis Phase 2:** Redis store deferred to Phase 2
3. ✅ **Multi-tenancy:** Header-based (X-Tenant-ID), extensible
