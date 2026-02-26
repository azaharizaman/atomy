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
use Nexus\Identity\ValueObjects\Credentials;
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
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
    SessionValidatorInterface
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
        $user = $this->userPersist->create([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'locale' => $locale,
            'timezone' => $timezone,
            'metadata' => $metadata,
            'status' => 'active',
        ]);

        return $user->getId();
    }

    // --- UserUpdaterInterface ---

    public function update(
        string $userId,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null,
    ): void {
        $data = array_filter([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'locale' => $locale,
            'timezone' => $timezone,
            'metadata' => $metadata,
        ], fn($value) => $value !== null);

        $this->userPersist->update($userId, $data);
    }

    // --- TenantUserAssignerInterface ---

    public function assign(string $userId, string $tenantId, array $roles): string
    {
        $this->userPersist->update($userId, [
            'tenant_id' => $tenantId,
        ]);

        foreach ($roles as $roleId) {
            $this->userPersist->assignRole($userId, $roleId);
        }

        return $userId . ':' . $tenantId;
    }

    // --- NotificationSenderInterface ---

    public function sendWelcome(string $userId, ?string $temporaryPassword = null): void
    {
        $this->logger->info('Sending welcome notification', [
            'user_id' => $userId,
            'has_temp_password' => $temporaryPassword !== null,
        ]);
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

    public function authenticate(string $email, string $password, ?string $tenantId = null): array
    {
        $user = $this->userAuthenticator->authenticate(new Credentials($email, $password));
        
        // Map UserInterface to array expected by orchestrator
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'status' => $user->getStatus()->value,
            'permissions' => [], // Should be resolved from user
            'roles' => [], // Should be resolved from user
        ];
    }

    public function getUserById(string $userId): array
    {
        $user = $this->userQuery->findById($userId);
        
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'status' => $user->getStatus()->value,
            'permissions' => [],
            'roles' => [],
        ];
    }

    // --- OrchestratorTokenManagerInterface ---

    public function generateAccessToken(string $userId, ?string $tenantId = null): string
    {
        $token = $this->identityTokenManager->generateToken(
            $userId, 
            'Access Token',
            [],
            (new \DateTimeImmutable())->modify('+1 hour')
        );
        
        return $token->getValue();
    }

    public function generateRefreshToken(string $userId): string
    {
        // Identity package doesn't have native refresh tokens yet, 
        // we'll use a permanent API token as a placeholder.
        $token = $this->identityTokenManager->generateToken($userId, 'Refresh Token');
        return $token->getValue();
    }

    public function validateRefreshToken(string $refreshToken): array
    {
        $user = $this->identityTokenManager->validateToken($refreshToken);
        return [
            'user_id' => $user->getId(),
            'tenant_id' => $user->getTenantId(),
        ];
    }

    public function createSession(string $userId, string $accessToken): string
    {
        $session = $this->sessionManager->createSession($userId, [
            'access_token_hint' => substr($accessToken, 0, 8),
        ]);
        
        return $session->getValue();
    }

    public function invalidateSession(string $sessionId): void
    {
        $this->sessionManager->revokeSession($sessionId);
    }

    public function invalidateUserSessions(string $userId): void
    {
        $this->sessionManager->revokeAllSessions($userId);
    }

    // --- PasswordChangerInterface ---

    public function changeWithVerification(string $userId, string $currentPassword, string $newPassword): void
    {
        // This logic should be in Identity package, but we can bridge it
        $user = $this->userQuery->findById($userId);
        if ($this->userAuthenticator->verifyCredentials(new Credentials($user->getEmail(), $currentPassword))) {
            $this->userPersist->update($userId, ['password' => $newPassword]);
        } else {
            throw new \RuntimeException('Invalid current password');
        }
    }

    public function resetByAdmin(string $userId, string $newPassword): void
    {
        $this->userPersist->update($userId, ['password' => $newPassword]);
    }

    // --- SessionValidatorInterface ---

    public function isValid(string $sessionId): bool
    {
        return $this->sessionManager->isValid($sessionId);
    }

    // --- MfaEnrollerInterface ---

    public function enroll(string $userId, string $method, ?string $phone = null, ?string $email = null): array
    {
        if ($method === 'totp') {
            $result = $this->mfaEnrollment->enrollTotp($userId);
            return [
                'secret' => $result['secret']->getValue(),
                'qr_code_url' => $result['qrCodeUri'],
            ];
        }

        throw new \RuntimeException("MFA method {$method} enrollment not implemented in adapter");
    }

    public function getStatus(string $userId): array
    {
        return $this->mfaEnrollment->getUserEnrollments($userId);
    }

    // --- MfaVerifierInterface ---

    public function verify(string $userId, string $code, string $method): bool
    {
        if ($method === 'totp') {
            return $this->mfaVerification->verifyTotp($userId, $code);
        }

        return false;
    }

    public function verifyBackupCode(string $userId, string $code): bool
    {
        return $this->mfaVerification->verifyBackupCode($userId, $code);
    }

    public function getFailedAttempts(string $userId): int
    {
        return 0; 
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
