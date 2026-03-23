# Registration, Tenant Creation & User Verification Lifecycle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement end-to-end self-service registration flow with tenant creation, email verification, invite-based team joining, admin delegation, and user unregistration with data retention policies.

**Architecture:** 
- **Phase 1:** Add generic multi-tenant concepts to Nexus Layer 1 packages (TenantStatus::QueuedDeletion, retention_hold_until, nameExists validation)
- **Phase 2:** Complete Laravel adapter bindings for Identity package (prerequisite)
- **Phase 3:** Build Atomy-Q specific services in orchestrators (IdentityOperations)
- **Phase 4:** Build Laravel scheduled jobs for purge operations

**Tech Stack:** PHP 8.3, Laravel, Nexus packages (Identity, Tenant, Crypto, Notifier, Idempotency)

---

## Phase 1: Layer 1 Enhancements (Nexus Packages)

### Task 1.1: Add QUEUED_DELETION to TenantStatus Enum

**Files:**
- Modify: `packages/Tenant/src/Enums/TenantStatus.php:16-22`

- [ ] **Step 1: Add QUEUED_DELETION case**

```php
enum TenantStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
    case Trial = 'trial';
    case QueuedDeletion = 'queued_deletion';  // NEW
```

- [ ] **Step 2: Add isQueuedDeletion() method**

```php
public function isQueuedDeletion(): bool
{
    return $this === self::QueuedDeletion;
}
```

- [ ] **Step 3: Update canTransitionTo() to include QUEUED_DELETION**

```php
$transitions = [
    self::Pending->value => [self::Active->value, self::Archived->value, self::Trial->value, self::QueuedDeletion->value],
    self::Active->value => [self::Suspended->value, self::Archived->value, self::QueuedDeletion->value],
    self::Suspended->value => [self::Active->value, self::Archived->value, self::QueuedDeletion->value],
    self::Trial->value => [self::Active->value, self::Suspended->value, self::Archived->value, self::QueuedDeletion->value],
    self::QueuedDeletion->value => [self::Active->value], // Can reactivate within retention hold
    self::Archived->value => [], // Cannot transition from archived
];
```

- [ ] **Step 4: Run tests**

Run: `cd packages/Tenant && ./vendor/bin/phpunit`
Expected: PASS

---

### Task 1.2: Add retention_hold_until to TenantInterface

**Files:**
- Modify: `packages/Tenant/src/Contracts/TenantInterface.php`

- [ ] **Step 1: Add getRetentionHoldUntil method**

Add after line 241 (after getDeletedAt):

```php
/**
 * Get the retention hold timestamp (for queued deletion).
 *
 * @return \DateTimeInterface|null
 */
public function getRetentionHoldUntil(): ?\DateTimeInterface;
```

- [ ] **Step 2: Add isQueuedForDeletion method**

```php
/**
 * Check if tenant is queued for deletion.
 *
 * @return bool
 */
public function isQueuedForDeletion(): bool;
```

- [ ] **Step 3: Run PHPStan**

Run: `cd packages/Tenant && ./vendor/bin/phpstan analyse src/Contracts/TenantInterface.php --level=max`
Expected: No errors

---

### Task 1.3: Add nameExists to TenantValidationInterface

**Files:**
- Modify: `packages/Tenant/src/Contracts/TenantValidationInterface.php`

- [ ] **Step 1: Add nameExists method**

Add after domainExists method:

```php
/**
 * Check if a tenant name is already in use.
 *
 * @param string $name Tenant name to check
 * @param string|null $excludeId Tenant ID to exclude from check (for updates)
 * @return bool True if name exists, false otherwise
 */
public function nameExists(string $name, ?string $excludeId = null): bool;
```

---

### Task 1.4: Implement nameExists in Laravel Adapter

**Files:**
- Modify: `adapters/Laravel/Tenant/src/EloquentTenantValidation.php` (or create if not exists)

- [ ] **Step 1: Find existing adapter**

Run: `ls adapters/Laravel/Tenant/src/` to find existing validation adapter

- [ ] **Step 2: Implement nameExists**

```php
public function nameExists(string $name, ?string $excludeId = null): bool
{
    $query = EloquentTenant::where('name', $name);
    
    if ($excludeId !== null) {
        $query->where('id', '!=', $excludeId);
    }
    
    return $query->exists();
}
```

---

## Phase 2: Adapter Layer Completions (Prerequisite)

### Task 2.1: UserPersistInterface Laravel Adapter

**Files:**
- Create: `adapters/Laravel/Identity/src/EloquentUserPersist.php`

- [ ] **Step 1: Create adapter implementing UserPersistInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\Identity\Adapters\Laravel;

use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserInterface;

final readonly class EloquentUserPersist implements UserPersistInterface
{
    public function create(array $data): UserInterface
    {
        // Implementation using Eloquent model
    }

    public function update(string $userId, array $data): UserInterface
    {
        // Implementation
    }

    public function delete(string $userId): bool
    {
        // Implementation
    }

    public function forceDelete(string $userId): bool
    {
        // Implementation
    }
}
```

---

### Task 2.2: UserQueryInterface Laravel Adapter

**Files:**
- Create: `adapters/Laravel/Identity/src/EloquentUserQuery.php`

- [ ] **Step 1: Create adapter implementing UserQueryInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\Identity\Adapters\Laravel;

use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Contracts\UserInterface;

final readonly class EloquentUserQuery implements UserQueryInterface
{
    public function findById(string $id): ?UserInterface
    {
        // Implementation
    }

    public function findByEmail(string $email): ?UserInterface
    {
        // Implementation
    }

    public function emailExists(string $email, ?string $excludeId = null): bool
    {
        // Implementation
    }

    public function search(array $criteria): array
    {
        // Implementation
    }
}
```

---

## Phase 3: Atomy-Q Orchestrator Services

### Task 3.1: VerificationTokenService

**Files:**
- Create: `orchestrators/IdentityOperations/src/Services/VerificationTokenService.php`
- Create: `orchestrators/IdentityOperations/src/Contracts/VerificationTokenServiceInterface.php`

- [ ] **Step 1: Define interface**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

interface VerificationTokenServiceInterface
{
    /**
     * Generate a verification token for email confirmation.
     */
    public function generate(string $userId, string $tenantId): string;

    /**
     * Validate and decode a verification token.
     * 
     * @return array{user_id: string, tenant_id: string, purpose: string, issued_at: string}|null
     */
    public function validate(string $token): ?array;

    /**
     * Invalidate all tokens for a user (e.g., after registration with new tenant).
     */
    public function invalidateForUser(string $userId): void;
}
```

- [ ] **Step 2: Implement service**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\IdentityOperations\Contracts\VerificationTokenServiceInterface;

final readonly class VerificationTokenService implements VerificationTokenServiceInterface
{
    private const PURPOSE = 'email_verification';
    private const EXPIRY_SECONDS = 86400; // 24 hours

    public function __construct(
        private AsymmetricSignerInterface $signer
    ) {
    }

    public function generate(string $userId, string $tenantId): string
    {
        $payload = json_encode([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'purpose' => self::PURPOSE,
            'issued_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'expires_at' => (new \DateTimeImmutable())->modify('+24 hours')->format(\DateTimeInterface::ATOM),
        ]);

        return $this->signer->sign($payload);
    }

    public function validate(string $token): ?array
    {
        $payload = $this->signer->verify($token);
        
        if ($payload === null) {
            return null;
        }

        $data = json_decode($payload, true);

        if ($data['purpose'] !== self::PURPOSE) {
            return null;
        }

        $expiresAt = new \DateTimeImmutable($data['expires_at']);
        if ($expiresAt < new \DateTimeImmutable()) {
            return null;
        }

        return $data;
    }

    public function invalidateForUser(string $userId): void
    {
        // Token-based: no DB storage, so nothing to invalidate
        // Used for clearing stale pending invites on verification
    }
}
```

---

### Task 3.2: InvitationService

**Files:**
- Create: `orchestrators/IdentityOperations/src/Services/InvitationService.php`
- Create: `orchestrators/IdentityOperations/src/Contracts/InvitationServiceInterface.php`
- Create: `orchestrators/IdentityOperations/src/DTOs/InvitationCreateRequest.php`
- Create: `orchestrators/IdentityOperations/src/DTOs/InvitationCreateResult.php`

- [ ] **Step 1: Define InvitationServiceInterface**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\InvitationCreateRequest;
use Nexus\IdentityOperations\DTOs\InvitationCreateResult;

interface InvitationServiceInterface
{
    /**
     * Create and send an invitation.
     *
     * @throws \Nexus\IdentityOperations\Exceptions\InvitationException
     */
    public function createInvitation(InvitationCreateRequest $request): InvitationCreateResult;

    /**
     * Accept an invitation (Path A: new user).
     *
     * @throws \Nexus\IdentityOperations\Exceptions\InvitationException
     */
    public function acceptInvitation(string $token): void;

    /**
     * Check if invitation can proceed to denounce path (Path B).
     */
    public function getInvitationDetails(string $token): ?array;
}
```

- [ ] **Step 2: Create DTOs**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

final readonly class InvitationCreateRequest
{
    public function __construct(
        public string $inviteeEmail,
        public string $tenantId,
        public string $inviterId
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DTOs;

final readonly class InvitationCreateResult
{
    public function __construct(
        public string $invitationId,
        public string $token
    ) {
    }
}
```

- [ ] **Step 3: Implement InvitationService**

Key logic:
- One active invite per tenant (invalidate previous on new invite)
- 24h expiry
- Path B: handle denounce scenario

---

### Task 3.3: DenounceJoinService

**Files:**
- Create: `orchestrators/IdentityOperations/src/Services/DenounceJoinService.php`
- Create: `orchestrators/IdentityOperations/src/Contracts/DenounceJoinServiceInterface.php`

- [ ] **Step 1: Define interface**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

interface DenounceJoinServiceInterface
{
    /**
     * Execute denounce & join flow.
     * 
     * @throws \Nexus\IdentityOperations\Exceptions\DenounceException
     */
    public function execute(
        string $userId,
        string $oldTenantId,
        string $newTenantId
    ): void;

    /**
     * Check if old tenant will become orphaned.
     */
    public function willBeOrphaned(string $tenantId): bool;
}
```

---

### Task 3.4: AdminDelegationService

**Files:**
- Create: `orchestrators/IdentityOperations/src/Services/AdminDelegationService.php`
- Create: `orchestrators/IdentityOperations/src/Contracts/AdminDelegationServiceInterface.php`

- [ ] **Step 1: Define interface**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

interface AdminDelegationServiceInterface
{
    /**
     * Delegate admin role from current admin to target member.
     *
     * @throws \Nexus\IdentityOperations\Exceptions\DelegationException
     */
    public function delegate(
        string $currentAdminId,
        string $targetMemberId,
        string $tenantId
    ): void;

    /**
     * Check if delegation is allowed (not sole user).
     */
    public function canDelegate(string $tenantId): bool;
}
```

---

### Task 3.5: UserUnregistrationService

**Files:**
- Create: `orchestrators/IdentityOperations/src/Services/UserUnregistrationService.php`
- Create: `orchestrators/IdentityOperations/src/Contracts/UserUnregistrationServiceInterface.php`

- [ ] **Step 1: Define interface**

```php
<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

interface UserUnregistrationServiceInterface
{
    /**
     * Queue user for deletion (soft delete).
     *
     * @throws \Nexus\IdentityOperations\Exceptions\UnregistrationException
     */
    public function queueForDeletion(string $userId): void;

    /**
     * Check if user can unregister (solo admin check).
     */
    public function canUnregister(string $userId): bool;

    /**
     * Check if unregistration will trigger tenant deletion.
     */
    public function willTriggerTenantDeletion(string $userId): bool;
}
```

---

## Phase 4: Laravel Scheduled Jobs

### Task 4.1: UserPurgeJob

**Files:**
- Create: `adapters/Laravel/Identity/src/Jobs/UserPurgeJob.php`

- [ ] **Step 1: Create Laravel command/job**

```php
<?php

declare(strict_types=1);

namespace Nexus\Identity\Adapters\Laravel\Jobs;

use Illuminate\Console\Command;

final class UserPurgeJob extends Command
{
    protected $signature = 'identity:purge-users';
    protected $description = 'Purge users queued for deletion past their deleted_at timestamp';

    public function handle(): int
    {
        // 1. Find users where status = queued_deletion AND deleted_at < now
        // 2. Cancel pending invitations sent by departing user
        // 3. Hard-delete user and cascade user-owned data
        // 4. Check if tenant has remaining active users
        // 5. If no remaining: queue tenant for deletion
        
        $this->info('User purge completed.');
        return self::SUCCESS;
    }
}
```

---

### Task 4.2: TenantPurgeJob

**Files:**
- Create: `adapters/Laravel/Tenant/src/Jobs/TenantPurgeJob.php`

- [ ] **Step 1: Create Laravel command/job**

```php
<?php

declare(strict_types=1);

namespace Nexus\Tenant\Adapters\Laravel\Jobs;

use Illuminate\Console\Command;

final class TenantPurgeJob extends Command
{
    protected $signature = 'tenant:purge-queued';
    protected $description = 'Purge tenants queued for deletion past their retention_hold_until timestamp';

    public function handle(): int
    {
        // 1. Find tenants where status = queued_deletion AND retention_hold_until < now
        // 2. Cancel all pending invitations for each tenant
        // 3. Hard-delete tenant and ALL associated data
        
        $this->info('Tenant purge completed.');
        return self::SUCCESS;
    }
}
```

---

## Execution Order Summary

1. **Phase 1 (Layer 1):** Nexus package enhancements — independent, useful for any SaaS
2. **Phase 2 (Adapters):** Laravel adapter bindings — prerequisite for orchestrators
3. **Phase 3 (Orchestrators):** Atomy-Q business logic — uses Phases 1-2
4. **Phase 4 (Jobs):** Laravel scheduled jobs — uses Phase 3 services

---

**Next Steps:**

1. **Subagent-Driven (recommended):** Dispatch subagent per task with @superpowers:subagent-driven-development
2. **Inline Execution:** Use @superpowers:executing-plans for batch execution

Which approach do you prefer?
