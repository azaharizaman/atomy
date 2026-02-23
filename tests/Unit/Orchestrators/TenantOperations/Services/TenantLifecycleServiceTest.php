<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\TenantOperations\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\TenantOperations\Services\TenantLifecycleService;
use Nexus\TenantOperations\Services\TenantStateManagerInterface;
use Nexus\TenantOperations\Services\UserAccessControllerInterface;
use Nexus\TenantOperations\Services\DataArchiverInterface;
use Nexus\TenantOperations\Services\DataExporterInterface;
use Nexus\TenantOperations\Services\DataDeleterInterface;
use Nexus\TenantOperations\Services\AuditLoggerInterface;
use Nexus\TenantOperations\DTOs\TenantSuspendRequest;
use Nexus\TenantOperations\DTOs\TenantSuspendResult;
use Nexus\TenantOperations\DTOs\TenantActivateRequest;
use Nexus\TenantOperations\DTOs\TenantActivateResult;
use Nexus\TenantOperations\DTOs\TenantArchiveRequest;
use Nexus\TenantOperations\DTOs\TenantArchiveResult;
use Nexus\TenantOperations\DTOs\TenantDeleteRequest;
use Nexus\TenantOperations\DTOs\TenantDeleteResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for TenantLifecycleService
 * 
 * Tests tenant lifecycle operations including:
 * - Tenant suspension
 * - Tenant activation
 * - Tenant archiving
 * - Tenant deletion
 */
final class TenantLifecycleServiceTest extends TestCase
{
    private TenantStateManagerInterface&MockObject $stateManager;
    private UserAccessControllerInterface&MockObject $userAccessController;
    private DataArchiverInterface&MockObject $dataArchiver;
    private DataExporterInterface&MockObject $dataExporter;
    private DataDeleterInterface&MockObject $dataDeleter;
    private AuditLoggerInterface&MockObject $auditLogger;
    private LoggerInterface $logger;
    private TenantLifecycleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateManager = $this->createMock(TenantStateManagerInterface::class);
        $this->userAccessController = $this->createMock(UserAccessControllerInterface::class);
        $this->dataArchiver = $this->createMock(DataArchiverInterface::class);
        $this->dataExporter = $this->createMock(DataExporterInterface::class);
        $this->dataDeleter = $this->createMock(DataDeleterInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = new NullLogger();

        $this->service = new TenantLifecycleService(
            $this->stateManager,
            $this->userAccessController,
            $this->dataArchiver,
            $this->dataExporter,
            $this->dataDeleter,
            $this->auditLogger,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for suspend()
    // =========================================================================

    public function testSuspend_WhenSuccessful_ReturnsSuccessResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantSuspendRequest(
            tenantId: $tenantId,
            suspendedBy: 'admin@example.com',
            reason: 'Payment overdue'
        );

        $this->userAccessController
            ->expects($this->once())
            ->method('disable')
            ->with($tenantId);

        $this->stateManager
            ->expects($this->once())
            ->method('suspend')
            ->with($tenantId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'tenant.suspended',
                $tenantId,
                $this->callback(function ($data) use ($tenantId) {
                    return $data['suspended_by'] === 'admin@example.com'
                        && $data['reason'] === 'Payment overdue';
                })
            );

        // Act
        $result = $this->service->suspend($request);

        // Assert
        $this->assertInstanceOf(TenantSuspendResult::class, $result);
        $this->assertTrue($result->success, 'Expected suspend to succeed');
        $this->assertSame($tenantId, $result->tenantId);
        $this->assertStringContainsString('successfully', $result->message);
    }

    public function testSuspend_WhenStateManagerThrows_ReturnsFailureResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantSuspendRequest(
            tenantId: $tenantId,
            suspendedBy: 'admin@example.com',
        );

        $this->userAccessController
            ->expects($this->once())
            ->method('disable')
            ->with($tenantId);

        $this->stateManager
            ->expects($this->once())
            ->method('suspend')
            ->with($tenantId)
            ->willThrowException(new \RuntimeException('Database error'));

        $this->auditLogger
            ->expects($this->never())
            ->method('log');

        // Act
        $result = $this->service->suspend($request);

        // Assert
        $this->assertFalse($result->success, 'Expected suspend to fail');
        $this->assertStringContainsString('Failed', $result->message);
        $this->assertNull($result->tenantId);
    }

    public function testSuspend_WithoutReason_UsesDefaultReason(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantSuspendRequest(
            tenantId: $tenantId,
            suspendedBy: 'admin@example.com',
            reason: null
        );

        $this->userAccessController
            ->expects($this->once())
            ->method('disable')
            ->with($tenantId);

        $this->stateManager
            ->expects($this->once())
            ->method('suspend')
            ->with($tenantId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'tenant.suspended',
                $tenantId,
                $this->callback(function ($data) {
                    return array_key_exists('reason', $data) && $data['reason'] === null;
                })
            );

        // Act
        $result = $this->service->suspend($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Tests for activate()
    // =========================================================================

    public function testActivate_WhenSuccessful_ReturnsSuccessResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantActivateRequest(
            tenantId: $tenantId,
            activatedBy: 'admin@example.com',
            reason: 'Payment received'
        );

        $this->stateManager
            ->expects($this->once())
            ->method('activate')
            ->with($tenantId);

        $this->userAccessController
            ->expects($this->once())
            ->method('enable')
            ->with($tenantId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'tenant.activated',
                $tenantId,
                $this->callback(function ($data) {
                    return $data['activated_by'] === 'admin@example.com';
                })
            );

        // Act
        $result = $this->service->activate($request);

        // Assert
        $this->assertInstanceOf(TenantActivateResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame($tenantId, $result->tenantId);
    }

    public function testActivate_WhenStateManagerThrows_ReturnsFailureResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantActivateRequest(
            tenantId: $tenantId,
            activatedBy: 'admin@example.com',
        );

        $this->stateManager
            ->expects($this->once())
            ->method('activate')
            ->with($tenantId)
            ->willThrowException(new \RuntimeException('Tenant not found'));

        $this->userAccessController
            ->expects($this->never())
            ->method('enable');

        // Act
        $result = $this->service->activate($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed', $result->message);
    }

    // =========================================================================
    // Tests for archive()
    // =========================================================================

    public function testArchive_WithDataPreservation_ArchivesDataAndReturnsLocation(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $archiveLocation = '/archives/tenant-123-2024-01-01.zip';
        $request = new TenantArchiveRequest(
            tenantId: $tenantId,
            archivedBy: 'admin@example.com',
            preserveData: true,
            reason: 'Tenant migrated to new system'
        );

        $this->dataArchiver
            ->expects($this->once())
            ->method('archive')
            ->with($tenantId)
            ->willReturn($archiveLocation);

        $this->stateManager
            ->expects($this->once())
            ->method('archive')
            ->with($tenantId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'tenant.archived',
                $tenantId,
                $this->callback(function ($data) use ($archiveLocation) {
                    return $data['archive_location'] === $archiveLocation;
                })
            );

        // Act
        $result = $this->service->archive($request);

        // Assert
        $this->assertInstanceOf(TenantArchiveResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame($archiveLocation, $result->archiveLocation);
    }

    public function testArchive_WithoutDataPreservation_SkipsArchiving(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantArchiveRequest(
            tenantId: $tenantId,
            archivedBy: 'admin@example.com',
            preserveData: false,
        );

        $this->dataArchiver
            ->expects($this->never())
            ->method('archive');

        $this->stateManager
            ->expects($this->once())
            ->method('archive')
            ->with($tenantId);

        // Act
        $result = $this->service->archive($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertNull($result->archiveLocation);
    }

    public function testArchive_WhenArchiverThrows_ReturnsFailureResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantArchiveRequest(
            tenantId: $tenantId,
            archivedBy: 'admin@example.com',
            preserveData: true,
        );

        $this->dataArchiver
            ->expects($this->once())
            ->method('archive')
            ->with($tenantId)
            ->willThrowException(new \RuntimeException('Storage full'));

        // Act
        $result = $this->service->archive($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed', $result->message);
    }

    // =========================================================================
    // Tests for delete()
    // =========================================================================

    public function testDelete_WithDataExport_ExportsDataAndDeletes(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $exportPath = '/exports/tenant-123-2024-01-01.json';
        $request = new TenantDeleteRequest(
            tenantId: $tenantId,
            deletedBy: 'admin@example.com',
            exportData: true,
            reason: 'Tenant requested deletion'
        );

        $this->dataExporter
            ->expects($this->once())
            ->method('export')
            ->with($tenantId)
            ->willReturn($exportPath);

        $this->dataDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($tenantId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'tenant.deleted',
                $tenantId,
                $this->callback(function ($data) use ($exportPath) {
                    return $data['export_path'] === $exportPath;
                })
            );

        // Act
        $result = $this->service->delete($request);

        // Assert
        $this->assertInstanceOf(TenantDeleteResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame($exportPath, $result->exportPath);
    }

    public function testDelete_WithoutExport_SkipsExport(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantDeleteRequest(
            tenantId: $tenantId,
            deletedBy: 'admin@example.com',
            exportData: false,
        );

        $this->dataExporter
            ->expects($this->never())
            ->method('export');

        $this->dataDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($tenantId);

        // Act
        $result = $this->service->delete($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertNull($result->exportPath);
    }

    public function testDelete_WhenDeleterThrows_ReturnsFailureResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantDeleteRequest(
            tenantId: $tenantId,
            deletedBy: 'admin@example.com',
            exportData: false,
        );

        $this->dataDeleter
            ->expects($this->once())
            ->method('delete')
            ->with($tenantId)
            ->willThrowException(new \RuntimeException('Foreign key constraint'));

        // Act
        $result = $this->service->delete($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed', $result->message);
    }

    // =========================================================================
    // Tests for enableUserAccess() and disableUserAccess()
    // =========================================================================

    public function testDisableUserAccess_CallsControllerDisable(): void
    {
        // Arrange
        $tenantId = 'tenant-123';

        $this->userAccessController
            ->expects($this->once())
            ->method('disable')
            ->with($tenantId);

        // Act
        $this->service->disableUserAccess($tenantId);
    }

    public function testEnableUserAccess_CallsControllerEnable(): void
    {
        // Arrange
        $tenantId = 'tenant-123';

        $this->userAccessController
            ->expects($this->once())
            ->method('enable')
            ->with($tenantId);

        // Act
        $this->service->enableUserAccess($tenantId);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testSuspend_WithEmptyTenantId_ThrowsException(): void
    {
        // This would fail at the state manager level, but we can test the flow
        $request = new TenantSuspendRequest(
            tenantId: '',
            suspendedBy: 'admin@example.com',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        // Trigger validation through state manager
        $this->stateManager
            ->expects($this->once())
            ->method('suspend')
            ->with('')
            ->willThrowException(new \InvalidArgumentException('Tenant ID cannot be empty'));
            
        $this->service->suspend($request);
    }

    public function testActivate_WithEmptyTenantId_ThrowsException(): void
    {
        $request = new TenantActivateRequest(
            tenantId: '',
            activatedBy: 'admin@example.com',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        $this->stateManager
            ->expects($this->once())
            ->method('activate')
            ->with('')
            ->willThrowException(new \InvalidArgumentException('Tenant ID cannot be empty'));
            
        $this->service->activate($request);
    }

    public function testArchive_WithEmptyTenantId_ThrowsException(): void
    {
        $request = new TenantArchiveRequest(
            tenantId: '',
            archivedBy: 'admin@example.com',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        $this->stateManager
            ->expects($this->once())
            ->method('archive')
            ->with('')
            ->willThrowException(new \InvalidArgumentException('Tenant ID cannot be empty'));
            
        $this->service->archive($request);
    }

    public function testDelete_WithEmptyTenantId_ThrowsException(): void
    {
        $request = new TenantDeleteRequest(
            tenantId: '',
            deletedBy: 'admin@example.com',
        );

        $this->expectException(\InvalidArgumentException::class);
        
        $this->dataDeleter
            ->expects($this->once())
            ->method('delete')
            ->with('')
            ->willThrowException(new \InvalidArgumentException('Tenant ID cannot be empty'));
            
        $this->service->delete($request);
    }

    public function testSuspend_AuditLogsCorrectAction(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $adminUser = 'admin@example.com';
        $reason = 'Test reason';

        $request = new TenantSuspendRequest(
            tenantId: $tenantId,
            suspendedBy: $adminUser,
            reason: $reason
        );

        // Act - just verify the audit is called with correct parameters
        $this->userAccessController->method('disable')->willReturnCallback(function($id) use ($tenantId) {
            $this->assertSame($tenantId, $id);
        });

        $this->stateManager->method('suspend')->willReturnCallback(function($id) use ($tenantId) {
            $this->assertSame($tenantId, $id);
        });

        $capturedLogData = null;
        $this->auditLogger->method('log')->willReturnCallback(function($event, $id, $data) use (&$capturedLogData) {
            $capturedLogData = ['event' => $event, 'tenantId' => $id, 'data' => $data];
        });

        $result = $this->service->suspend($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('tenant.suspended', $capturedLogData['event']);
        $this->assertEquals($tenantId, $capturedLogData['tenantId']);
        $this->assertEquals($adminUser, $capturedLogData['data']['suspended_by']);
        $this->assertEquals($reason, $capturedLogData['data']['reason']);
    }
}
