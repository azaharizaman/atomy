<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Identity Audit Service for logging security-relevant events.
 * 
 * This service integrates with the Nexus AuditLogger package to produce
 * secure audit events for identity operations.
 */
final class IdentityAuditService
{
    /**
     * Audit levels following Nexus AuditLogger standards.
     */
    private const LEVEL_LOW = 1;
    private const LEVEL_MEDIUM = 2;
    private const LEVEL_HIGH = 3;
    private const LEVEL_CRITICAL = 4;

    /**
     * Default retention days for identity audit logs.
     */
    private const DEFAULT_RETENTION_DAYS = 365;

    public function __construct(
        private readonly AuditLogRepositoryInterface $auditRepository,
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    // ==================== User Events ====================

    public function logUserCreated(string $userId, string $email, ?string $creatorId = null): AuditLogInterface
    {
        return $this->log(
            logName: 'user_created',
            description: "User account created: {$email}",
            subjectType: 'User',
            subjectId: $userId,
            event: 'created',
            level: self::LEVEL_MEDIUM,
            properties: [
                'email' => $email,
            ],
            causerId: $creatorId ?? $this->getCurrentUserId(),
        );
    }

    public function logUserDeleted(string $userId, string $email, ?string $deleterId = null): AuditLogInterface
    {
        return $this->log(
            logName: 'user_deleted',
            description: "User account deleted: {$email}",
            subjectType: 'User',
            subjectId: $userId,
            event: 'deleted',
            level: self::LEVEL_HIGH,
            properties: [
                'email' => $email,
            ],
            causerId: $deleterId ?? $this->getCurrentUserId(),
        );
    }

    public function logPasswordChanged(string $userId, ?string $changerId = null): AuditLogInterface
    {
        return $this->log(
            logName: 'password_changed',
            description: "Password changed for user",
            subjectType: 'User',
            subjectId: $userId,
            event: 'updated',
            level: self::LEVEL_HIGH,
            causerId: $changerId ?? $this->getCurrentUserId(),
        );
    }

    public function logLoginSuccess(string $userId, string $email): AuditLogInterface
    {
        return $this->log(
            logName: 'login_success',
            description: "Successful login: {$email}",
            subjectType: 'User',
            subjectId: $userId,
            event: 'login',
            level: self::LEVEL_LOW,
            causerId: $userId,
        );
    }

    public function logLoginFailed(string $email, ?string $reason = null): AuditLogInterface
    {
        return $this->log(
            logName: 'login_failed',
            description: "Failed login attempt: {$email}",
            subjectType: 'User',
            subjectId: null,
            event: 'login_failed',
            level: self::LEVEL_MEDIUM,
            properties: [
                'email' => $email,
                'reason' => $reason,
            ],
        );
    }

    public function logLogout(string $userId): AuditLogInterface
    {
        return $this->log(
            logName: 'logout',
            description: "User logged out",
            subjectType: 'User',
            subjectId: $userId,
            event: 'logout',
            level: self::LEVEL_LOW,
            causerId: $userId,
        );
    }

    // ==================== Role Events ====================

    public function logRoleCreated(string $roleId, string $roleName): AuditLogInterface
    {
        return $this->log(
            logName: 'role_created',
            description: "Role created: {$roleName}",
            subjectType: 'Role',
            subjectId: $roleId,
            event: 'created',
            level: self::LEVEL_MEDIUM,
            properties: [
                'role_name' => $roleName,
            ],
        );
    }

    public function logRoleUpdated(string $roleId, string $roleName, array $changes = []): AuditLogInterface
    {
        return $this->log(
            logName: 'role_updated',
            description: "Role updated: {$roleName}",
            subjectType: 'Role',
            subjectId: $roleId,
            event: 'updated',
            level: self::LEVEL_MEDIUM,
            properties: [
                'role_name' => $roleName,
                'changes' => $changes,
            ],
        );
    }

    public function logRoleDeleted(string $roleId, string $roleName): AuditLogInterface
    {
        return $this->log(
            logName: 'role_deleted',
            description: "Role deleted: {$roleName}",
            subjectType: 'Role',
            subjectId: $roleId,
            event: 'deleted',
            level: self::LEVEL_HIGH,
            properties: [
                'role_name' => $roleName,
            ],
        );
    }

    public function logRoleAssigned(string $userId, string $roleId, string $roleName): AuditLogInterface
    {
        return $this->log(
            logName: 'role_assigned',
            description: "Role '{$roleName}' assigned to user",
            subjectType: 'User',
            subjectId: $userId,
            event: 'role_assigned',
            level: self::LEVEL_MEDIUM,
            properties: [
                'role_id' => $roleId,
                'role_name' => $roleName,
            ],
        );
    }

    public function logRoleRevoked(string $userId, string $roleId, string $roleName): AuditLogInterface
    {
        return $this->log(
            logName: 'role_revoked',
            description: "Role '{$roleName}' revoked from user",
            subjectType: 'User',
            subjectId: $userId,
            event: 'role_revoked',
            level: self::LEVEL_MEDIUM,
            properties: [
                'role_id' => $roleId,
                'role_name' => $roleName,
            ],
        );
    }

    // ==================== Permission Events ====================

    public function logPermissionCreated(string $permId, string $permName): AuditLogInterface
    {
        return $this->log(
            logName: 'permission_created',
            description: "Permission created: {$permName}",
            subjectType: 'Permission',
            subjectId: $permId,
            event: 'created',
            level: self::LEVEL_MEDIUM,
            properties: [
                'permission_name' => $permName,
            ],
        );
    }

    public function logPermissionDeleted(string $permId, string $permName): AuditLogInterface
    {
        return $this->log(
            logName: 'permission_deleted',
            description: "Permission deleted: {$permName}",
            subjectType: 'Permission',
            subjectId: $permId,
            event: 'deleted',
            level: self::LEVEL_HIGH,
            properties: [
                'permission_name' => $permName,
            ],
        );
    }

    public function logPermissionAssignedToRole(string $roleId, string $roleName, string $permId, string $permName): AuditLogInterface
    {
        return $this->log(
            logName: 'permission_assigned',
            description: "Permission '{$permName}' assigned to role '{$roleName}'",
            subjectType: 'Role',
            subjectId: $roleId,
            event: 'permission_assigned',
            level: self::LEVEL_MEDIUM,
            properties: [
                'role_name' => $roleName,
                'permission_id' => $permId,
                'permission_name' => $permName,
            ],
        );
    }

    public function logPermissionRevokedFromRole(string $roleId, string $roleName, string $permId, string $permName): AuditLogInterface
    {
        return $this->log(
            logName: 'permission_revoked',
            description: "Permission '{$permName}' revoked from role '{$roleName}'",
            subjectType: 'Role',
            subjectId: $roleId,
            event: 'permission_revoked',
            level: self::LEVEL_MEDIUM,
            properties: [
                'role_name' => $roleName,
                'permission_id' => $permId,
                'permission_name' => $permName,
            ],
        );
    }

    // ==================== Token Events ====================

    public function logTokenGenerated(string $userId, string $tokenId, string $tokenName): AuditLogInterface
    {
        return $this->log(
            logName: 'token_generated',
            description: "API token generated: {$tokenName}",
            subjectType: 'ApiToken',
            subjectId: $tokenId,
            event: 'created',
            level: self::LEVEL_MEDIUM,
            properties: [
                'user_id' => $userId,
                'token_name' => $tokenName,
            ],
        );
    }

    public function logTokenRevoked(string $tokenId): AuditLogInterface
    {
        return $this->log(
            logName: 'token_revoked',
            description: "API token revoked",
            subjectType: 'ApiToken',
            subjectId: $tokenId,
            event: 'revoked',
            level: self::LEVEL_MEDIUM,
        );
    }

    public function logAllTokensRevoked(string $userId): AuditLogInterface
    {
        return $this->log(
            logName: 'tokens_revoked_all',
            description: "All API tokens revoked for user",
            subjectType: 'User',
            subjectId: $userId,
            event: 'tokens_revoked',
            level: self::LEVEL_HIGH,
        );
    }

    // ==================== Session Events ====================

    public function logSessionCreated(string $userId, string $sessionToken): AuditLogInterface
    {
        // Only log partial token for security
        $maskedToken = substr($sessionToken, 0, 8) . '...';
        
        return $this->log(
            logName: 'session_created',
            description: "Session created",
            subjectType: 'Session',
            subjectId: $maskedToken,
            event: 'created',
            level: self::LEVEL_LOW,
            properties: [
                'user_id' => $userId,
            ],
            causerId: $userId,
        );
    }

    public function logSessionRevoked(string $sessionToken): AuditLogInterface
    {
        $maskedToken = substr($sessionToken, 0, 8) . '...';
        
        return $this->log(
            logName: 'session_revoked',
            description: "Session revoked",
            subjectType: 'Session',
            subjectId: $maskedToken,
            event: 'revoked',
            level: self::LEVEL_LOW,
        );
    }

    public function logAllSessionsRevoked(string $userId): AuditLogInterface
    {
        return $this->log(
            logName: 'sessions_revoked_all',
            description: "All sessions revoked for user",
            subjectType: 'User',
            subjectId: $userId,
            event: 'sessions_revoked',
            level: self::LEVEL_MEDIUM,
        );
    }

    // ==================== MFA Events ====================

    public function logMfaEnabled(string $userId, string $method = 'totp'): AuditLogInterface
    {
        return $this->log(
            logName: 'mfa_enabled',
            description: "MFA enabled ({$method})",
            subjectType: 'User',
            subjectId: $userId,
            event: 'mfa_enabled',
            level: self::LEVEL_HIGH,
            properties: [
                'method' => $method,
            ],
            causerId: $userId,
        );
    }

    public function logMfaDisabled(string $userId): AuditLogInterface
    {
        return $this->log(
            logName: 'mfa_disabled',
            description: "MFA disabled",
            subjectType: 'User',
            subjectId: $userId,
            event: 'mfa_disabled',
            level: self::LEVEL_CRITICAL,
            causerId: $this->getCurrentUserId(),
        );
    }

    public function logMfaVerified(string $userId, string $method = 'totp'): AuditLogInterface
    {
        return $this->log(
            logName: 'mfa_verified',
            description: "MFA verification successful ({$method})",
            subjectType: 'User',
            subjectId: $userId,
            event: 'mfa_verified',
            level: self::LEVEL_LOW,
            properties: [
                'method' => $method,
            ],
            causerId: $userId,
        );
    }

    public function logMfaFailed(string $userId, string $method = 'totp'): AuditLogInterface
    {
        return $this->log(
            logName: 'mfa_failed',
            description: "MFA verification failed ({$method})",
            subjectType: 'User',
            subjectId: $userId,
            event: 'mfa_failed',
            level: self::LEVEL_MEDIUM,
            properties: [
                'method' => $method,
            ],
        );
    }

    public function logTrustedDeviceAdded(string $userId, string $deviceId): AuditLogInterface
    {
        return $this->log(
            logName: 'trusted_device_added',
            description: "Trusted device added",
            subjectType: 'User',
            subjectId: $userId,
            event: 'device_trusted',
            level: self::LEVEL_MEDIUM,
            properties: [
                'device_id' => $deviceId,
            ],
            causerId: $userId,
        );
    }

    public function logTrustedDeviceRevoked(string $userId, string $deviceId): AuditLogInterface
    {
        return $this->log(
            logName: 'trusted_device_revoked',
            description: "Trusted device revoked",
            subjectType: 'User',
            subjectId: $userId,
            event: 'device_revoked',
            level: self::LEVEL_MEDIUM,
            properties: [
                'device_id' => $deviceId,
            ],
        );
    }

    // ==================== GDPR Events ====================

    public function logDataExport(string $userId): AuditLogInterface
    {
        return $this->log(
            logName: 'data_export',
            description: "User data exported (GDPR right of access)",
            subjectType: 'User',
            subjectId: $userId,
            event: 'exported',
            level: self::LEVEL_MEDIUM,
        );
    }

    public function logDataDeletion(string $userId, string $email): AuditLogInterface
    {
        return $this->log(
            logName: 'data_deletion',
            description: "User data deleted (GDPR right to erasure): {$email}",
            subjectType: 'User',
            subjectId: $userId,
            event: 'erased',
            level: self::LEVEL_CRITICAL,
            properties: [
                'email' => $email,
            ],
        );
    }

    // ==================== Core Logging Method ====================

    private function log(
        string $logName,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $event = null,
        int $level = self::LEVEL_MEDIUM,
        array $properties = [],
        ?string $causerId = null,
    ): AuditLogInterface {
        $request = $this->requestStack->getCurrentRequest();
        
        $data = [
            'id' => (string) new Ulid(),
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'causer_type' => $causerId !== null ? 'User' : null,
            'causer_id' => $causerId ?? $this->getCurrentUserId(),
            'properties' => $properties,
            'event' => $event,
            'level' => $level,
            'ip_address' => $request?->getClientIp(),
            'user_agent' => $request?->headers->get('User-Agent'),
            'retention_days' => self::DEFAULT_RETENTION_DAYS,
            'created_at' => new \DateTimeImmutable(),
            'expires_at' => (new \DateTimeImmutable())->modify('+' . self::DEFAULT_RETENTION_DAYS . ' days'),
        ];

        return $this->auditRepository->create($data);
    }

    private function getCurrentUserId(): ?string
    {
        $token = $this->tokenStorage->getToken();
        
        if ($token === null) {
            return null;
        }

        $user = $token->getUser();
        
        if ($user instanceof User) {
            return $user->getId();
        }

        return null;
    }
}
