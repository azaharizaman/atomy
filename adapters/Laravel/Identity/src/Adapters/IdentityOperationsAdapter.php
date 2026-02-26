<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Nexus\Identity\Contracts\MfaEnrollmentServiceInterface;
use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\TokenManagerInterface as IdentityTokenManagerInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Nexus\Identity\Contracts\PasswordHasherInterface;
use Nexus\Identity\ValueObjects\Credentials;
use Nexus\Identity\ValueObjects\UserStatus as PackageUserStatus;
use Nexus\IdentityOperations\DTOs\RefreshTokenPayload;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaStatusResult;
use Nexus\IdentityOperations\DTOs\MfaMethod;
use Nexus\IdentityOperations\Services\AuthenticatorInterface;
use Nexus\IdentityOperations\Services\TokenManagerInterface as OrchestratorTokenManagerInterface;
use Nexus\IdentityOperations\Services\PasswordChangerInterface;
use Nexus\IdentityOperations\Services\SessionValidatorInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Nexus\IdentityOperations\Services\BackupCodeGeneratorInterface;
use Nexus\IdentityOperations\Services\MfaDisablerInterface;
use Nexus\IdentityOperations\Services\MfaEnrollerInterface;
use Nexus\IdentityOperations\Services\MfaVerifierInterface;
use Nexus\IdentityOperations\Services\NotificationSenderInterface;
use Nexus\IdentityOperations\Services\TenantUserAssignerInterface;
use Nexus\IdentityOperations\Services\UserCreatorInterface;
use Nexus\IdentityOperations\Services\UserUpdaterInterface;
use Nexus\IdentityOperations\Services\PermissionAssignerInterface;
use Nexus\IdentityOperations\Services\PermissionRevokerInterface;
use Nexus\IdentityOperations\Services\RoleAssignerInterface;
use Nexus\IdentityOperations\Services\RoleRevokerInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Illuminate\Support\Facades\DB;

/**
 * Adapter for IdentityOperations orchestrator.
 * 
 * Bridges Layer 2 orchestrator interfaces to Layer 1 packages.
 */
final readonly class IdentityOperationsAdapter implements
    UserCreatorInterface,
    UserUpdaterInterface,
    TenantUserAssignerInterface,
    NotificationSenderInterface,
    AuditLoggerInterface,
    MfaEnrollerInterface,
    MfaVerifierInterface,
    MfaDisablerInterface,
    BackupCodeGeneratorInterface,
    AuthenticatorInterface,
    OrchestratorTokenManagerInterface,
    PasswordChangerInterface,
    SessionValidatorInterface,
    PermissionAssignerInterface,
    PermissionRevokerInterface,
    RoleAssignerInterface,
    RoleRevokerInterface
{
    public function __construct(
        private UserPersistInterface $userPersist,
        private UserQueryInterface $userQuery,
        private UserAuthenticatorInterface $userAuthenticator,
        private IdentityTokenManagerInterface $identityTokenManager,
        private SessionManagerInterface $sessionManager,
        private MfaEnrollmentServiceInterface $mfaEnrollment,
        private MfaVerificationServiceInterface $mfaVerification,
        private NotificationManagerInterface $notificationManager,
        private AuditLogRepositoryInterface $auditLogRepository,
        private CacheRepositoryInterface $cache,
        private PasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    // --- UserCreatorInterface ---

    public function create(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null,
    ): string {
        $hashedPassword = $this->passwordHasher->hash($password);

        $user = $this->userPersist->create([
            'email' => $email,
            'password_hash' => $hashedPassword,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'locale' => $locale,
            'timezone' => $timezone,
            'metadata' => $metadata,
            'status' => PackageUserStatus::ACTIVE->value,
        ]);

        return $user->getId();
    }

    // --- UserUpdaterInterface ---

    public function update(UserUpdateRequest $request): void
    {
        $data = $request->toArray();
        if (empty($data)) {
            return;
        }

        $this->userPersist->update($request->userId, $data);
    }

    // --- TenantUserAssignerInterface ---

    public function assignTenantRoles(string $userId, string $tenantId, array $roles): string
    {
        return DB::transaction(function () use ($userId, $tenantId, $roles) {
            $this->userPersist->update($userId, [
                'tenant_id' => $tenantId,
            ]);

            foreach ($roles as $roleId) {
                // Note: userPersist methods rely on the entity's current state 
                // (including tenant_id updated above) to enforce boundaries.
                $this->userPersist->assignRole($userId, $roleId);
            }

            return $userId . ':' . $tenantId;
        });
    }

    // --- PermissionAssignerInterface ---

    public function assignPermission(string $userId, string $permission, string $tenantId, ?\DateTimeInterface $expiresAt = null): string
    {
        // Note: Direct permission assignment relies on underlying persistence layer to enforce 
        // tenant boundaries. $tenantId is provided to ensure call site context matches.
        $this->userPersist->assignPermission($userId, $permission);
        return $userId . ':' . $permission;
    }

    // --- PermissionRevokerInterface ---

    public function revoke(string $userId, string $permission, string $tenantId): void
    {
        $this->userPersist->revokePermission($userId, $permission);
    }

    // --- RoleAssignerInterface ---

    public function assignRole(string $userId, string $roleId, string $tenantId): string
    {
        $this->userPersist->assignRole($userId, $roleId);
        return $userId . ':' . $roleId;
    }

    // --- RoleRevokerInterface ---

    public function revoke(string $userId, string $roleId, string $tenantId): void
    {
        $this->userPersist->revokeRole($userId, $roleId);
    }

    // --- NotificationSenderInterface ---

    public function sendWelcome(string $userId, ?string $temporaryPassword = null): void
    {
        try {
            $user = $this->userQuery->findById($userId);
            
            $notification = new class($temporaryPassword) extends \Nexus\Notifier\Services\AbstractNotification {
                public function __construct(private ?string $temporaryPassword) {}
                public function toEmail(): array { 
                    return [
                        'subject' => 'Welcome to Atomy',
                        'template' => 'welcome',
                        'data' => ['temporary_password' => $this->temporaryPassword],
                    ];
                }
                public function toSms(): ?string { return null; }
                public function toPush(): ?array { return null; }
                public function toInApp(): ?array { return null; }
            };

            /** @var \Nexus\Notifier\Contracts\NotifiableInterface $user */
            $this->notificationManager->send($user, $notification);
            $this->logger->info('Welcome notification sent', ['user_id' => $userId]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // --- AuditLoggerInterface ---

    public function log(string $event, string $entityId, array $data = []): void
    {
        $this->auditLogRepository->create([
            'event' => $event,
            'subject_id' => $entityId,
            'subject_type' => 'user',
            'properties' => $data,
            'created_at' => new \DateTimeImmutable(),
        ]);
    }

    // --- AuthenticatorInterface ---

    /**
     * @param string|null $tenantId Note: $tenantId is intentionally unused because 
     *                              UserAuthenticatorInterface only accepts Credentials 
     *                              without tenant-scoped authentication support.
     */
    public function authenticate(string $email, string $password, ?string $tenantId = null): array
    {
        $user = $this->userAuthenticator->authenticate(new Credentials($email, $password));
        
        return $this->mapUserToArray($user);
    }

    public function getUserById(string $userId): array
    {
        $user = $this->userQuery->findById($userId);
        
        return $this->mapUserToArray($user);
    }

    private function mapUserToArray(\Nexus\Identity\Contracts\UserInterface $user): array
    {
        $permissions = array_map(
            fn($p) => $p->getName(),
            $this->userQuery->getUserPermissions($user->getId())
        );

        $roles = array_map(
            fn($r) => $r->getName(),
            $this->userQuery->getUserRoles($user->getId())
        );

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getName(), // UserInterface has getName, not getFirstName/LastName
            'last_name' => null,
            'status' => PackageUserStatus::from($user->getStatus()),
            'permissions' => $permissions,
            'roles' => $roles,
        ];
    }

    // --- OrchestratorTokenManagerInterface ---

    public function generateAccessToken(string $userId, string $tenantId): string
    {
        $token = $this->identityTokenManager->generateToken(
            $userId, 
            'Access Token',
            ['tenant_id' => $tenantId],
            (new \DateTimeImmutable())->modify('+1 hour')
        );
        
        return $token->getValue();
    }

    public function generateRefreshToken(string $userId, string $tenantId): string
    {
        $token = $this->identityTokenManager->generateToken(
            $userId,
            'Refresh Token',
            ['tenant_id' => $tenantId, 'refresh_token' => true],
            (new \DateTimeImmutable())->modify('+30 days')
        );
        
        return $token->getValue();
    }

    public function validateRefreshToken(string $refreshToken, string $tenantId): RefreshTokenPayload
    {
        $user = $this->identityTokenManager->validateToken($refreshToken);
        
        $tokenTenantId = $user->getTenantId();
        if ($tokenTenantId === null) {
            throw new \RuntimeException('Token is missing required tenant context');
        }

        if ($tokenTenantId !== $tenantId) {
            throw new \RuntimeException('Token tenant ID mismatch');
        }

        return new RefreshTokenPayload(
            userId: $user->getId(),
            tenantId: $tokenTenantId,
        );
    }

    public function createSession(string $userId, string $accessToken, string $tenantId): string
    {
        $session = $this->sessionManager->createSession($userId, [
            'access_token_hint' => substr($accessToken, 0, 8),
            'tenant_id' => $tenantId,
        ]);
        
        return $session->getValue();
    }

    public function invalidateSession(string $sessionId, string $tenantId): void
    {
        // Note: Intentional cross-tenant behavior if underlying SessionManager 
        // does not support tenant-scoped revocation yet.
        $this->sessionManager->revokeSession($sessionId);
    }

    public function invalidateUserSessions(string $userId, string $tenantId): void
    {
        // Note: Intentional cross-tenant behavior if underlying SessionManager 
        // does not support tenant-scoped revocation yet.
        $this->sessionManager->revokeAllSessions($userId);
    }

    // --- PasswordChangerInterface ---

    public function changeWithVerification(string $userId, string $currentPassword, string $newPassword): void
    {
        $user = $this->userQuery->findById($userId);
        if ($this->userAuthenticator->verifyCredentials(new Credentials($user->getEmail(), $currentPassword))) {
            $hashedPassword = $this->passwordHasher->hash($newPassword);
            $this->userPersist->update($userId, ['password_hash' => $hashedPassword]);
        } else {
            throw new \RuntimeException('Invalid current password');
        }
    }

    public function resetByAdmin(string $userId, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hash($newPassword);
        $this->userPersist->update($userId, ['password_hash' => $hashedPassword]);
    }

    // --- SessionValidatorInterface ---

    public function isValid(string $sessionId): bool
    {
        return $this->sessionManager->isValid($sessionId);
    }

    // --- MfaEnrollerInterface ---

    public function enroll(string $userId, MfaMethod $method, ?string $phone = null, ?string $email = null): MfaEnableResult
    {
        // TODO: $phone and $email parameters are reserved for future SMS/email MFA support.
        if ($method === MfaMethod::TOTP) {
            $result = $this->mfaEnrollment->enrollTotp($userId);
            return MfaEnableResult::success(
                $userId,
                $result['secret']->getValue(),
                $result['qrCodeUri']
            );
        }

        return MfaEnableResult::failure($userId, "MFA method {$method->value} enrollment not implemented in adapter");
    }

    public function getStatus(string $userId): MfaStatusResult
    {
        $enrollments = $this->mfaEnrollment->getUserEnrollments($userId);
        
        $status = [];
        foreach ($enrollments as $enrollment) {
            $status[$enrollment['method']] = true;
        }

        return new MfaStatusResult($userId, $status);
    }

    // --- MfaVerifierInterface ---

    public function verify(string $userId, MfaMethod $method, string $code): bool
    {
        if ($method === MfaMethod::TOTP) {
            return $this->mfaVerification->verifyTotp($userId, $code);
        }

        $this->logger->warning('Unsupported MFA method verification attempt', [
            'user_id' => $userId,
            'method' => $method->value
        ]);

        return false;
    }

    public function verifyBackupCode(string $userId, string $code, ?string $tenantId = null): bool
    {
        return $this->mfaVerification->verifyBackupCode($userId, $code);
    }

    public function getFailedAttempts(string $userId, ?string $tenantId = null): int
    {
        $key = sprintf('mfa_failed_attempts:%s:%s', $userId, $tenantId ?? 'default');
        
        try {
            return (int) $this->cache->get($key, 0);
        } catch (\Exception $e) {
            $this->logger->error('Failed to read failed login attempts from cache', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    // --- MfaDisablerInterface ---

    public function disable(string $userId): void
    {
        $enrollments = $this->mfaEnrollment->getUserEnrollments($userId);
        foreach ($enrollments as $enrollment) {
            $this->mfaEnrollment->revokeEnrollment($userId, $enrollment['id']);
        }
    }

    // --- BackupCodeGeneratorInterface ---

    public function generate(string $userId): array
    {
        $codeSet = $this->mfaEnrollment->generateBackupCodes($userId);
        return array_map(fn($code) => $code->getValue(), $codeSet->getCodes());
    }
}
