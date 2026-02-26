<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
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
    private $onboardingService;
    private $contextDataProvider;
    private $logger;
    private $coordinator;

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

    public function testUpdateUser(): void
    {
        $request = new UserUpdateRequest(userId: 'user-1', firstName: 'Jane');
        $result = UserUpdateResult::success('user-1');

        $this->onboardingService->expects($this->once())
            ->method('updateUser')
            ->with($request)
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

    public function testSendWelcomeNotification(): void
    {
        $this->onboardingService->expects($this->once())
            ->method('sendWelcomeNotification')
            ->with('user-1', 'pass')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->sendWelcomeNotification('user-1', 'pass'));
    }
}
