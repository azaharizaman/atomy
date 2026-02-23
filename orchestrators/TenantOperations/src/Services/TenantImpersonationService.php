<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

use Nexus\TenantOperations\Contracts\TenantImpersonationServiceInterface;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationStartResult;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for tenant impersonation operations.
 * 
 * Handles starting and ending impersonation sessions for admin users.
 * Note: Session storage should be handled by ImpersonationSessionManagerInterface implementation.
 */
final readonly class TenantImpersonationService implements TenantImpersonationServiceInterface
{
    public function __construct(
        private ImpersonationSessionManagerInterface $sessionManager,
        private ImpersonationPermissionCheckerInterface $permissionChecker,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function startImpersonation(ImpersonationStartRequest $request): ImpersonationStartResult
    {
        $this->logger->info('Starting impersonation', [
            'admin_user_id' => $request->adminUserId,
            'target_tenant_id' => $request->targetTenantId,
        ]);

        // Check if admin already has active session
        if ($this->isImpersonating($request->adminUserId)) {
            return ImpersonationStartResult::failure(
                message: 'Admin already has an active impersonation session'
            );
        }

        // Determine session timeout
        $timeoutMinutes = $request->sessionTimeoutMinutes ?? 30;
        $expiresAt = (new \DateTimeImmutable())
            ->add(new \DateInterval("PT{$timeoutMinutes}M"))
            ->format(\DateTimeInterface::ISO8601);

        // Create session
        $sessionId = $this->sessionManager->create(
            adminUserId: $request->adminUserId,
            targetTenantId: $request->targetTenantId,
            reason: $request->reason,
            expiresAt: $expiresAt,
        );

        $result = ImpersonationStartResult::success(
            sessionId: $sessionId,
            adminUserId: $request->adminUserId,
            targetTenantId: $request->targetTenantId,
            expiresAt: $expiresAt,
            message: 'Impersonation started successfully',
        );

        // Log audit
        $this->auditLogger->log(
            'impersonation.started',
            $request->targetTenantId,
            [
                'admin_user_id' => $request->adminUserId,
                'session_id' => $sessionId,
                'reason' => $request->reason,
            ]
        );

        return $result;
    }

    public function endImpersonation(ImpersonationEndRequest $request): ImpersonationEndResult
    {
        $this->logger->info('Ending impersonation', [
            'admin_user_id' => $request->adminUserId,
            'session_id' => $request->sessionId,
        ]);

        $session = $this->sessionManager->getActiveSession($request->adminUserId);

        if ($session === null) {
            return ImpersonationEndResult::failure(
                message: 'No active impersonation session found'
            );
        }

        // Validate session ID if provided
        if ($request->sessionId !== null && $request->sessionId !== $session['session_id']) {
            return ImpersonationEndResult::failure(
                message: 'Session ID mismatch'
            );
        }

        // End session
        $this->sessionManager->end($session['session_id']);

        // Get action count
        $actionCount = $this->sessionManager->getActionCount($session['session_id']);

        // Log audit
        $this->auditLogger->log(
            'impersonation.ended',
            $session['target_tenant_id'],
            [
                'admin_user_id' => $request->adminUserId,
                'session_id' => $session['session_id'],
                'reason' => $request->reason,
                'actions_performed' => $actionCount,
            ]
        );

        return ImpersonationEndResult::success(
            sessionId: $session['session_id'],
            adminUserId: $request->adminUserId,
            actionsPerformedCount: $actionCount,
            message: 'Impersonation ended successfully'
        );
    }

    public function isImpersonating(string $adminUserId): bool
    {
        return $this->sessionManager->hasActiveSession($adminUserId);
    }

    public function getCurrentSession(string $adminUserId): ?ImpersonationStartResult
    {
        $session = $this->sessionManager->getActiveSession($adminUserId);
        
        if ($session === null) {
            return null;
        }

        return ImpersonationStartResult::success(
            sessionId: $session['session_id'],
            adminUserId: $adminUserId,
            targetTenantId: $session['target_tenant_id'],
            expiresAt: $session['expires_at'],
        );
    }
}

/**
 * Interface for managing impersonation sessions.
 */
interface ImpersonationSessionManagerInterface
{
    public function create(
        string $adminUserId,
        string $targetTenantId,
        ?string $reason,
        string $expiresAt,
    ): string;

    public function end(string $sessionId): void;

    public function getActionCount(string $sessionId): int;

    public function recordAction(string $sessionId, string $action): void;

    public function hasActiveSession(string $adminUserId): bool;

    /**
     * @return array{session_id: string, target_tenant_id: string, expires_at: string}|null
     */
    public function getActiveSession(string $adminUserId): ?array;
}

/**
 * Interface for checking impersonation permissions.
 */
interface ImpersonationPermissionCheckerInterface
{
    public function hasPermission(string $adminUserId): bool;
}
