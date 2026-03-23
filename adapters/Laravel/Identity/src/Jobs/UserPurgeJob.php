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

            $this->cancelPendingInvitations($user->id, $user->tenant_id, $invitationService, $logger);

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
        string $tenantId,
        InvitationServiceInterface $invitationService,
        LoggerInterface $logger,
    ): void {
        try {
            $invitations = DB::table('invitations')
                ->where('inviter_id', $userId)
                ->where('tenant_id', $tenantId)
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
