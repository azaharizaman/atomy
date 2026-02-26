<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\IdentityOperations\Services\UserLifecycleService;
use Nexus\IdentityOperations\Services\UserStateManagerInterface;
use Nexus\IdentityOperations\Services\SessionManagerInterface;
use Nexus\IdentityOperations\Services\AuditLoggerInterface;
use Nexus\IdentityOperations\DTOs\UserSuspendRequest;
use Nexus\IdentityOperations\DTOs\UserActivateRequest;
use Nexus\IdentityOperations\DTOs\UserDeactivateRequest;
use Psr\Log\LoggerInterface;

final class UserLifecycleServiceTest extends TestCase
{
    private readonly UserStateManagerInterface|MockObject $stateManager;
    private readonly SessionManagerInterface|MockObject $sessionManager;
    private readonly AuditLoggerInterface|MockObject $auditLogger;
    private readonly LoggerInterface|MockObject $logger;
    private readonly UserLifecycleService $service;

    protected function setUp(): void
    {
        $this->stateManager = $this->createMock(UserStateManagerInterface::class);
        $this->sessionManager = $this->createMock(SessionManagerInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserLifecycleService(
            $this->stateManager,
            $this->sessionManager,
            $this->auditLogger,
            $this->logger
        );
    }

    public function testSuspendSuccessfully(): void
    {
        $request = new UserSuspendRequest(
            userId: 'user-123',
            tenantId: 'tenant-1',
            suspendedBy: 'admin-1',
            reason: 'Policy violation'
        );

        $this->stateManager->expects($this->once())
            ->method('setAccessEnabled')
            ->with('user-123', false);

        $this->sessionManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1');

        $this->stateManager->expects($this->once())
            ->method('suspend')
            ->with('user-123');

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with('user.suspended', 'user-123');

        $result = $this->service->suspend($request);

        $this->assertTrue($result->success);
    }

    public function testSuspendFailure(): void
    {
        $request = new UserSuspendRequest(userId: 'user-123', tenantId: 'tenant-1', suspendedBy: 'admin-1');
        
        $this->stateManager->expects($this->once())
            ->method('setAccessEnabled')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->suspend($request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed to suspend user', $result->message);
    }

    public function testActivateSuccessfully(): void
    {
        $request = new UserActivateRequest(
            userId: 'user-123',
            tenantId: 'tenant-1',
            activatedBy: 'admin-1'
        );

        $this->stateManager->expects($this->once())
            ->method('activate')
            ->with('user-123');

        $this->stateManager->expects($this->once())
            ->method('setAccessEnabled')
            ->with('user-123', true);

        $result = $this->service->activate($request);

        $this->assertTrue($result->success);
    }

    public function testActivateFailure(): void
    {
        $request = new UserActivateRequest(userId: 'user-123', tenantId: 'tenant-1', activatedBy: 'admin-1');
        
        $this->stateManager->expects($this->once())
            ->method('activate')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->activate($request);

        $this->assertFalse($result->success);
    }

    public function testDeactivateSuccessfully(): void
    {
        $request = new UserDeactivateRequest(
            userId: 'user-123',
            tenantId: 'tenant-1',
            deactivatedBy: 'admin-1'
        );

        $this->stateManager->expects($this->once())
            ->method('deactivate')
            ->with('user-123');

        $this->sessionManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1');

        $result = $this->service->deactivate($request);

        $this->assertTrue($result->success);
    }

    public function testDeactivateFailure(): void
    {
        $request = new UserDeactivateRequest(userId: 'user-123', tenantId: 'tenant-1', deactivatedBy: 'admin-1');
        
        $this->stateManager->expects($this->once())
            ->method('setAccessEnabled')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->deactivate($request);

        $this->assertFalse($result->success);
    }

    public function testForceLogoutSuccessfully(): void
    {
        $this->sessionManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1');

        $result = $this->service->forceLogout('user-123', 'admin-1', 'tenant-1');

        $this->assertTrue($result);
    }

    public function testForceLogoutFailure(): void
    {
        $this->sessionManager->expects($this->once())
            ->method('invalidateUserSessions')
            ->with('user-123', 'tenant-1')
            ->willThrowException(new \Exception('Error'));

        $result = $this->service->forceLogout('user-123', 'admin-1', 'tenant-1');

        $this->assertFalse($result);
    }
}
