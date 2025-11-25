# Getting Started with Nexus\Tenant

This guide will help you quickly integrate the Nexus\Tenant package into your application.

---

## Prerequisites

- PHP 8.3 or higher
- Composer
- PSR-3 compatible logger (e.g., Monolog)
- Database (MySQL, PostgreSQL, SQLite, etc.)
- Cache system (optional but recommended)

---

## Step 1: Installation

Install via Composer:

```bash
composer require nexus/tenant:"*@dev"
```

---

## Step 2: Create Your Tenant Entity

Create a class that implements `TenantInterface`:

```php
<?php

namespace App\Models;

use Nexus\Tenant\Contracts\TenantInterface;

class Tenant implements TenantInterface
{
    public function __construct(
        private string $id,
        private string $code,
        private string $name,
        private string $email,
        private string $status,
        private ?string $domain = null,
        private ?string $subdomain = null,
        private ?string $parentId = null,
        private array $metadata = []
    ) {}
    
    // Implement all interface methods
    public function getId(): string { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getStatus(): string { return $this->status; }
    public function getDomain(): ?string { return $this->domain; }
    public function getSubdomain(): ?string { return $this->subdomain; }
    public function getParentId(): ?string { return $this->parentId; }
    public function getMetadata(): array { return $this->metadata; }
    
    public function isActive(): bool 
    { 
        return $this->status === 'active'; 
    }
    
    public function isSuspended(): bool 
    { 
        return $this->status === 'suspended'; 
    }
    
    public function isTrial(): bool 
    { 
        return $this->status === 'trial'; 
    }
}
```

---

## Step 3: Implement Repository Interfaces

The package uses **Interface Segregation Principle**, so you'll implement three focused interfaces:

### Laravel Example (using Eloquent):

```php
<?php

namespace App\Repositories;

use App\Models\Tenant;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Contracts\TenantValidationInterface;
use Nexus\Tenant\Contracts\TenantInterface;

class TenantRepository implements 
    TenantPersistenceInterface,
    TenantQueryInterface,
    TenantValidationInterface
{
    // WRITE OPERATIONS (TenantPersistenceInterface)
    
    public function create(array $data): TenantInterface
    {
        return Tenant::create($data);
    }
    
    public function update(string $id, array $data): TenantInterface
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($data);
        return $tenant;
    }
    
    public function delete(string $id): bool
    {
        return Tenant::findOrFail($id)->delete();
    }
    
    public function forceDelete(string $id): bool
    {
        return Tenant::withTrashed()->findOrFail($id)->forceDelete();
    }
    
    public function restore(string $id): bool
    {
        return Tenant::withTrashed()->findOrFail($id)->restore();
    }
    
    // READ OPERATIONS (TenantQueryInterface)
    
    public function findById(string $id): ?TenantInterface
    {
        return Tenant::find($id);
    }
    
    public function findByCode(string $code): ?TenantInterface
    {
        return Tenant::where('code', $code)->first();
    }
    
    public function findByDomain(string $domain): ?TenantInterface
    {
        return Tenant::where('domain', $domain)->first();
    }
    
    public function findBySubdomain(string $subdomain): ?TenantInterface
    {
        return Tenant::where('subdomain', $subdomain)->first();
    }
    
    public function all(): array
    {
        return Tenant::all()->all(); // Returns raw array
    }
    
    public function getChildren(string $parentId): array
    {
        return Tenant::where('parent_id', $parentId)->get()->all();
    }
    
    // VALIDATION OPERATIONS (TenantValidationInterface)
    
    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $query = Tenant::where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    public function domainExists(string $domain, ?string $excludeId = null): bool
    {
        $query = Tenant::where('domain', $domain);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
```

---

## Step 4: Implement Cache Adapter

```php
<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Cache;
use Nexus\Tenant\Contracts\CacheRepositoryInterface;

class LaravelCacheAdapter implements CacheRepositoryInterface
{
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }
    
    public function set(string $key, mixed $value, int $ttl): void
    {
        Cache::put($key, $value, $ttl);
    }
    
    public function forget(string $key): void
    {
        Cache::forget($key);
    }
    
    public function flush(): void
    {
        Cache::flush();
    }
}
```

---

## Step 5: Implement Event Dispatcher

```php
<?php

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Event;
use Nexus\Tenant\Contracts\EventDispatcherInterface;

class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): void
    {
        Event::dispatch($event);
    }
}
```

---

## Step 6: Implement Impersonation Storage

```php
<?php

namespace App\Services\Tenant;

use Nexus\Tenant\Contracts\ImpersonationStorageInterface;

class SessionImpersonationStorage implements ImpersonationStorageInterface
{
    public function store(
        string $key,
        string $originalTenantId,
        string $targetTenantId,
        ?string $impersonatorId = null
    ): void {
        session()->put("impersonation.{$key}", [
            'original_tenant_id' => $originalTenantId,
            'target_tenant_id' => $targetTenantId,
            'impersonator_id' => $impersonatorId,
            'started_at' => now(),
        ]);
    }
    
    public function retrieve(string $key): ?array
    {
        return session()->get("impersonation.{$key}");
    }
    
    public function isActive(string $key): bool
    {
        return session()->has("impersonation.{$key}");
    }
    
    public function clear(string $key): void
    {
        session()->forget("impersonation.{$key}");
    }
    
    public function getOriginalTenantId(string $key): ?string
    {
        return session()->get("impersonation.{$key}.original_tenant_id");
    }
    
    public function getTargetTenantId(string $key): ?string
    {
        return session()->get("impersonation.{$key}.target_tenant_id");
    }
}
```

---

## Step 7: Register Services in Service Provider

### Laravel Service Provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Contracts\TenantValidationInterface;
use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Nexus\Tenant\Contracts\EventDispatcherInterface;
use Nexus\Tenant\Contracts\ImpersonationStorageInterface;
use App\Repositories\TenantRepository;
use App\Services\Tenant\LaravelCacheAdapter;
use App\Services\Tenant\LaravelEventDispatcher;
use App\Services\Tenant\SessionImpersonationStorage;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interfaces (same implementation for all three)
        $this->app->singleton(TenantPersistenceInterface::class, TenantRepository::class);
        $this->app->singleton(TenantQueryInterface::class, TenantRepository::class);
        $this->app->singleton(TenantValidationInterface::class, TenantRepository::class);
        
        // Bind cache adapter
        $this->app->singleton(CacheRepositoryInterface::class, LaravelCacheAdapter::class);
        
        // Bind event dispatcher
        $this->app->singleton(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        
        // Bind impersonation storage
        $this->app->singleton(ImpersonationStorageInterface::class, SessionImpersonationStorage::class);
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\TenantServiceProvider::class,
],
```

---

## Step 8: Create Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('email');
            $table->string('domain')->nullable()->unique();
            $table->string('subdomain', 100)->nullable()->unique();
            $table->enum('status', ['pending', 'active', 'trial', 'suspended', 'archived'])
                  ->default('pending');
            $table->ulid('parent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('parent_id');
            
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

Run the migration:

```bash
php artisan migrate
```

---

## Step 9: Basic Usage

### Creating a Tenant

```php
use Nexus\Tenant\Services\TenantLifecycleService;

$lifecycleService = app(TenantLifecycleService::class);

$tenant = $lifecycleService->createTenant(
    code: 'ACME',
    name: 'Acme Corporation',
    email: 'admin@acme.com',
    domain: 'acme.yourapp.com'
);
```

### Setting Tenant Context

```php
use Nexus\Tenant\Services\TenantContextManager;

$contextManager = app(TenantContextManager::class);

// Set current tenant
$contextManager->setTenant('tenant-id-123');

// Get current tenant
$tenant = $contextManager->getCurrentTenant();

// Check if tenant is set
if ($contextManager->hasTenant()) {
    $tenantId = $contextManager->getCurrentTenantId();
}
```

### Resolving Tenant by Domain

```php
use Nexus\Tenant\Services\TenantResolverService;

$resolver = app(TenantResolverService::class);

$tenant = $resolver->resolveByDomain('acme.yourapp.com');
```

---

## Step 10: Listen to Events

Create event listeners for tenant lifecycle events:

```php
<?php

namespace App\Listeners;

use Nexus\Tenant\Events\TenantCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTenantWelcomeEmail implements ShouldQueue
{
    public function handle(TenantCreatedEvent $event): void
    {
        $tenant = $event->tenant;
        
        Mail::to($tenant->getEmail())->send(
            new \App\Mail\WelcomeEmail($tenant)
        );
    }
}
```

Register in `EventServiceProvider`:

```php
use Nexus\Tenant\Events\TenantCreatedEvent;
use App\Listeners\SendTenantWelcomeEmail;

protected $listen = [
    TenantCreatedEvent::class => [
        SendTenantWelcomeEmail::class,
    ],
];
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed method documentation
- Check the [Integration Guide](integration-guide.md) for advanced patterns
- See [Examples](examples/) for working code samples

---

## Troubleshooting

### Issue: "Class TenantInterface not found"
**Solution:** Run `composer dump-autoload` to refresh autoloader.

### Issue: "Tenant context not set"
**Solution:** Ensure middleware sets tenant context before controllers execute.

### Issue: "Cache not working"
**Solution:** Verify `CacheRepositoryInterface` is properly bound in service provider.

---

**Need Help?** See the main [README.md](../README.md) or check the [integration guide](integration-guide.md).
