<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Services\UserOnboardingService;
use Nexus\IdentityOperations\Services\UserCreatorInterface;
use Nexus\IdentityOperations\Services\UserUpdaterInterface;
use Nexus\IdentityOperations\Services\TenantUserAssignerInterface;
use Nexus\IdentityOperations\Services\NotificationSenderInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Nexus\IdentityOperations\DTOs\UserCreateRequest;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Psr\Log\LoggerInterface;

final class UserOnboardingServiceTest extends TestCase
{
    private UserCreatorInterface|MockObject $userCreator;
    private UserUpdaterInterface|MockObject $userUpdater;
    private TenantUserAssignerInterface|MockObject $tenantUserAssigner;
    private NotificationSenderInterface|MockObject $notificationSender;
    private AuditLoggerInterface|MockObject $auditLogger;
    private LoggerInterface|MockObject $logger;
    private UserOnboardingService $service;

    protected function setUp(): void
    {
        $this->userCreator = $this->createMock(UserCreatorInterface::class);
        $this->userUpdater = $this->createMock(UserUpdaterInterface::class);
        $this->tenantUserAssigner = $this->createMock(TenantUserAssignerInterface::class);
        $this->notificationSender = $this->createMock(NotificationSenderInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserOnboardingService(
            $this->userCreator,
            $this->userUpdater,
            $this->tenantUserAssigner,
            $this->notificationSender,
            $this->auditLogger,
            $this->logger
        );
    }

    public function testCreateUserSuccessfully(): void
    {
        $request = new UserCreateRequest(
            email: 'test@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
            tenantId: 'tenant-1',
            roles: ['admin'],
            sendWelcomeEmail: true
        );

        $this->userCreator->expects($this->once())
            ->method('create')
            ->willReturn('user-123');

        $this->tenantUserAssigner->expects($this->once())
            ->method('assign')
            ->with('user-123', 'tenant-1', ['admin'])
            ->willReturn('tenant-user-1');

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('user.created', 'user-123');

        $this->notificationSender->expects($this->once())
            ->method('sendWelcome')
            ->with('user-123');

        $result = $this->service->createUser($request);

        $this->assertTrue($result->success);
        $this->assertEquals('user-123', $result->userId);
        $this->assertEquals('tenant-user-1', $result->tenantUserId);
    }

    public function testUpdateUserSuccessfully(): void
    {
        $request = UserUpdateRequest::create('user-123')
            ->setFirstName('Jane');

        $this->userUpdater->expects($this->once())
            ->method('update')
            ->with('user-123', $request);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('user.updated', 'user-123');

        $result = $this->service->updateUser($request);

        $this->assertTrue($result->success);
        $this->assertEquals('user-123', $result->userId);
    }

    public function testUpdateUserFailure(): void
    {
        $request = UserUpdateRequest::create('user-1');
        $this->userUpdater->expects($this->once())
            ->method('update')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->updateUser($request);
        $this->assertFalse($result->success);
    }

    public function testAssignToTenantSuccessfully(): void
    {
        $this->tenantUserAssigner->expects($this->once())
            ->method('assign')
            ->with('user-123', 'tenant-1', ['editor']);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('user.assigned_to_tenant', 'user-123');

        $result = $this->service->assignToTenant('user-123', 'tenant-1', ['editor']);

        $this->assertTrue($result);
    }

    public function testSendWelcomeNotificationSuccessfully(): void
    {
        $this->notificationSender->expects($this->once())
            ->method('sendWelcome')
            ->with('user-123', 'temp-pass');

        $result = $this->service->sendWelcomeNotification('user-123', 'temp-pass');

        $this->assertTrue($result);
    }
}
