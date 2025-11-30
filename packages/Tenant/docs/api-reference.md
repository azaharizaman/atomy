# API Reference: Tenant Package

Complete reference documentation for all public interfaces and services in the Nexus\Tenant package.

---

## Contracts (Interfaces)

### TenantInterface

Defines the contract for a tenant entity.

```php
namespace Nexus\Tenant\Contracts;

interface TenantInterface
{
    public function getId(): string;
    public function getCode(): string;
    public function getName(): string;
    public function getEmail(): string;
    public function getStatus(): string;
    public function getDomain(): ?string;
    public function getSubdomain(): ?string;
    public function getParentId(): ?string;
    public function getMetadata(): array;
    public function isActive(): bool;
    public function isSuspended(): bool;
    public function isTrial(): bool;
}
```

**Methods:**

- **`getId(): string`** - Returns unique tenant identifier (ULID format)
- **`getCode(): string`** - Returns unique alphanumeric tenant code
- **`getName(): string`** - Returns tenant display name
- **`getEmail(): string`** - Returns primary contact email
- **`getStatus(): string`** - Returns current status (pending|active|trial|suspended|archived)
- **`getDomain(): ?string`** - Returns custom domain (e.g., "acme.com")
- **`getSubdomain(): ?string`** - Returns subdomain (e.g., "acme")
- **`getParentId(): ?string`** - Returns parent tenant ID for hierarchical structures
- **`getMetadata(): array`** - Returns tenant metadata as associative array
- **`isActive(): bool`** - Checks if tenant status is 'active'
- **`isSuspended(): bool`** - Checks if tenant status is 'suspended'
- **`isTrial(): bool`** - Checks if tenant status is 'trial'

---

### TenantPersistenceInterface

Write operations only (CQRS Write Model).

```php
namespace Nexus\Tenant\Contracts;

interface TenantPersistenceInterface
{
    public function create(array $data): TenantInterface;
    public function update(string $id, array $data): TenantInterface;
    public function delete(string $id): bool;
    public function forceDelete(string $id): bool;
    public function restore(string $id): bool;
}
```

**Methods:**

- **`create(array $data): TenantInterface`**
  - Creates new tenant
  - **Parameters:** `$data` - Associative array with tenant data
  - **Returns:** Created tenant entity
  - **Throws:** Implementation-specific exceptions

- **`update(string $id, array $data): TenantInterface`**
  - Updates existing tenant
  - **Parameters:** 
    - `$id` - Tenant ID
    - `$data` - Fields to update
  - **Returns:** Updated tenant entity
  - **Throws:** Implementation-specific exceptions

- **`delete(string $id): bool`**
  - Soft deletes tenant
  - **Parameters:** `$id` - Tenant ID
  - **Returns:** Success boolean

- **`forceDelete(string $id): bool`**
  - Permanently deletes tenant
  - **Parameters:** `$id` - Tenant ID
  - **Returns:** Success boolean

- **`restore(string $id): bool`**
  - Restores soft-deleted tenant
  - **Parameters:** `$id` - Tenant ID
  - **Returns:** Success boolean

---

### TenantQueryInterface

Read operations only (CQRS Read Model).

```php
namespace Nexus\Tenant\Contracts;

interface TenantQueryInterface
{
    public function findById(string $id): ?TenantInterface;
    public function findByCode(string $code): ?TenantInterface;
    public function findByDomain(string $domain): ?TenantInterface;
    public function findBySubdomain(string $subdomain): ?TenantInterface;
    public function all(): array;
    public function getChildren(string $parentId): array;
}
```

**Methods:**

- **`findById(string $id): ?TenantInterface`**
  - Finds tenant by ID
  - **Returns:** Tenant or null if not found

- **`findByCode(string $code): ?TenantInterface`**
  - Finds tenant by unique code
  - **Returns:** Tenant or null if not found

- **`findByDomain(string $domain): ?TenantInterface`**
  - Finds tenant by custom domain
  - **Returns:** Tenant or null if not found

- **`findBySubdomain(string $subdomain): ?TenantInterface`**
  - Finds tenant by subdomain
  - **Returns:** Tenant or null if not found

- **`all(): array`**
  - Retrieves all tenants
  - **Returns:** Array of `TenantInterface` objects
  - **Note:** No pagination (raw collection). Application layer handles pagination.

- **`getChildren(string $parentId): array`**
  - Retrieves child tenants
  - **Parameters:** `$parentId` - Parent tenant ID
  - **Returns:** Array of child `TenantInterface` objects

---

### TenantValidationInterface

Validation operations only.

```php
namespace Nexus\Tenant\Contracts;

interface TenantValidationInterface
{
    public function codeExists(string $code, ?string $excludeId = null): bool;
    public function domainExists(string $domain, ?string $excludeId = null): bool;
}
```

**Methods:**

- **`codeExists(string $code, ?string $excludeId = null): bool`**
  - Checks if tenant code exists
  - **Parameters:**
    - `$code` - Code to check
    - `$excludeId` - (Optional) Tenant ID to exclude from check
  - **Returns:** True if exists

- **`domainExists(string $domain, ?string $excludeId = null): bool`**
  - Checks if domain is already assigned
  - **Parameters:**
    - `$domain` - Domain to check
    - `$excludeId` - (Optional) Tenant ID to exclude from check
  - **Returns:** True if exists

---

### TenantContextInterface

Manages current tenant context within request/process.

```php
namespace Nexus\Tenant\Contracts;

interface TenantContextInterface
{
    public function setTenant(string $tenantId): void;
    public function getCurrentTenantId(): ?string;
    public function hasTenant(): bool;
    public function getCurrentTenant(): ?TenantInterface;
    public function clearTenant(): void;
    public function requireTenant(): string;
}
```

**Methods:**

- **`setTenant(string $tenantId): void`**
  - Sets active tenant context
  - **Throws:** 
    - `TenantNotFoundException` - If tenant doesn't exist
    - `TenantSuspendedException` - If tenant is suspended

- **`getCurrentTenantId(): ?string`**
  - Gets current tenant ID
  - **Returns:** Tenant ID or null

- **`hasTenant(): bool`**
  - Checks if tenant context is set
  - **Returns:** Boolean

- **`getCurrentTenant(): ?TenantInterface`**
  - Gets current tenant entity
  - **Returns:** Tenant or null

- **`clearTenant(): void`**
  - Clears tenant context

- **`requireTenant(): string`**
  - Requires tenant context to be set
  - **Returns:** Current tenant ID
  - **Throws:** `TenantContextNotSetException`

---

### EventDispatcherInterface

Dispatches events to application layer.

```php
namespace Nexus\Tenant\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
```

**Methods:**

- **`dispatch(object $event): void`**
  - Dispatches event to listeners
  - **Parameters:** `$event` - Event value object

---

### ImpersonationStorageInterface

External storage for impersonation state.

```php
namespace Nexus\Tenant\Contracts;

interface ImpersonationStorageInterface
{
    public function store(
        string $key,
        string $originalTenantId,
        string $targetTenantId,
        ?string $impersonatorId = null
    ): void;
    
    public function retrieve(string $key): ?array;
    public function isActive(string $key): bool;
    public function clear(string $key): void;
    public function getOriginalTenantId(string $key): ?string;
    public function getTargetTenantId(string $key): ?string;
}
```

**Methods:**

- **`store()`** - Stores impersonation context
- **`retrieve()`** - Retrieves impersonation data
- **`isActive()`** - Checks if impersonation is active
- **`clear()`** - Clears impersonation
- **`getOriginalTenantId()`** - Gets original tenant ID
- **`getTargetTenantId()`** - Gets target tenant ID

---

### CacheRepositoryInterface

Cache abstraction.

```php
namespace Nexus\Tenant\Contracts;

interface CacheRepositoryInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): void;
    public function forget(string $key): void;
    public function flush(): void;
}
```

---

## Services

### TenantLifecycleService

Manages tenant CRUD operations and lifecycle.

```php
namespace Nexus\Tenant\Services;

final readonly class TenantLifecycleService
{
    public function __construct(
        private TenantPersistenceInterface $persistence,
        private TenantQueryInterface $query,
        private TenantValidationInterface $validation,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger()
    ) {}
}
```

**Methods:**

- **`createTenant(string $code, string $name, string $email, ?string $domain, array $additionalData): TenantInterface`**
  - Creates new tenant with validation
  - **Throws:** `DuplicateTenantCodeException`, `DuplicateTenantDomainException`
  - **Dispatches:** `TenantCreatedEvent`

- **`activateTenant(string $tenantId): TenantInterface`**
  - Activates pending tenant
  - **Throws:** `TenantNotFoundException`
  - **Dispatches:** `TenantActivatedEvent`

- **`suspendTenant(string $tenantId, ?string $reason): TenantInterface`**
  - Suspends active tenant
  - **Throws:** `TenantNotFoundException`
  - **Dispatches:** `TenantSuspendedEvent`

- **`reactivateTenant(string $tenantId): TenantInterface`**
  - Reactivates suspended tenant
  - **Throws:** `TenantNotFoundException`
  - **Dispatches:** `TenantReactivatedEvent`

- **`archiveTenant(string $tenantId, ?string $reason): bool`**
  - Soft deletes tenant
  - **Throws:** `TenantNotFoundException`
  - **Dispatches:** `TenantArchivedEvent`

- **`deleteTenant(string $tenantId): bool`**
  - Permanently deletes tenant
  - **Returns:** Success boolean

- **`updateTenant(string $tenantId, array $data): TenantInterface`**
  - Updates tenant with validation
  - **Throws:** `TenantNotFoundException`, `DuplicateTenantCodeException`, `DuplicateTenantDomainException`
  - **Dispatches:** `TenantUpdatedEvent`

---

### TenantContextManager

Manages request-scoped tenant context.

```php
namespace Nexus\Tenant\Services;

final class TenantContextManager implements TenantContextInterface
{
    public function __construct(
        private readonly TenantQueryInterface $query,
        private readonly CacheRepositoryInterface $cache,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {}
}
```

**Additional Methods:**

- **`refreshTenantCache(string $tenantId): void`**
  - Invalidates and refreshes tenant cache

- **`clearAllTenantCaches(): void`**
  - Clears all tenant caches

---

### TenantResolverService

Multi-strategy tenant identification.

```php
namespace Nexus\Tenant\Services;

final readonly class TenantResolverService
{
    public function __construct(
        private TenantQueryInterface $query,
        private CacheRepositoryInterface $cache,
        private LoggerInterface $logger = new NullLogger()
    ) {}
}
```

**Methods:**

- **`resolveByDomain(string $domain): ?TenantInterface`**
  - Resolves tenant by full domain

- **`resolveBySubdomain(string $subdomain): ?TenantInterface`**
  - Resolves tenant by subdomain

- **`resolveByCode(string $code): ?TenantInterface`**
  - Resolves tenant by code

---

### TenantImpersonationService

Secure tenant impersonation for support staff.

```php
namespace Nexus\Tenant\Services;

final readonly class TenantImpersonationService
{
    public function __construct(
        private TenantQueryInterface $tenantQuery,
        private TenantContextManager $contextManager,
        private ImpersonationStorageInterface $storage,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger()
    ) {}
}
```

**Methods:**

- **`impersonate(string $storageKey, string $tenantId, string $impersonatorId, ?string $reason): void`**
  - Starts impersonation
  - **Throws:** `TenantNotFoundException`
  - **Dispatches:** `ImpersonationStartedEvent`

- **`stopImpersonation(string $storageKey): void`**
  - Stops impersonation and restores context
  - **Dispatches:** `ImpersonationEndedEvent`

- **`isImpersonating(string $storageKey): bool`**
  - Checks if currently impersonating

- **`getImpersonatedTenantId(string $storageKey): ?string`**
  - Gets impersonated tenant ID

- **`getOriginalTenantId(string $storageKey): ?string`**
  - Gets original tenant ID

---

### TenantStatusService

Domain service for business logic filtering.

```php
namespace Nexus\Tenant\Services;

final readonly class TenantStatusService
{
    public function __construct(
        private TenantQueryInterface $query
    ) {}
}
```

**Methods:**

- **`getActiveTenants(): array`**
  - Returns all active tenants

- **`getSuspendedTenants(): array`**
  - Returns all suspended tenants

- **`getTrialTenants(): array`**
  - Returns all trial tenants

- **`getPendingTenants(): array`**
  - Returns all pending tenants

- **`getExpiredTrials(): array`**
  - Returns tenants with expired trials

- **`getStatistics(): array`**
  - Returns tenant count statistics by status

---

## Enums

### TenantStatus

```php
namespace Nexus\Tenant\Enums;

enum TenantStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Trial = 'trial';
    case Suspended = 'suspended';
    case Archived = 'archived';
    
    public function canTransitionTo(self $newStatus): bool;
}
```

**Valid Transitions:**
- Pending → Active, Trial, Archived
- Active → Suspended, Archived
- Trial → Active, Suspended, Archived
- Suspended → Active, Archived
- Archived → (Cannot transition)

---

### IdentificationStrategy

```php
namespace Nexus\Tenant\Enums;

enum IdentificationStrategy: string
{
    case Domain = 'domain';
    case Subdomain = 'subdomain';
    case Header = 'header';
    case Path = 'path';
    case Session = 'session';
}
```

---

## Events

All events are immutable `readonly` value objects:

- **`TenantCreatedEvent`** - `public readonly TenantInterface $tenant`
- **`TenantActivatedEvent`** - `public readonly TenantInterface $tenant`
- **`TenantSuspendedEvent`** - `public readonly TenantInterface $tenant, public readonly ?string $reason`
- **`TenantReactivatedEvent`** - `public readonly TenantInterface $tenant`
- **`TenantArchivedEvent`** - `public readonly TenantInterface $tenant, public readonly ?string $reason`
- **`TenantDeletedEvent`** - `public readonly TenantInterface $tenant, public readonly bool $force`
- **`TenantUpdatedEvent`** - `public readonly TenantInterface $tenant, public readonly array $changes`
- **`ImpersonationStartedEvent`** - `public readonly TenantInterface $originalTenant, $targetTenant, $impersonatorId`
- **`ImpersonationEndedEvent`** - `public readonly TenantInterface $targetTenant, $restoredTenant, $impersonatorId`

---

## Exceptions

All exceptions extend PHP base exceptions:

- **`TenantNotFoundException`** - Tenant not found
- **`TenantContextNotSetException`** - Tenant context required but not set
- **`TenantSuspendedException`** - Attempted access to suspended tenant
- **`DuplicateTenantCodeException`** - Tenant code already exists
- **`DuplicateTenantDomainException`** - Domain already assigned
- **`InvalidTenantStatusTransitionException`** - Invalid status transition
- **`ImpersonationNotAllowedException`** - Impersonation not permitted

---

**For more examples, see:**
- [Getting Started Guide](getting-started.md)
- [Integration Guide](integration-guide.md)
- [Code Examples](examples/)
