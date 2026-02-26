<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Coordinators\UserOnboardingCoordinator;
use Nexus\IdentityOperations\Contracts\UserOnboardingServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserCreateRequest;
use Nexus\IdentityOperations\DTOs\UserCreateResult;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\IdentityOperations\DTOs\UserUpdateResult;
use Psr\Log\LoggerInterface;

final class UserOnboardingCoordinatorTest extends TestCase
{
    private readonly UserOnboardingServiceInterface|MockObject $onboardingService;
    private readonly UserContextProviderInterface|MockObject $contextDataProvider;
    private readonly LoggerInterface|MockObject $logger;
    private readonly UserOnboardingCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->onboardingService = $this->createMock(UserOnboardingServiceInterface::class);
        $this->contextDataProvider = $this->createMock(UserContextProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new UserOnboardingCoordinator(
            $this->onboardingService,
            $this->contextDataProvider,
            $this->logger
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('UserOnboardingCoordinator', $this->coordinator->getName());
    }

    public function testHasRequiredData(): void
    {
        $this->contextDataProvider->expects($this->once())
            ->method('userExists')
            ->with('user-1')
            ->willReturn(true);
        $this->assertTrue($this->coordinator->hasRequiredData('user-1'));
    }

    public function testHasRequiredDataFails(): void
    {
        $this->contextDataProvider->expects($this->once())
            ->method('userExists')
            ->with('user-999')
            ->willReturn(false);
        $this->assertFalse($this->coordinator->hasRequiredData('user-999'));
    }

    public function testCreateUser(): void
    {
        $request = new UserCreateRequest(
            email: 'test@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe'
        );

        $result = UserCreateResult::success('user-123');

        $this->onboardingService->expects($this->once())
            ->method('createUser')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->createUser($request));
    }

    public function testCreateUserFails(): void
    {
        $request = new UserCreateRequest(email: 'fail@example.com', password: '...', firstName: 'F', lastName: 'L');
        $result = UserCreateResult::failure('Email already exists');

        $this->onboardingService->expects($this->once())
            ->method('createUser')
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->createUser($request));
    }

    public function testUpdateUser(): void
    {
        $request = UserUpdateRequest::create('user-1')->setFirstName('Jane');
        $result = UserUpdateResult::success('user-1');

        $this->onboardingService->expects($this->once())
            ->method('updateUser')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->updateUser($request));
    }

    public function testUpdateUserFails(): void
    {
        $request = UserUpdateRequest::create('user-1');
        $result = UserUpdateResult::failure('User not found');

        $this->onboardingService->expects($this->once())
            ->method('updateUser')
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->updateUser($request));
    }

    public function testSetupInitialPermissions(): void
    {
        $this->onboardingService->expects($this->once())
            ->method('assignToTenant')
            ->with('user-1', 'tenant-1', ['admin'])
            ->willReturn(true);

        $this->assertTrue($this->coordinator->setupInitialPermissions('user-1', 'tenant-1', ['admin']));
    }

    public function testSetupInitialPermissionsFails(): void
    {
        $this->onboardingService->expects($this->once())
            ->method('assignToTenant')
            ->willReturn(false);

        $this->assertFalse($this->coordinator->setupInitialPermissions('user-1', 't-1', []));
    }

    public function testSendWelcomeNotification(): void
    {
        $this->onboardingService->expects($this->once())
            ->method('sendWelcomeNotification')
            ->with('user-1', 'pass')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->sendWelcomeNotification('user-1', 'pass'));
    }

    public function testSendWelcomeNotificationThrows(): void
    {
        $this->onboardingService->expects($this->once())
            ->method('sendWelcomeNotification')
            ->willReturn(false);

        $this->assertFalse($this->coordinator->sendWelcomeNotification('user-1'));
    }
}
