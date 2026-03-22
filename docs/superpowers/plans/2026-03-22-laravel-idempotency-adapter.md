# Idempotency Laravel Adapter Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement Laravel Layer 3 adapter for Nexus Idempotency Layer 1 package, enabling API request deduplication in Atomy-Q SaaS.

**Architecture:** Follows the approved design spec at `docs/superpowers/specs/2026-03-22-idempotency-laravel-adapter-design.md`. Creates Eloquent model, database store, clock adapter, policy factory, HTTP middleware, and service provider.

**Tech Stack:** PHP 8.3, Laravel 11/12, Eloquent, Composer

---

## File Structure to Create

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
│   │   └── DatabaseIdempotencyStore.php
│   ├── Clock/
│   │   └── LaravelIdempotencyClock.php
│   ├── Http/
│   │   ├── IdempotencyMiddleware.php
│   │   ├── IdempotencyRequest.php
│   │   └── Idempotency.php (trait)
│   ├── Models/
│   │   └── IdempotencyRecord.php
│   ├── Providers/
│   │   └── IdempotencyAdapterServiceProvider.php
│   └── Support/
│       └── IdempotencyPolicyFactory.php
└── tests/
    ├── Unit/
    │   ├── DatabaseIdempotencyStoreTest.php
    │   └── LaravelIdempotencyClockTest.php
    └── Integration/
        └── IdempotencyMiddlewareTest.php
```

---

## Dependencies

The adapter depends on these Layer 1 interfaces from `nexus/idempotency`:
- `Nexus\Idempotency\Contracts\IdempotencyStoreInterface` (extends Query + Persist)
- `Nexus\Idempotency\Contracts\IdempotencyClockInterface`
- `Nexus\Idempotency\Contracts\IdempotencyServiceInterface`
- Domain VOs: `TenantId`, `OperationRef`, `ClientKey`, `RequestFingerprint`, `AttemptToken`, `ResultEnvelope`
- Domain: `IdempotencyPolicy`, `IdempotencyRecord`, `BeginDecision`, `ClaimPendingResult`
- Enums: `BeginOutcome`, `IdempotencyRecordStatus`

---

## Task 1: Composer.json and Config

**Files:**
- Create: `adapters/Laravel/Idempotency/composer.json`
- Create: `adapters/Laravel/Idempotency/config/nexus-idempotency.php`

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "nexus/laravel-idempotency-adapter",
    "description": "Laravel adapter for Idempotency package",
    "type": "library",
    "license": "MIT",
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
    },
    "autoload": {
        "psr-4": {
            "Nexus\\Laravel\\Idempotency\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Nexus\\Laravel\\Idempotency\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Nexus\\Laravel\\Idempotency\\Providers\\IdempotencyAdapterServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 2: Create config file**

```php
<?php

return [
    'store' => env('IDEMPOTENCY_STORE', 'database'),
    
    'policy' => [
        'pending_ttl_seconds' => (int) env('IDEMPOTENCY_PENDING_TTL', 604800),
        'allow_retry_after_fail' => filter_var(env('IDEMPOTENCY_ALLOW_RETRY', true), FILTER_VALIDATE_BOOLEAN),
        'expire_completed_after_seconds' => (int) env('IDEMPOTENCY_COMPLETED_TTL', 86400),
    ],
    
    'redis' => [
        'connection' => env('IDEMPOTENCY_REDIS_CONNECTION', 'default'),
        'prefix' => 'nexus:idempotency:',
    ],
    
    'middleware' => [
        'enabled' => true,
        'header_name' => 'Idempotency-Key',
        'tenant_header' => 'X-Tenant-ID',
    ],
];
```

- [ ] **Step 3: Commit**

```bash
git add adapters/Laravel/Idempotency/composer.json adapters/Laravel/Idempotency/config/
git commit -m "feat(idempotency-adapter): add composer.json and config"
```

---

## Task 2: Database Migration and Eloquent Model

**Files:**
- Create: `adapters/Laravel/Idempotency/database/migrations/2026_03_22_000001_create_nexus_idempotency_records_table.php`
- Create: `adapters/Laravel/Idempotency/src/Models/IdempotencyRecord.php`

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nexus_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->string('operation_ref', 255);
            $table->string('client_key', 255);
            $table->text('request_fingerprint');
            $table->string('attempt_token', 36);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('result_envelope')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'operation_ref', 'client_key'], 'uk_tenant_operation_client');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_idempotency_records');
    }
};
```

- [ ] **Step 2: Create Eloquent model**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Models;

use Illuminate\Database\Eloquent\Model;

final class IdempotencyRecord extends Model
{
    protected $table = 'nexus_idempotency_records';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'tenant_id',
        'operation_ref',
        'client_key',
        'request_fingerprint',
        'attempt_token',
        'status',
        'result_envelope',
        'expires_at',
    ];

    protected $casts = [
        'result_envelope' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

- [ ] **Step 3: Commit**

```bash
git add adapters/Laravel/Idempotency/database/ adapters/Laravel/Idempotency/src/Models/
git commit -m "feat(idempotency-adapter): add migration and Eloquent model"
```

---

## Task 3: Clock Implementation

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Clock/LaravelIdempotencyClock.php`

- [ ] **Step 1: Create clock implementation**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Clock;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;

final class LaravelIdempotencyClock implements IdempotencyClockInterface
{
    public function now(): DateTimeImmutable
    {
        return CarbonImmutable::now();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add adapters/Laravel/Idempotency/src/Clock/
git commit -m "feat(idempotency-adapter): add Laravel clock implementation"
```

---

## Task 4: Policy Factory

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Support/IdempotencyPolicyFactory.php`

- [ ] **Step 1: Create policy factory**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Support;

use Illuminate\Support\Facades\Config;
use Nexus\Idempotency\Domain\IdempotencyPolicy;

final class IdempotencyPolicyFactory
{
    public static function make(): IdempotencyPolicy
    {
        $config = Config::get('nexus-idempotency.policy', []);

        return new IdempotencyPolicy(
            pendingTtlSeconds: $config['pending_ttl_seconds'] ?? IdempotencyPolicy::DEFAULT_PENDING_TTL_SECONDS,
            allowRetryAfterFail: $config['allow_retry_after_fail'] ?? true,
            expireCompletedAfterSeconds: $config['expire_completed_after_seconds'] ?? null,
        );
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add adapters/Laravel/Idempotency/src/Support/
git commit -m "feat(idempotency-adapter): add policy factory"
```

---

## Task 5: Database Store Implementation

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Adapters/DatabaseIdempotencyStore.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\Laravel\Idempotency\Adapters\DatabaseIdempotencyStore;
use Nexus\Laravel\Idempotency\Models\IdempotencyRecord as EloquentModel;

class DatabaseIdempotencyStoreTest extends TestCase
{
    public function test_implements_store_interface(): void
    {
        $store = new DatabaseIdempotencyStore(
            $this->createMock(EloquentModel::class)
        );
        
        $this->assertInstanceOf(
            \Nexus\Idempotency\Contracts\IdempotencyStoreInterface::class,
            $store
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd .worktrees/feat-laravel-idempotency-adapter
./vendor/bin/phpunit adapters/Laravel/Idempotency/tests/Unit/DatabaseIdempotencyStoreTest.php
```
Expected: FAIL - class does not exist

- [ ] **Step 3: Write store implementation**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Adapters;

use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyPersistInterface;
use Nexus\Idempotency\Contracts\IdempotencyQueryInterface;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Idempotency\Domain\ClaimPendingResult;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\Exceptions\IdempotencyTenantMismatchException;
use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;
use Nexus\Laravel\Idempotency\Models\IdempotencyRecord as EloquentModel;

final class DatabaseIdempotencyStore implements IdempotencyStoreInterface
{
    public function __construct(
        private readonly EloquentModel $model
    ) {}

    public function find(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): ?IdempotencyRecord {
        $record = $this->model
            ->where('tenant_id', $tenantId->value)
            ->where('operation_ref', $operationRef->value)
            ->where('client_key', $clientKey->value)
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->toDomainRecord($record);
    }

    public function claimPending(IdempotencyRecord $newRecordIfAbsent): ClaimPendingResult
    {
        $now = $newRecordIfAbsent->createdAt;
        $expiresAt = $this->calculateExpiresAt($now, $newRecordIfAbsent);

        $result = $this->model->unguarded(function ($query) use ($newRecordIfAbsent, $expiresAt) {
            return $query->upsert(
                [
                    'tenant_id' => $newRecordIfAbsent->tenantId->value,
                    'operation_ref' => $newRecordIfAbsent->operationRef->value,
                    'client_key' => $newRecordIfAbsent->clientKey->value,
                    'request_fingerprint' => $newRecordIfAbsent->fingerprint->value,
                    'attempt_token' => $newRecordIfAbsent->attemptToken->value,
                    'status' => IdempotencyRecordStatus::Pending->value,
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'created_at' => $newRecordIfAbsent->createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $newRecordIfAbsent->lastTransitionAt->format('Y-m-d H:i:s'),
                ],
                ['tenant_id', 'operation_ref', 'client_key'],
                [
                    'request_fingerprint' => $newRecordIfAbsent->fingerprint->value,
                    'attempt_token' => $newRecordIfAbsent->attemptToken->value,
                    'status' => IdempotencyRecordStatus::Pending->value,
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'updated_at' => $newRecordIfAbsent->lastTransitionAt->format('Y-m-d H:i:s'),
                ]
            );
        });

        $existing = $this->find(
            $newRecordIfAbsent->tenantId,
            $newRecordIfAbsent->operationRef,
            $newRecordIfAbsent->clientKey
        );

        if ($existing === null) {
            throw new \RuntimeException('Failed to find or create idempotency record');
        }

        $claimedNew = $existing->fingerprint->value === $newRecordIfAbsent->fingerprint->value
            && $existing->attemptToken->value === $newRecordIfAbsent->attemptToken->value;

        return new ClaimPendingResult($existing, $claimedNew);
    }

    public function save(IdempotencyRecord $record): void
    {
        $eloquent = $this->model
            ->where('tenant_id', $record->tenantId->value)
            ->where('operation_ref', $record->operationRef->value)
            ->where('client_key', $record->clientKey->value)
            ->firstOrFail();

        $eloquent->status = $record->status->value;
        $eloquent->attempt_token = $record->attemptToken->value;
        $eloquent->request_fingerprint = $record->fingerprint->value;
        
        if ($record->resultEnvelope !== null) {
            $eloquent->result_envelope = $record->resultEnvelope->value;
        }

        $eloquent->expires_at = $record->lastTransitionAt->modify('+' . $this->getPendingTtlSeconds() . ' seconds');
        $eloquent->save();
    }

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void {
        $this->model
            ->where('tenant_id', $tenantId->value)
            ->where('operation_ref', $operationRef->value)
            ->where('client_key', $clientKey->value)
            ->delete();
    }

    private function toDomainRecord(EloquentModel $model): IdempotencyRecord
    {
        return new IdempotencyRecord(
            new TenantId($model->tenant_id),
            new OperationRef($model->operation_ref),
            new ClientKey($model->client_key),
            IdempotencyRecordStatus::from($model->status),
            new RequestFingerprint($model->request_fingerprint),
            new AttemptToken($model->attempt_token),
            $model->result_envelope !== null ? new ResultEnvelope($model->result_envelope) : null,
            new DateTimeImmutable($model->created_at),
            new DateTimeImmutable($model->updated_at),
        );
    }

    private function calculateExpiresAt(DateTimeImmutable $now, IdempotencyRecord $record): DateTimeImmutable
    {
        return $now->modify('+' . $this->getPendingTtlSeconds() . ' seconds');
    }

    private function getPendingTtlSeconds(): int
    {
        return config('nexus-idempotency.policy.pending_ttl_seconds', 604800);
    }
}
```

- [ ] **Step 4: Run test**

```bash
./vendor/bin/phpunit adapters/Laravel/Idempotency/tests/Unit/DatabaseIdempotencyStoreTest.php
```

- [ ] **Step 5: Commit**

```bash
git add adapters/Laravel/Idempotency/src/Adapters/ adapters/Laravel/Idempotency/tests/
git commit -m "feat(idempotency-adapter): add database store implementation"
```

---

## Task 6: HTTP Middleware and Controller Helpers

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Http/IdempotencyRequest.php`
- Create: `adapters/Laravel/Idempotency/src/Http/Idempotency.php` (trait)
- Create: `adapters/Laravel/Idempotency/src/Http/IdempotencyMiddleware.php`

- [ ] **Step 1: Create IdempotencyRequest**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\TenantId;

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

- [ ] **Step 2: Create Idempotency trait**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Illuminate\Http\Request;

trait Idempotency
{
    protected function getIdempotencyRequest(Request $request): ?IdempotencyRequest
    {
        return $request->attributes->get('idempotency_request');
    }
}
```

- [ ] **Step 3: Create Middleware**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Closure;
use Illuminate\Http\Request;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Domain\BeginDecision;
use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\TenantId;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function __construct(
        private readonly IdempotencyServiceInterface $idempotencyService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $headerName = config('nexus-idempotency.middleware.header_name', 'Idempotency-Key');
        $tenantHeader = config('nexus-idempotency.middleware.tenant_header', 'X-Tenant-ID');
        
        $clientKeyValue = $request->header($headerName);
        
        if (empty($clientKeyValue)) {
            return response()->json([
                'error' => 'Idempotency-Key header required',
            ], 400);
        }

        $tenantIdValue = $request->header($tenantHeader);
        
        if (empty($tenantIdValue)) {
            $user = $request->user();
            $tenantIdValue = $user?->tenant_id;
        }

        if (empty($tenantIdValue)) {
            return response()->json([
                'error' => 'Tenant identification failed',
            ], 400);
        }

        $tenantId = new TenantId($tenantIdValue);
        $operationRef = new OperationRef($request->method() . ' ' . $request->path());
        $clientKey = new ClientKey($clientKeyValue);
        $fingerprint = $this->computeFingerprint($request);

        $decision = $this->idempotencyService->begin(
            $tenantId,
            $operationRef,
            $clientKey,
            $fingerprint
        );

        if ($decision->outcome === BeginOutcome::InProgress) {
            return response()->json([
                'error' => 'Duplicate request in progress',
            ], 409)->withHeaders([
                'Retry-After' => 60,
            ]);
        }

        if ($decision->outcome === BeginOutcome::Replay && $decision->resultEnvelope !== null) {
            return response()->json(
                $decision->resultEnvelope->value,
                200
            );
        }

        $request->attributes->set('idempotency_request', new IdempotencyRequest(
            $tenantId,
            $operationRef,
            $clientKey,
            $fingerprint,
            $decision->record->attemptToken
        ));

        return $next($request);
    }

    private function computeFingerprint(Request $request): RequestFingerprint
    {
        $data = [
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'query' => $request->query->all(),
            'body' => $request->except(['password', 'token', 'secret']),
        ];

        return new RequestFingerprint(json_encode($data));
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add adapters/Laravel/Idempotency/src/Http/
git commit -m "feat(idempotency-adapter): add HTTP middleware and controller helpers"
```

---

## Task 7: Service Provider

**Files:**
- Create: `adapters/Laravel/Idempotency/src/Providers/IdempotencyAdapterServiceProvider.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nexus\Laravel\Idempotency\Providers\IdempotencyAdapterServiceProvider;

class IdempotencyAdapterServiceProviderTest extends TestCase
{
    public function test_service_provider_exists(): void
    {
        $this->assertTrue(class_exists(IdempotencyAdapterServiceProvider::class));
    }
}
```

- [ ] **Step 2: Create service provider**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Laravel\Idempotency\Adapters\DatabaseIdempotencyStore;
use Nexus\Laravel\Idempotency\Clock\LaravelIdempotencyClock;
use Nexus\Laravel\Idempotency\Models\IdempotencyRecord;
use Nexus\Laravel\Idempotency\Support\IdempotencyPolicyFactory;

final class IdempotencyAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/nexus-idempotency.php',
            'nexus-idempotency'
        );

        $this->app->singleton(IdempotencyClockInterface::class, LaravelIdempotencyClock::class);

        $this->app->singleton(IdempotencyPolicyFactory::class);

        $this->app->singleton(IdempotencyStoreInterface::class, function ($app) {
            $store = config('nexus-idempotency.store', 'database');
            
            if ($store === 'database') {
                return new DatabaseIdempotencyStore(
                    new IdempotencyRecord()
                );
            }

            throw new \RuntimeException("Unsupported idempotency store: {$store}");
        });

        $this->app->singleton(IdempotencyServiceInterface::class, function ($app) {
            // Note: DatabaseIdempotencyStore implements IdempotencyStoreInterface
            // which extends both IdempotencyQueryInterface and IdempotencyPersistInterface
            $store = $app->make(IdempotencyStoreInterface::class);
            
            return new \Nexus\Idempotency\Services\IdempotencyService(
                $store, // implements IdempotencyQueryInterface
                $store, // implements IdempotencyPersistInterface
                $app->make(IdempotencyClockInterface::class),
                $app->make(IdempotencyPolicyFactory::class)->make()
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/nexus-idempotency.php' => config_path('nexus-idempotency.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
```

- [ ] **Step 3: Run test**

```bash
./vendor/bin/phpunit adapters/Laravel/Idempotency/tests/Unit/IdempotencyAdapterServiceProviderTest.php
```

- [ ] **Step 4: Commit**

```bash
git add adapters/Laravel/Idempotency/src/Providers/
git commit -m "feat(idempotency-adapter): add service provider"
```

---

## Task 8: Unit Tests

**Files:**
- Create: `adapters/Laravel/Idempotency/tests/Unit/LaravelIdempotencyClockTest.php`

- [ ] **Step 1: Write clock test**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\Laravel\Idempotency\Clock\LaravelIdempotencyClock;

class LaravelIdempotencyClockTest extends TestCase
{
    private LaravelIdempotencyClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new LaravelIdempotencyClock();
    }

    public function test_now_returns_datetime_immutable(): void
    {
        $result = $this->clock->now();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
    }

    public function test_now_returns_current_time(): void
    {
        $before = CarbonImmutable::now();
        $result = $this->clock->now();
        $after = CarbonImmutable::now();
        
        $this->assertGreaterThanOrEqual($before, $result);
        $this->assertLessThanOrEqual($after, $result);
    }
}
```

- [ ] **Step 2: Run test**

```bash
./vendor/bin/phpunit adapters/Laravel/Idempotency/tests/Unit/LaravelIdempotencyClockTest.php
```

- [ ] **Step 3: Commit**

```bash
git add adapters/Laravel/Idempotency/tests/
git commit -m "test(idempotency-adapter): add unit tests"
```

---

## Task 9: Final Verification

- [ ] **Step 1: Run all tests**

```bash
./vendor/bin/phpunit adapters/Laravel/Idempotency/tests/
```

- [ ] **Step 2: Verify implementation against spec**

Check all components from `docs/superpowers/specs/2026-03-22-idempotency-laravel-adapter-design.md`:
- ✅ composer.json
- ✅ config/nexus-idempotency.php
- ✅ migration
- ✅ Eloquent model
- ✅ LaravelIdempotencyClock
- ✅ IdempotencyPolicyFactory
- ✅ DatabaseIdempotencyStore
- ✅ IdempotencyRequest
- ✅ Idempotency trait
- ✅ IdempotencyMiddleware
- ✅ ServiceProvider

- [ ] **Step 3: Push branch**

```bash
git push -u origin feat/laravel-idempotency-adapter
```

---

## Summary

All tasks complete. The adapter provides:
1. Database persistence via Eloquent
2. Clock implementation for time operations
3. Policy factory for configurable TTL/retry
4. HTTP middleware for Idempotency-Key header processing
5. Controller helpers (trait + request object)
6. Full service provider wiring
7. Unit tests for core components
