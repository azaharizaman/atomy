<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\Contracts\UserLifecycleServiceInterface;
use Nexus\IdentityOperations\DTOs\UserSuspendRequest;
use Nexus\IdentityOperations\DTOs\UserSuspendResult;
use Nexus\IdentityOperations\DTOs\UserActivateRequest;
use Nexus\IdentityOperations\DTOs\UserActivateResult;
use Nexus\IdentityOperations\DTOs\UserDeactivateRequest;
use Nexus\IdentityOperations\DTOs\UserDeactivateResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for user lifecycle operations.
 */
final readonly class UserLifecycleService implements UserLifecycleServiceInterface
{
    public function __construct(
        private UserStateManagerInterface $stateManager,
        private SessionManagerInterface $sessionManager,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function suspend(UserSuspendRequest $request): UserSuspendResult
    {
        $this->logger->info('Suspending user', [
            'user_id' => $request->userId,
            'suspended_by' => $request->suspendedBy,
            'tenant_id' => $request->tenantId,
        ]);

        try {
            // Disable user access
            $this->disableAccess($request->userId);

            // Invalidate sessions
            $this->sessionManager->invalidateUserSessions($request->userId, $request->tenantId);

            // Update user state
            $this->stateManager->suspend($request->userId);

            // Log audit
            $this->auditLogger->log(
                'user.suspended',
                $request->userId,
                [
                    'suspended_by' => $request->suspendedBy,
                    'reason' => $request->reason,
                    'tenant_id' => $request->tenantId,
                ]
            );

            return UserSuspendResult::success(
                userId: $request->userId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to suspend user', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return UserSuspendResult::failure(
                message: 'Failed to suspend user: ' . $e->getMessage()
            );
        }
    }

    public function activate(UserActivateRequest $request): UserActivateResult
    {
        $this->logger->info('Activating user', [
            'user_id' => $request->userId,
            'activated_by' => $request->activatedBy,
            'tenant_id' => $request->tenantId,
        ]);

        try {
            // Update user state
            $this->stateManager->activate($request->userId);

            // Enable user access
            $this->enableAccess($request->userId);

            // Log audit
            $this->auditLogger->log(
                'user.activated',
                $request->userId,
                [
                    'activated_by' => $request->activatedBy,
                    'reason' => $request->reason,
                    'tenant_id' => $request->tenantId,
                ]
            );

            return UserActivateResult::success(
                userId: $request->userId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to activate user', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return UserActivateResult::failure(
                message: 'Failed to activate user: ' . $e->getMessage()
            );
        }
    }

    public function deactivate(UserDeactivateRequest $request): UserDeactivateResult
    {
        $this->logger->info('Deactivating user', [
            'user_id' => $request->userId,
            'deactivated_by' => $request->deactivatedBy,
            'tenant_id' => $request->tenantId,
        ]);

        try {
            // Disable user access
            $this->disableAccess($request->userId);

            // Invalidate all sessions
            $this->sessionManager->invalidateUserSessions($request->userId, $request->tenantId);

            // Update user state
            $this->stateManager->deactivate($request->userId);

            // Log audit
            $this->auditLogger->log(
                'user.deactivated',
                $request->userId,
                [
                    'deactivated_by' => $request->deactivatedBy,
                    'reason' => $request->reason,
                    'preserve_data' => $request->preserveData,
                    'tenant_id' => $request->tenantId,
                ]
            );

            return UserDeactivateResult::success(
                userId: $request->userId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to deactivate user', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return UserDeactivateResult::failure(
                message: 'Failed to deactivate user: ' . $e->getMessage()
            );
        }
    }

    public function forceLogout(string $userId, string $performedBy, string $tenantId): bool
    {
        try {
            $this->sessionManager->invalidateUserSessions($userId, $tenantId);

            $this->auditLogger->log(
                'user.force_logout',
                $userId,
                ['performed_by' => $performedBy, 'tenant_id' => $tenantId]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to force logout user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function disableAccess(string $userId): void
    {
        $this->stateManager->setAccessEnabled($userId, false);
    }

    public function enableAccess(string $userId): void
    {
        $this->stateManager->setAccessEnabled($userId, true);
    }
}
