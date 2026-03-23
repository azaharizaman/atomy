# UserPurgeJob Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create Laravel command `identity:purge-users` that permanently deletes users after retention period expires, cancels pending invitations, and triggers tenant deletion if no active users remain.

**Architecture:** Daily scheduled Laravel command that queries users with `status = 'queued_deletion'` and `deleted_at < now`, cancels their pending invitations, hard-deletes the user records, and checks if the former tenant has any remaining active users. If none, queue the tenant for deletion with 7-day retention.

**Tech Stack:** Laravel Artisan Command, Eloquent, Identity package (UserPersistInterface, UserQueryInterface), InvitationServiceInterface, TenantArchiverInterface

---

### Task 1: Create UserPurgeJob command

**Files:**
- Create: `adapters/Laravel/Identity/src/Jobs/UserPurgeJob.php`

- [ ] **Step 1: Write the UserPurgeJob command**

```php
<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Jobs;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\IdentityOperations\Contracts\InvitationServiceInterface;
use Nexus\IdentityOperations\Contracts\TenantArchiverInterface;
use Nexus\IdentityOperations\DataProviders\TenantQueryInterface;
use Psr\Log\LoggerInterface;

final class UserPurgeJob extends Command
{
    protected $signature = 'identity:purge-users';

    protected $description = 'Permanently delete users whose retention period has expired';

    public function handle(
        UserQueryInterface $userQuery,
        UserPersistInterface $userPersist,
        InvitationServiceInterface $invitationService,
        TenantQueryInterface $tenantQuery,
        TenantArchiverInterface $tenantArchiver,
        LoggerInterface $logger,
    ): int {
        $now = Carbon::now('UTC');
        $cutoff = $now->format('Y-m-d H:i:s');

        $usersToPurge = DB::table('users')
            ->where('status', 'queued_deletion')
            ->where('deleted_at', '<', $cutoff)
            ->get();

        if ($usersToPurge->isEmpty()) {
            $logger->info('No users to purge');
            $this->info('No users to purge.');
            return self::SUCCESS;
        }

        $purgedCount = 0;
        $tenantIdsToCheck = [];

        foreach ($usersToPurge as $user) {
            $logger->info('Purging user', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

            $this->cancelPendingInvitations($user->id, $invitationService, $logger);

            $userPersist->delete($user->id, $user->tenant_id);

            if ($user->tenant_id !== null && !in_array($user->tenant_id, $tenantIdsToCheck, true)) {
                $tenantIdsToCheck[] = $user->tenant_id;
            }

            $purgedCount++;
            $logger->info('User purged', ['user_id' => $user->id]);
        }

        foreach ($tenantIdsToCheck as $tenantId) {
            $activeUserCount = $tenantQuery->countActiveUsers($tenantId);

            if ($activeUserCount === 0) {
                $logger->info('No active users remaining for tenant, queueing for deletion', ['tenant_id' => $tenantId]);
                $tenantArchiver->archiveWithRetention($tenantId, 7);
                $this->info("Tenant {$tenantId} queued for deletion (no active users remaining).");
            }
        }

        $this->info("Purged {$purgedCount} user(s).");
        $logger->info('User purge completed', ['purged_count' => $purgedCount]);

        return self::SUCCESS;
    }

    private function cancelPendingInvitations(
        string $userId,
        InvitationServiceInterface $invitationService,
        LoggerInterface $logger,
    ): void {
        try {
            $invitations = DB::table('invitations')
                ->where('inviter_id', $userId)
                ->where('status', 'pending')
                ->get();

            foreach ($invitations as $invitation) {
                $invitationService->cancelInvitation($invitation->id);
                $logger->info('Cancelled pending invitation', ['invitation_id' => $invitation->id]);
            }
        } catch (\Throwable $e) {
            $logger->warning('Failed to cancel invitations for user', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add adapters/Laravel/Identity/src/Jobs/UserPurgeJob.php
git commit -m "feat(Identity): add identity:purge-users command"
```

**Dependencies identified:**
- `Nexus\Identity\Contracts\UserQueryInterface` - exists in packages/Identity
- `Nexus\Identity\Contracts\UserPersistInterface` - exists in packages/Identity
- `Nexus\IdentityOperations\Contracts\InvitationServiceInterface` - exists in orchestrators/IdentityOperations
- `Nexus\IdentityOperations\DataProviders\TenantQueryInterface` - exists in orchestrators/IdentityOperations
- `Nexus\IdentityOperations\Contracts\TenantArchiverInterface` - exists in orchestrators/IdentityOperations

Note: The command uses DB facade directly to query users with `status = 'queued_deletion'` and `deleted_at < now`, which is the same pattern used in `IdempotencyCleanupCommand`.
