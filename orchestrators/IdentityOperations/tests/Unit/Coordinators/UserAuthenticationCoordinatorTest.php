<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\TestCase;
use Nexus\IdentityOperations\Coordinators\UserAuthenticationCoordinator;
use Nexus\IdentityOperations\Contracts\UserAuthenticationServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserContext;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class UserAuthenticationCoordinatorTest extends TestCase
{
    private UserAuthenticationServiceInterface|MockObject $authService;
    private UserContextProviderInterface|MockObject $contextDataProvider;
    private LoggerInterface|MockObject $logger;
    private UserAuthenticationCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(UserAuthenticationServiceInterface::class);
        $this->contextDataProvider = $this->createMock(UserContextProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new UserAuthenticationCoordinator(
            $this->authService,
            $this->contextDataProvider,
            $this->logger
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('UserAuthenticationCoordinator', $this->coordinator->getName());
    }

    public function testHasRequiredData(): void
    {
        $this->contextDataProvider->expects($this->once())
            ->method('userExists')
            ->with('user-123')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->hasRequiredData('user-123'));
    }

    public function testAuthenticate(): void
    {
        $context = new UserContext(
            userId: 'user-123',
            email: 'test@example.com',
            firstName: 'John',
            lastName: 'Doe',
            tenantId: 'tenant-1',
            status: 'active'
        );

        $this->authService->expects($this->once())
            ->method('authenticate')
            ->with('test@example.com', 'password', 'tenant-1')
            ->willReturn($context);

        $result = $this->coordinator->authenticate('test@example.com', 'password', 'tenant-1');

        $this->assertSame($context, $result);
    }

    public function testRefreshToken(): void
    {
        $context = new UserContext(
            userId: 'user-123',
            email: 'test@example.com',
            firstName: 'John',
            lastName: 'Doe',
            tenantId: 'tenant-1',
            status: 'active'
        );

        $this->authService->expects($this->once())
            ->method('refreshToken')
            ->with('token', 'tenant-1')
            ->willReturn($context);

        $result = $this->coordinator->refreshToken('token', 'tenant-1');

        $this->assertSame($context, $result);
    }

    public function testLogout(): void
    {
        $this->authService->expects($this->once())
            ->method('logout')
            ->with('user-123', 'session-1', 'tenant-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->logout('user-123', 'session-1', 'tenant-1'));
    }

    public function testValidateSession(): void
    {
        $this->authService->expects($this->once())
            ->method('validateSession')
            ->with('session-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->validateSession('session-1'));
    }

    public function testChangePassword(): void
    {
        $this->authService->expects($this->once())
            ->method('changePassword')
            ->with('user-1', 'old', 'new')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->changePassword('user-1', 'old', 'new'));
    }

    public function testResetPassword(): void
    {
        $this->authService->expects($this->once())
            ->method('resetPassword')
            ->with('user-1', 'new', 'admin-1')
            ->willReturn(true);

        $this->assertTrue($this->coordinator->resetPassword('user-1', 'new', 'admin-1'));
    }
}
