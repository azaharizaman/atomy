<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Coordinators\UserLifecycleCoordinator;
use Nexus\IdentityOperations\Contracts\UserLifecycleServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserSuspendRequest;
use Nexus\IdentityOperations\DTOs\UserSuspendResult;
use Nexus\IdentityOperations\DTOs\UserActivateRequest;
use Nexus\IdentityOperations\DTOs\UserActivateResult;
use Nexus\IdentityOperations\DTOs\UserDeactivateRequest;
use Nexus\IdentityOperations\DTOs\UserDeactivateResult;
use Psr\Log\LoggerInterface;

final class UserLifecycleCoordinatorTest extends TestCase
{
    private $lifecycleService;
    private $contextDataProvider;
    private $logger;
    private $coordinator;

    protected function setUp(): void
    {
        $this->lifecycleService = $this->createMock(UserLifecycleServiceInterface::class);
        $this->contextDataProvider = $this->createMock(UserContextProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new UserLifecycleCoordinator(
            $this->lifecycleService,
            $this->contextDataProvider,
            $this->logger
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('UserLifecycleCoordinator', $this->coordinator->getName());
    }

    public function testHasRequiredData(): void
    {
        $this->contextDataProvider->expects($this->once())
            ->method('userExists')
            ->with('user-1')
            ->willReturn(true);
        $this->assertTrue($this->coordinator->hasRequiredData('user-1'));
    }

    public function testSuspend(): void
    {
        $request = new UserSuspendRequest(userId: 'user-123', suspendedBy: 'admin-1');
        $result = UserSuspendResult::success('user-123');

        $this->lifecycleService->expects($this->once())
            ->method('suspend')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->suspend($request));
    }

    public function testActivate(): void
    {
        $request = new UserActivateRequest(userId: 'user-123', activatedBy: 'admin-1');
        $result = UserActivateResult::success('user-123');

        $this->lifecycleService->expects($this->once())
            ->method('activate')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->activate($request));
    }

    public function testDeactivate(): void
    {
        $request = new UserDeactivateRequest(userId: 'user-123', deactivatedBy: 'admin-1');
        $result = UserDeactivateResult::success('user-123');

        $this->lifecycleService->expects($this->once())
            ->method('deactivate')
            ->with($request)
            ->willReturn($result);

        $this->assertSame($result, $this->coordinator->deactivate($request));
    }

    public function testForceLogout(): void
    {
        $this->lifecycleService->expects($this->once())
            ->method('forceLogout')
            ->with('user-1', 'admin-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->forceLogout('user-1', 'admin-1'));
    }
}
