<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\Contracts\UserOnboardingServiceInterface;
use Nexus\IdentityOperations\DTOs\UserCreateRequest;
use Nexus\IdentityOperations\DTOs\UserCreateResult;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\IdentityOperations\DTOs\UserUpdateResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for user onboarding operations.
 */
final readonly class UserOnboardingService implements UserOnboardingServiceInterface
{
    public function __construct(
        private UserCreatorInterface $userCreator,
        private UserUpdaterInterface $userUpdater,
        private TenantUserAssignerInterface $tenantUserAssigner,
        private NotificationSenderInterface $notificationSender,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function createUser(UserCreateRequest $request): UserCreateResult
    {
        $this->logger->info('Creating user', [
            'email' => hash('sha256', $request->email),
            'tenant_id' => $request->tenantId,
        ]);

        try {
            // Create user
            $userId = $this->userCreator->create(
                email: $request->email,
                password: $request->password,
                firstName: $request->firstName,
                lastName: $request->lastName,
                phone: $request->phone,
                locale: $request->locale,
                timezone: $request->timezone,
                metadata: $request->metadata,
            );

            // Assign to tenant if provided
            $tenantUserId = null;
            if ($request->tenantId !== null) {
                $tenantUserId = $this->tenantUserAssigner->assignTenantRoles(
                    userId: $userId,
                    tenantId: $request->tenantId,
                    roles: $request->roles,
                );
            }

            // Log audit
            $this->auditLogger->log(
                'user.created',
                $userId,
                [
                    'email' => hash('sha256', $request->email),
                    'tenant_id' => $request->tenantId,
                    'roles' => $request->roles,
                ]
            );

            // Send welcome notification
            if ($request->sendWelcomeEmail) {
                $this->sendWelcomeNotification($userId);
            }

            return UserCreateResult::success(
                userId: $userId,
                tenantUserId: $tenantUserId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create user', [
                'email_hash' => hash('sha256', $request->email),
                'error' => $e->getMessage(),
            ]);

            return UserCreateResult::failure(
                message: 'Failed to create user'
            );
        }
    }

    public function updateUser(UserUpdateRequest $request): UserUpdateResult
    {
        $this->logger->info('Updating user', [
            'user_id' => $request->userId,
        ]);

        try {
            $this->userUpdater->update($request);

            // Log audit
            $this->auditLogger->log(
                'user.updated',
                $request->userId,
                [
                    'updated_by' => $request->getUpdatedBy(),
                ]
            );

            return UserUpdateResult::success(
                userId: $request->userId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to update user', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return UserUpdateResult::failure(
                message: 'Failed to update user'
            );
        }
    }

    public function assignToTenant(string $userId, string $tenantId, array $roles): bool
    {
        try {
            $this->tenantUserAssigner->assignTenantRoles($userId, $tenantId, $roles);

            $this->auditLogger->log(
                'user.assigned_to_tenant',
                $userId,
                [
                    'tenant_id' => $tenantId,
                    'roles' => $roles,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to assign user to tenant', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendWelcomeNotification(string $userId, ?string $temporaryPassword = null): bool
    {
        try {
            $this->notificationSender->sendWelcome($userId, $temporaryPassword);
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to send welcome notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
