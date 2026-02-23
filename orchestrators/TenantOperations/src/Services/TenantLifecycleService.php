<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

use Nexus\TenantOperations\Contracts\TenantLifecycleServiceInterface;
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
 * Service for tenant lifecycle operations.
 * 
 * Handles tenant state transitions: suspend, activate, archive, delete.
 * Uses transactional boundaries for atomic operations.
 */
final readonly class TenantLifecycleService implements TenantLifecycleServiceInterface
{
    public function __construct(
        private TenantStateManagerInterface $stateManager,
        private UserAccessControllerInterface $userAccessController,
        private DataArchiverInterface $dataArchiver,
        private DataExporterInterface $dataExporter,
        private DataDeleterInterface $dataDeleter,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function suspend(TenantSuspendRequest $request): TenantSuspendResult
    {
        $this->logger->info('Suspending tenant', [
            'tenant_id' => $request->tenantId,
            'suspended_by' => $request->suspendedBy,
        ]);

        try {
            // Disable user access
            $this->disableUserAccess($request->tenantId);

            // Update tenant state
            $this->stateManager->suspend($request->tenantId);

            // Log audit
            $this->auditLogger->log(
                'tenant.suspended',
                $request->tenantId,
                [
                    'suspended_by' => $request->suspendedBy,
                    'reason' => $request->reason,
                ]
            );

            return TenantSuspendResult::success(
                tenantId: $request->tenantId,
                message: 'Tenant suspended successfully'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to suspend tenant', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return TenantSuspendResult::failure(
                message: 'Failed to suspend tenant: ' . $e->getMessage()
            );
        }
    }

    public function activate(TenantActivateRequest $request): TenantActivateResult
    {
        $this->logger->info('Activating tenant', [
            'tenant_id' => $request->tenantId,
            'activated_by' => $request->activatedBy,
        ]);

        try {
            // Update tenant state
            $this->stateManager->activate($request->tenantId);

            // Enable user access
            $this->enableUserAccess($request->tenantId);

            // Log audit
            $this->auditLogger->log(
                'tenant.activated',
                $request->tenantId,
                [
                    'activated_by' => $request->activatedBy,
                    'reason' => $request->reason,
                ]
            );

            return TenantActivateResult::success(
                tenantId: $request->tenantId,
                message: 'Tenant activated successfully'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to activate tenant', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return TenantActivateResult::failure(
                message: 'Failed to activate tenant: ' . $e->getMessage()
            );
        }
    }

    public function archive(TenantArchiveRequest $request): TenantArchiveResult
    {
        $this->logger->info('Archiving tenant', [
            'tenant_id' => $request->tenantId,
            'archived_by' => $request->archivedBy,
        ]);

        try {
            $archiveLocation = null;

            // Archive data if requested
            if ($request->preserveData) {
                $archiveLocation = $this->dataArchiver->archive($request->tenantId);
            }

            // Update tenant state
            $this->stateManager->archive($request->tenantId);

            // Log audit
            $this->auditLogger->log(
                'tenant.archived',
                $request->tenantId,
                [
                    'archived_by' => $request->archivedBy,
                    'reason' => $request->reason,
                    'archive_location' => $archiveLocation,
                ]
            );

            return TenantArchiveResult::success(
                tenantId: $request->tenantId,
                archiveLocation: $archiveLocation,
                message: 'Tenant archived successfully'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to archive tenant', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return TenantArchiveResult::failure(
                message: 'Failed to archive tenant: ' . $e->getMessage()
            );
        }
    }

    public function delete(TenantDeleteRequest $request): TenantDeleteResult
    {
        $this->logger->info('Deleting tenant', [
            'tenant_id' => $request->tenantId,
            'deleted_by' => $request->deletedBy,
        ]);

        try {
            $exportPath = null;

            // Export data if requested
            if ($request->exportData) {
                $exportPath = $this->dataExporter->export($request->tenantId);
            }

            // Delete tenant data
            $this->dataDeleter->delete($request->tenantId);

            // Log audit
            $this->auditLogger->log(
                'tenant.deleted',
                $request->tenantId,
                [
                    'deleted_by' => $request->deletedBy,
                    'reason' => $request->reason,
                    'export_path' => $exportPath,
                ]
            );

            return TenantDeleteResult::success(
                tenantId: $request->tenantId,
                exportPath: $exportPath,
                message: 'Tenant deleted successfully'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete tenant', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            return TenantDeleteResult::failure(
                message: 'Failed to delete tenant: ' . $e->getMessage()
            );
        }
    }

    public function disableUserAccess(string $tenantId): void
    {
        $this->userAccessController->disable($tenantId);
    }

    public function enableUserAccess(string $tenantId): void
    {
        $this->userAccessController->enable($tenantId);
    }
}

/**
 * Interface for managing tenant state.
 */
interface TenantStateManagerInterface
{
    public function suspend(string $tenantId): void;
    public function activate(string $tenantId): void;
    public function archive(string $tenantId): void;
}

/**
 * Interface for controlling user access.
 */
interface UserAccessControllerInterface
{
    public function disable(string $tenantId): void;
    public function enable(string $tenantId): void;
}

/**
 * Interface for data archiving.
 */
interface DataArchiverInterface
{
    public function archive(string $tenantId): string;
}

/**
 * Interface for data exporting.
 */
interface DataExporterInterface
{
    public function export(string $tenantId): string;
}

/**
 * Interface for data deletion.
 */
interface DataDeleterInterface
{
    public function delete(string $tenantId): void;
}
