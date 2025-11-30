# Integration Guide: Nexus\Tenant

This guide demonstrates how to integrate the Tenant package into various PHP frameworks and application architectures.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Middleware Integration](#middleware-integration)
4. [Multi-Database Strategy](#multi-database-strategy)
5. [Event Handling Patterns](#event-handling-patterns)
6. [Advanced Patterns](#advanced-patterns)

---

## Laravel Integration

### Complete Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Tenant\Contracts\{
    TenantPersistenceInterface,
    TenantQueryInterface,
    TenantValidationInterface,
    TenantContextInterface,
    CacheRepositoryInterface,
    EventDispatcherInterface,
    ImpersonationStorageInterface
};
use Nexus\Tenant\Services\{
    TenantLifecycleService,
    TenantContextManager,
    TenantResolverService,
    TenantImpersonationService,
    TenantStatusService
};
use App\Repositories\TenantRepository;
use App\Services\Tenant\{
    LaravelCacheAdapter,
    LaravelEventDispatcher,
    SessionImpersonationStorage
};
use Psr\Log\LoggerInterface;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository interfaces (single implementation for all)
        $this->app->singleton(TenantPersistenceInterface::class, TenantRepository::class);
        $this->app->singleton(TenantQueryInterface::class, TenantRepository::class);
        $this->app->singleton(TenantValidationInterface::class, TenantRepository::class);
        
        // Cache adapter
        $this->app->singleton(CacheRepositoryInterface::class, LaravelCacheAdapter::class);
        
        // Event dispatcher
        $this->app->singleton(EventDispatcherInterface::class, LaravelEventDispatcher::class);
        
        // Impersonation storage
        $this->app->singleton(ImpersonationStorageInterface::class, SessionImpersonationStorage::class);
        
        // Context manager
        $this->app->singleton(TenantContextInterface::class, TenantContextManager::class);
        $this->app->singleton(TenantContextManager::class);
        
        // Domain services
        $this->app->singleton(TenantLifecycleService::class, function ($app) {
            return new TenantLifecycleService(
                persistence: $app->make(TenantPersistenceInterface::class),
                query: $app->make(TenantQueryInterface::class),
                validation: $app->make(TenantValidationInterface::class),
                eventDispatcher: $app->make(EventDispatcherInterface::class),
                logger: $app->make(LoggerInterface::class)
            );
        });
        
        $this->app->singleton(TenantResolverService::class);
        $this->app->singleton(TenantImpersonationService::class);
        $this->app->singleton(TenantStatusService::class);
    }
    
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/tenant.php' => config_path('tenant.php'),
        ], 'tenant-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'tenant-migrations');
    }
}
```

### Eloquent Repository Implementation

```php
<?php

namespace App\Repositories;

use App\Models\Tenant;
use Nexus\Tenant\Contracts\{
    TenantPersistenceInterface,
    TenantQueryInterface,
    TenantValidationInterface,
    TenantInterface
};

class TenantRepository implements 
    TenantPersistenceInterface,
    TenantQueryInterface,
    TenantValidationInterface
{
    // WRITE OPERATIONS
    
    public function create(array $data): TenantInterface
    {
        return Tenant::create($data);
    }
    
    public function update(string $id, array $data): TenantInterface
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($data);
        return $tenant->fresh();
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
    
    // READ OPERATIONS
    
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
        return Tenant::all()->all();
    }
    
    public function getChildren(string $parentId): array
    {
        return Tenant::where('parent_id', $parentId)->get()->all();
    }
    
    // VALIDATION OPERATIONS
    
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

### Middleware for Tenant Resolution

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Tenant\Services\TenantResolverService;
use Nexus\Tenant\Services\TenantContextManager;

class IdentifyTenant
{
    public function __construct(
        private readonly TenantResolverService $resolver,
        private readonly TenantContextManager $contextManager
    ) {}
    
    public function handle(Request $request, Closure $next)
    {
        // Strategy 1: Subdomain
        if ($subdomain = $this->extractSubdomain($request)) {
            $tenant = $this->resolver->resolveBySubdomain($subdomain);
            
            if ($tenant) {
                $this->contextManager->setTenant($tenant->getId());
                return $next($request);
            }
        }
        
        // Strategy 2: Custom domain
        $host = $request->getHost();
        $tenant = $this->resolver->resolveByDomain($host);
        
        if ($tenant) {
            $this->contextManager->setTenant($tenant->getId());
            return $next($request);
        }
        
        // Strategy 3: Header
        if ($tenantCode = $request->header('X-Tenant-Code')) {
            $tenant = $this->resolver->resolveByCode($tenantCode);
            
            if ($tenant) {
                $this->contextManager->setTenant($tenant->getId());
                return $next($request);
            }
        }
        
        // No tenant found
        abort(404, 'Tenant not found');
    }
    
    private function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // If host has subdomain (e.g., acme.yourapp.com)
        if (count($parts) >= 3) {
            return $parts[0];
        }
        
        return null;
    }
}
```

Register middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\IdentifyTenant::class,
    ],
];
```

---

## Symfony Integration

### Service Configuration (services.yaml)

```yaml
services:
    # Repository
    App\Repository\TenantRepository:
        arguments:
            $entityManager: '@doctrine.orm.entity_manager'
    
    # Bind interfaces to repository
    Nexus\Tenant\Contracts\TenantPersistenceInterface:
        alias: App\Repository\TenantRepository
    
    Nexus\Tenant\Contracts\TenantQueryInterface:
        alias: App\Repository\TenantRepository
    
    Nexus\Tenant\Contracts\TenantValidationInterface:
        alias: App\Repository\TenantRepository
    
    # Cache adapter
    Nexus\Tenant\Contracts\CacheRepositoryInterface:
        class: App\Service\Tenant\SymfonyCacheAdapter
        arguments:
            $cache: '@cache.app'
    
    # Event dispatcher
    Nexus\Tenant\Contracts\EventDispatcherInterface:
        class: App\Service\Tenant\SymfonyEventDispatcher
        arguments:
            $dispatcher: '@event_dispatcher'
    
    # Impersonation storage
    Nexus\Tenant\Contracts\ImpersonationStorageInterface:
        class: App\Service\Tenant\SessionImpersonationStorage
        arguments:
            $session: '@session'
    
    # Context manager
    Nexus\Tenant\Services\TenantContextManager:
        arguments:
            $query: '@Nexus\Tenant\Contracts\TenantQueryInterface'
            $cache: '@Nexus\Tenant\Contracts\CacheRepositoryInterface'
            $logger: '@logger'
    
    Nexus\Tenant\Contracts\TenantContextInterface:
        alias: Nexus\Tenant\Services\TenantContextManager
    
    # Services
    Nexus\Tenant\Services\TenantLifecycleService:
        arguments:
            $persistence: '@Nexus\Tenant\Contracts\TenantPersistenceInterface'
            $query: '@Nexus\Tenant\Contracts\TenantQueryInterface'
            $validation: '@Nexus\Tenant\Contracts\TenantValidationInterface'
            $eventDispatcher: '@Nexus\Tenant\Contracts\EventDispatcherInterface'
            $logger: '@logger'
    
    Nexus\Tenant\Services\TenantResolverService: ~
    Nexus\Tenant\Services\TenantImpersonationService: ~
    Nexus\Tenant\Services\TenantStatusService: ~
```

### Doctrine Repository

```php
<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Tenant\Contracts\{
    TenantPersistenceInterface,
    TenantQueryInterface,
    TenantValidationInterface,
    TenantInterface
};

class TenantRepository implements 
    TenantPersistenceInterface,
    TenantQueryInterface,
    TenantValidationInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}
    
    public function create(array $data): TenantInterface
    {
        $tenant = new Tenant();
        // Map data to entity
        $tenant->setCode($data['code']);
        $tenant->setName($data['name']);
        // ... set other properties
        
        $this->entityManager->persist($tenant);
        $this->entityManager->flush();
        
        return $tenant;
    }
    
    public function update(string $id, array $data): TenantInterface
    {
        $tenant = $this->findById($id);
        
        if (!$tenant) {
            throw new \RuntimeException("Tenant not found: {$id}");
        }
        
        // Update properties
        if (isset($data['name'])) {
            $tenant->setName($data['name']);
        }
        // ... update other properties
        
        $this->entityManager->flush();
        
        return $tenant;
    }
    
    public function findById(string $id): ?TenantInterface
    {
        return $this->entityManager->find(Tenant::class, $id);
    }
    
    public function findByCode(string $code): ?TenantInterface
    {
        return $this->entityManager
            ->getRepository(Tenant::class)
            ->findOneBy(['code' => $code]);
    }
    
    // ... implement other methods
}
```

### Event Subscriber

```php
<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Nexus\Tenant\Events\TenantCreatedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TenantEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}
    
    public static function getSubscribedEvents(): array
    {
        return [
            TenantCreatedEvent::class => 'onTenantCreated',
        ];
    }
    
    public function onTenantCreated(TenantCreatedEvent $event): void
    {
        $tenant = $event->tenant;
        
        $email = (new Email())
            ->to($tenant->getEmail())
            ->subject('Welcome to Our Platform')
            ->html("<p>Welcome {$tenant->getName()}!</p>");
        
        $this->mailer->send($email);
    }
}
```

---

## Middleware Integration

### Tenant Identification Middleware (Framework-Agnostic)

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nexus\Tenant\Services\TenantResolverService;
use Nexus\Tenant\Services\TenantContextManager;

class TenantIdentificationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TenantResolverService $resolver,
        private readonly TenantContextManager $contextManager
    ) {}
    
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Resolve tenant
        $tenant = $this->resolveTenant($request);
        
        if ($tenant) {
            $this->contextManager->setTenant($tenant->getId());
            
            // Add tenant to request attributes
            $request = $request->withAttribute('tenant', $tenant);
        }
        
        return $handler->handle($request);
    }
    
    private function resolveTenant(ServerRequestInterface $request): ?TenantInterface
    {
        $uri = $request->getUri();
        $host = $uri->getHost();
        
        // Try subdomain
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            $tenant = $this->resolver->resolveBySubdomain($subdomain);
            if ($tenant) return $tenant;
        }
        
        // Try domain
        $tenant = $this->resolver->resolveByDomain($host);
        if ($tenant) return $tenant;
        
        // Try header
        $headers = $request->getHeader('X-Tenant-Code');
        if (!empty($headers)) {
            $tenant = $this->resolver->resolveByCode($headers[0]);
            if ($tenant) return $tenant;
        }
        
        return null;
    }
}
```

---

## Multi-Database Strategy

### Per-Tenant Database Connection

```php
<?php

namespace App\Services\Tenant;

use Nexus\Tenant\Contracts\TenantInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TenantDatabaseManager
{
    public function switchToTenantDatabase(TenantInterface $tenant): void
    {
        $metadata = $tenant->getMetadata();
        
        if (!isset($metadata['database'])) {
            throw new \RuntimeException('Tenant database configuration not found');
        }
        
        $dbConfig = $metadata['database'];
        
        // Set tenant database connection
        Config::set('database.connections.tenant', [
            'driver' => $dbConfig['driver'] ?? 'mysql',
            'host' => $dbConfig['host'],
            'database' => $dbConfig['database'],
            'username' => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        
        // Purge old connection
        DB::purge('tenant');
        
        // Reconnect
        DB::reconnect('tenant');
        
        // Set as default
        DB::setDefaultConnection('tenant');
    }
    
    public function switchToSystemDatabase(): void
    {
        DB::setDefaultConnection('mysql');
    }
}
```

Usage in middleware:

```php
public function handle(Request $request, Closure $next)
{
    $tenant = $this->resolveTenant($request);
    
    if ($tenant) {
        $this->contextManager->setTenant($tenant->getId());
        $this->databaseManager->switchToTenantDatabase($tenant);
    }
    
    $response = $next($request);
    
    // Cleanup
    $this->databaseManager->switchToSystemDatabase();
    
    return $response;
}
```

---

## Event Handling Patterns

### Async Event Processing with Queues

```php
<?php

namespace App\Listeners;

use Nexus\Tenant\Events\TenantCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProvisionTenantResources implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function handle(TenantCreatedEvent $event): void
    {
        $tenant = $event->tenant;
        
        // Provision database
        $this->createTenantDatabase($tenant);
        
        // Create default admin user
        $this->createAdminUser($tenant);
        
        // Set up default settings
        $this->seedDefaultSettings($tenant);
        
        // Send welcome email
        $this->sendWelcomeEmail($tenant);
    }
    
    private function createTenantDatabase(TenantInterface $tenant): void
    {
        // Implementation
    }
}
```

### Synchronous Event Handling

```php
<?php

namespace App\Listeners;

use Nexus\Tenant\Events\TenantSuspendedEvent;
use App\Services\NotificationService;

class NotifyTenantSuspension
{
    public function __construct(
        private readonly NotificationService $notifier
    ) {}
    
    public function handle(TenantSuspendedEvent $event): void
    {
        $tenant = $event->tenant;
        $reason = $event->reason;
        
        $this->notifier->send(
            to: $tenant->getEmail(),
            subject: 'Account Suspended',
            template: 'tenant.suspended',
            data: [
                'name' => $tenant->getName(),
                'reason' => $reason,
            ]
        );
    }
}
```

---

## Advanced Patterns

### Tenant Scoping in Eloquent Models

Create a global scope for automatic tenant filtering:

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Nexus\Tenant\Services\TenantContextManager;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $contextManager = app(TenantContextManager::class);
        
        if ($contextManager->hasTenant()) {
            $builder->where('tenant_id', $contextManager->getCurrentTenantId());
        }
    }
}
```

Apply to models:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Invoice extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function (Invoice $invoice) {
            $contextManager = app(TenantContextManager::class);
            $invoice->tenant_id = $contextManager->requireTenant();
        });
    }
}
```

### Tenant-Aware Queue Jobs

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Nexus\Tenant\Services\TenantContextManager;

class ProcessInvoice implements ShouldQueue
{
    use InteractsWithQueue, Queueable;
    
    public function __construct(
        private readonly string $invoiceId,
        private readonly string $tenantId
    ) {}
    
    public function handle(TenantContextManager $contextManager): void
    {
        // Set tenant context
        $contextManager->setTenant($this->tenantId);
        
        // Process invoice
        // ... business logic
    }
}
```

### Admin Impersonation Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Nexus\Tenant\Services\TenantImpersonationService;

class TenantImpersonationController
{
    public function __construct(
        private readonly TenantImpersonationService $impersonationService
    ) {}
    
    public function start(Request $request, string $tenantId)
    {
        $this->authorize('impersonate-tenant');
        
        $this->impersonationService->impersonate(
            storageKey: 'admin_session',
            tenantId: $tenantId,
            impersonatorId: auth()->id(),
            reason: $request->input('reason')
        );
        
        return redirect()->route('dashboard')
            ->with('success', 'Now viewing as tenant');
    }
    
    public function stop()
    {
        $this->impersonationService->stopImpersonation('admin_session');
        
        return redirect()->route('admin.tenants.index')
            ->with('success', 'Stopped impersonation');
    }
}
```

---

## Testing Integration

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use Nexus\Tenant\Services\TenantContextManager;

class TenantAwareTest extends TestCase
{
    public function test_invoice_scoped_to_current_tenant(): void
    {
        // Create tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        // Set tenant context
        $contextManager = app(TenantContextManager::class);
        $contextManager->setTenant($tenant1->getId());
        
        // Create invoices
        $invoice1 = Invoice::create([...]); // Belongs to tenant1
        
        // Switch tenant
        $contextManager->setTenant($tenant2->getId());
        $invoice2 = Invoice::create([...]); // Belongs to tenant2
        
        // Query should only return tenant2 invoices
        $this->assertCount(1, Invoice::all());
        $this->assertEquals($invoice2->id, Invoice::first()->id);
    }
}
```

---

**For more details, see:**
- [Getting Started](getting-started.md)
- [API Reference](api-reference.md)
- [Code Examples](examples/)
