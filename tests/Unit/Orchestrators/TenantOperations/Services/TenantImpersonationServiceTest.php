<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\TenantOperations\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\TenantOperations\Services\TenantImpersonationService;
use Nexus\TenantOperations\Services\ImpersonationSessionManagerInterface;
use Nexus\TenantOperations\Services\ImpersonationPermissionCheckerInterface;
use Nexus\TenantOperations\Services\AuditLoggerInterface;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationStartResult;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for TenantImpersonationService
 * 
 * Tests impersonation operations including:
 * - Starting impersonation sessions
 * - Ending impersonation sessions
 * - Checking impersonation status
 * - Retrieving current session
 */
final class TenantImpersonationServiceTest extends TestCase
{
    private ImpersonationSessionManagerInterface&MockObject $sessionManager;
    private ImpersonationPermissionCheckerInterface&MockObject $permissionChecker;
    private AuditLoggerInterface&MockObject $auditLogger;
    private LoggerInterface $logger;
    private TenantImpersonationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionManager = $this->createMock(ImpersonationSessionManagerInterface::class);
        $this->permissionChecker = $this->createMock(ImpersonationPermissionCheckerInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = new NullLogger();

        $this->service = new TenantImpersonationService(
            $this->sessionManager,
            $this->permissionChecker,
            $this->auditLogger,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for startImpersonation()
    // =========================================================================

    public function testStartImpersonation_WhenNoActiveSession_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: 'admin-123',
            targetTenantId: 'tenant-456',
            reason: 'Testing user issue',
            sessionTimeoutMinutes: 60
        );

        $expectedSessionId = 'session-abc-123';
        $expectedExpiresAt = (new \DateTimeImmutable())
            ->add(new \DateInterval('PT60M'))
            ->format(\DateTimeInterface::ISO8601);

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->with('admin-123')
            ->willReturn(false);

        $this->sessionManager
            ->expects($this->once())
            ->method('create')
            ->with(
                adminUserId: 'admin-123',
                targetTenantId: 'tenant-456',
                reason: 'Testing user issue',
                expiresAt: $this->callback(function($expiresAt) use ($expectedExpiresAt) {
                    // Just verify it's a valid ISO8601 string
                    return strtotime($expiresAt) !== false;
                })
            )
            ->willReturn($expectedSessionId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'impersonation.started',
                'tenant-456',
                $this->callback(function($data) {
                    return $data['admin_user_id'] === 'admin-123'
                        && $data['session_id'] === 'session-abc-123';
                })
            );

        // Act
        $result = $this->service->startImpersonation($request);

        // Assert
        $this->assertInstanceOf(ImpersonationStartResult::class, $result);
        $this->assertTrue($result->success, 'Expected impersonation to start successfully');
        $this->assertSame($expectedSessionId, $result->sessionId);
        $this->assertSame('admin-123', $result->adminUserId);
        $this->assertSame('tenant-456', $result->targetTenantId);
        $this->assertNotNull($result->expiresAt);
    }

    public function testStartImpersonation_WhenAlreadyImpersonating_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: 'admin-123',
            targetTenantId: 'tenant-456',
            reason: 'Testing',
        );

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->with('admin-123')
            ->willReturn(true);

        $this->sessionManager
            ->expects($this->never())
            ->method('create');

        $this->auditLogger
            ->expects($this->never())
            ->method('log');

        // Act
        $result = $this->service->startImpersonation($request);

        // Assert
        $this->assertInstanceOf(ImpersonationStartResult::class, $result);
        $this->assertFalse($result->success, 'Expected impersonation to fail');
        $this->assertStringContainsString('already', $result->message);
        $this->assertNull($result->sessionId);
    }

    public function testStartImpersonation_WithoutTimeout_UsesDefaultTimeout(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: 'admin-123',
            targetTenantId: 'tenant-456',
            reason: 'Testing',
            sessionTimeoutMinutes: null
        );

        $expectedSessionId = 'session-new-123';

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->willReturn(false);

        $this->sessionManager
            ->expects($this->once())
            ->method('create')
            ->with(
                adminUserId: 'admin-123',
                targetTenantId: 'tenant-456',
                reason: 'Testing',
                expiresAt: $this->callback(function($expiresAt) {
                    // Default timeout is 30 minutes
                    $createdAt = new \DateTimeImmutable();
                    $expires = new \DateTimeImmutable($expiresAt);
                    $diff = $createdAt->diff($expires);
                    return $diff->i === 30 || $diff->h === 0 && $diff->i === 30;
                })
            )
            ->willReturn($expectedSessionId);

        // Act
        $result = $this->service->startImpersonation($request);

        // Assert
        $this->assertTrue($result->success);
    }

    public function testStartImpersonation_WithCustomTimeout_UsesCustomTimeout(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: 'admin-123',
            targetTenantId: 'tenant-456',
            sessionTimeoutMinutes: 120
        );

        $expectedSessionId = 'session-120min-123';

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->willReturn(false);

        $this->sessionManager
            ->expects($this->once())
            ->method('create')
            ->with(
                adminUserId: 'admin-123',
                targetTenantId: 'tenant-456',
                reason: null,
                expiresAt: $this->callback(function($expiresAt) {
                    $createdAt = new \DateTimeImmutable();
                    $expires = new \DateTimeImmutable($expiresAt);
                    $diff = $createdAt->diff($expires);
                    return $diff->i === 0 && $diff->h === 2;
                })
            )
            ->willReturn($expectedSessionId);

        // Act
        $result = $this->service->startImpersonation($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Tests for endImpersonation()
    // =========================================================================

    public function testEndImpersonation_WhenSessionExists_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new ImpersonationEndRequest(
            adminUserId: 'admin-123',
            sessionId: null,
            reason: 'Completed testing'
        );

        $sessionData = [
            'session_id' => 'session-abc-123',
            'target_tenant_id' => 'tenant-456',
            'expires_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with('admin-123')
            ->willReturn($sessionData);

        $this->sessionManager
            ->expects($this->once())
            ->method('end')
            ->with('session-abc-123');

        $this->sessionManager
            ->expects($this->once())
            ->method('getActionCount')
            ->with('session-abc-123')
            ->willReturn(5);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'impersonation.ended',
                'tenant-456',
                $this->callback(function($data) {
                    return $data['admin_user_id'] === 'admin-123'
                        && $data['session_id'] === 'session-abc-123'
                        && $data['actions_performed'] === 5;
                })
            );

        // Act
        $result = $this->service->endImpersonation($request);

        // Assert
        $this->assertInstanceOf(ImpersonationEndResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('session-abc-123', $result->sessionId);
        $this->assertSame('admin-123', $result->adminUserId);
        $this->assertSame(5, $result->actionsPerformedCount);
    }

    public function testEndImpersonation_WhenNoSession_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new ImpersonationEndRequest(
            adminUserId: 'admin-123',
            sessionId: null,
        );

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with('admin-123')
            ->willReturn(null);

        $this->sessionManager
            ->expects($this->never())
            ->method('end');

        $this->auditLogger
            ->expects($this->never())
            ->method('log');

        // Act
        $result = $this->service->endImpersonation($request);

        // Assert
        $this->assertInstanceOf(ImpersonationEndResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('No active', $result->message);
    }

    public function testEndImpersonation_WhenSessionIdMismatch_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new ImpersonationEndRequest(
            adminUserId: 'admin-123',
            sessionId: 'session-wrong-id',
        );

        $sessionData = [
            'session_id' => 'session-abc-123',
            'target_tenant_id' => 'tenant-456',
            'expires_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with('admin-123')
            ->willReturn($sessionData);

        // Act
        $result = $this->service->endImpersonation($request);

        // Assert
        $this->assertInstanceOf(ImpersonationEndResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('mismatch', $result->message);
    }

    public function testEndImpersonation_WhenSessionIdMatches_Succeeds(): void
    {
        // Arrange
        $request = new ImpersonationEndRequest(
            adminUserId: 'admin-123',
            sessionId: 'session-abc-123',
        );

        $sessionData = [
            'session_id' => 'session-abc-123',
            'target_tenant_id' => 'tenant-456',
            'expires_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with('admin-123')
            ->willReturn($sessionData);

        $this->sessionManager
            ->expects($this->once())
            ->method('end')
            ->with('session-abc-123');

        $this->sessionManager
            ->expects($this->once())
            ->method('getActionCount')
            ->willReturn(0);

        // Act
        $result = $this->service->endImpersonation($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Tests for isImpersonating()
    // =========================================================================

    public function testIsImpersonating_WhenActiveSession_ReturnsTrue(): void
    {
        // Arrange
        $adminUserId = 'admin-123';

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->with($adminUserId)
            ->willReturn(true);

        // Act
        $result = $this->service->isImpersonating($adminUserId);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsImpersonating_WhenNoActiveSession_ReturnsFalse(): void
    {
        // Arrange
        $adminUserId = 'admin-123';

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->with($adminUserId)
            ->willReturn(false);

        // Act
        $result = $this->service->isImpersonating($adminUserId);

        // Assert
        $this->assertFalse($result);
    }

    // =========================================================================
    // Tests for getCurrentSession()
    // =========================================================================

    public function testGetCurrentSession_WhenSessionExists_ReturnsSessionResult(): void
    {
        // Arrange
        $adminUserId = 'admin-123';
        
        $sessionData = [
            'session_id' => 'session-abc-123',
            'target_tenant_id' => 'tenant-456',
            'expires_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with($adminUserId)
            ->willReturn($sessionData);

        // Act
        $result = $this->service->getCurrentSession($adminUserId);

        // Assert
        $this->assertInstanceOf(ImpersonationStartResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('session-abc-123', $result->sessionId);
        $this->assertSame('admin-123', $result->adminUserId);
        $this->assertSame('tenant-456', $result->targetTenantId);
    }

    public function testGetCurrentSession_WhenNoSession_ReturnsNull(): void
    {
        // Arrange
        $adminUserId = 'admin-123';

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with($adminUserId)
            ->willReturn(null);

        // Act
        $result = $this->service->getCurrentSession($adminUserId);

        // Assert
        $this->assertNull($result);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testStartImpersonation_WithEmptyAdminUserId_ThrowsException(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: '',
            targetTenantId: 'tenant-456',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        // The session manager should throw for empty admin user ID
        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->with('')
            ->willThrowException(new \InvalidArgumentException('Admin user ID cannot be empty'));

        $this->service->startImpersonation($request);
    }

    public function testStartImpersonation_WithEmptyTargetTenantId_ThrowsException(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: 'admin-123',
            targetTenantId: '',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->willThrowException(new \InvalidArgumentException('Target tenant ID cannot be empty'));

        $this->service->startImpersonation($request);
    }

    public function testEndImpersonation_WithEmptyAdminUserId_ThrowsException(): void
    {
        // Arrange
        $request = new ImpersonationEndRequest(
            adminUserId: '',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->with('')
            ->willThrowException(new \InvalidArgumentException('Admin user ID cannot be empty'));

        $this->service->endImpersonation($request);
    }

    public function testStartImpersonation_AuditLogsWithReason(): void
    {
        // Arrange
        $request = new ImpersonationStartRequest(
            adminUserId: 'admin-123',
            targetTenantId: 'tenant-456',
            reason: 'Customer support investigation'
        );

        $this->sessionManager
            ->expects($this->once())
            ->method('hasActiveSession')
            ->willReturn(false);

        $this->sessionManager
            ->expects($this->once())
            ->method('create')
            ->willReturn('session-new-123');

        $capturedLog = null;
        $this->auditLogger->method('log')->willReturnCallback(function($event, $tenantId, $data) use (&$capturedLog) {
            $capturedLog = ['event' => $event, 'tenantId' => $tenantId, 'data' => $data];
        });

        // Act
        $result = $this->service->startImpersonation($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('impersonation.started', $capturedLog['event']);
        $this->assertEquals('tenant-456', $capturedLog['tenantId']);
        $this->assertEquals('Customer support investigation', $capturedLog['data']['reason']);
    }

    public function testEndImpersonation_AuditLogsWithActionCount(): void
    {
        // Arrange
        $request = new ImpersonationEndRequest(
            adminUserId: 'admin-123',
            reason: 'Session complete'
        );

        $sessionData = [
            'session_id' => 'session-abc-123',
            'target_tenant_id' => 'tenant-456',
            'expires_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];

        $this->sessionManager
            ->expects($this->once())
            ->method('getActiveSession')
            ->willReturn($sessionData);

        $this->sessionManager
            ->expects($this->once())
            ->method('getActionCount')
            ->willReturn(42);

        $capturedLog = null;
        $this->auditLogger->method('log')->willReturnCallback(function($event, $tenantId, $data) use (&$capturedLog) {
            $capturedLog = ['event' => $event, 'tenantId' => $tenantId, 'data' => $data];
        });

        // Act
        $result = $this->service->endImpersonation($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('impersonation.ended', $capturedLog['event']);
        $this->assertEquals(42, $capturedLog['data']['actions_performed']);
    }
}
