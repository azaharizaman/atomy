<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Compliance;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\ProcurementOperations\DTOs\Audit\AuditFindingData;
use Nexus\ProcurementOperations\DTOs\Audit\ControlTestResultData;
use Nexus\ProcurementOperations\DTOs\Audit\SegregationOfDutiesReportData;
use Nexus\ProcurementOperations\DTOs\Audit\Sox404EvidenceData;
use Nexus\ProcurementOperations\Enums\AuditFindingSeverity;
use Nexus\ProcurementOperations\Enums\ControlArea;
use Nexus\ProcurementOperations\Exceptions\ProcurementAuditException;
use Nexus\ProcurementOperations\Services\Compliance\ProcurementAuditService;
use Nexus\ProcurementOperations\Services\SODValidationService;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcurementAuditService.
 */
final class ProcurementAuditServiceTest extends TestCase
{
    private AuditLogManagerInterface&MockObject $auditLogManager;
    private AuditLogRepositoryInterface&MockObject $auditLogRepository;
    private UserQueryInterface&MockObject $userQuery;
    private RoleQueryInterface&MockObject $roleQuery;
    private SODValidationService&MockObject $sodValidationService;
    private SettingsManager&MockObject $settingsManager;
    private TenantContextInterface&MockObject $tenantContext;
    private ProcurementAuditService $service;

    protected function setUp(): void
    {
        $this->auditLogManager = $this->createMock(AuditLogManagerInterface::class);
        $this->auditLogRepository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->userQuery = $this->createMock(UserQueryInterface::class);
        $this->roleQuery = $this->createMock(RoleQueryInterface::class);
        $this->sodValidationService = $this->createMock(SODValidationService::class);
        $this->settingsManager = $this->createMock(SettingsManager::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);

        $this->tenantContext
            ->method('getCurrentTenantId')
            ->willReturn('tenant-1');

        $this->service = new ProcurementAuditService(
            $this->auditLogManager,
            $this->auditLogRepository,
            $this->userQuery,
            $this->roleQuery,
            $this->sodValidationService,
            $this->settingsManager,
            $this->tenantContext,
        );
    }

    /**
     * Test generates SOX 404 evidence data.
     */
    public function test_generates_sox404_evidence(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-12-31');

        // Mock audit log search to return empty results
        $this->auditLogRepository
            ->method('search')
            ->willReturn([]);

        // Mock SoD validation to return no conflicts
        $this->sodValidationService
            ->method('validateProcessSoD')
            ->willReturn(['is_compliant' => true, 'violations' => []]);

        // Mock role query
        $this->roleQuery
            ->method('findByTenantId')
            ->willReturn([]);

        $result = $this->service->generateSox404Evidence($periodStart, $periodEnd);

        $this->assertInstanceOf(Sox404EvidenceData::class, $result);
        $this->assertIsArray($result->controlTests);
        $this->assertIsArray($result->segregationOfDutiesMatrix);
        $this->assertIsArray($result->findings);
    }

    /**
     * Test get segregation of duties report.
     */
    public function test_get_segregation_of_duties_report(): void
    {
        // Mock SoD validation
        $this->sodValidationService
            ->method('validateProcessSoD')
            ->willReturn([
                'is_compliant' => false,
                'violations' => [
                    [
                        'user_id' => 'user-1',
                        'conflicting_roles' => ['po_creator', 'po_approver'],
                        'severity' => 'high',
                    ],
                ],
            ]);

        // Mock role query
        $this->roleQuery
            ->method('findByTenantId')
            ->willReturn([]);

        $result = $this->service->getSegregationOfDutiesReport();

        $this->assertInstanceOf(SegregationOfDutiesReportData::class, $result);
        $this->assertIsArray($result->violations);
        $this->assertIsArray($result->conflictMatrix);
    }

    /**
     * Test validate approval authority within limit.
     */
    public function test_validate_approval_authority_within_limit(): void
    {
        $userId = 'user-approver';
        $documentType = 'purchase_order';
        $amount = 5000_00; // $5,000 in cents

        $this->settingsManager
            ->method('get')
            ->willReturnCallback(function ($key, $default = null) {
                return match ($key) {
                    'procurement.approval_limits.purchase_order.user-approver' => 10000_00, // $10,000 limit
                    default => $default,
                };
            });

        $result = $this->service->validateApprovalAuthority($userId, $documentType, $amount);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_authorized', $result);
    }

    /**
     * Test record audit finding.
     */
    public function test_record_audit_finding(): void
    {
        $controlArea = ControlArea::APPROVAL_LIMITS;
        $severity = AuditFindingSeverity::HIGH;
        $description = 'Approval bypass detected';
        $affectedDocumentId = 'po-12345';

        $this->auditLogManager
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->anything(),
                $this->stringContains('audit_finding'),
                $this->anything(),
            );

        $result = $this->service->recordAuditFinding(
            $controlArea,
            $severity,
            $description,
            $affectedDocumentId,
        );

        $this->assertInstanceOf(AuditFindingData::class, $result);
        $this->assertSame($controlArea, $result->controlArea);
        $this->assertSame($severity, $result->severity);
        $this->assertSame($description, $result->description);
        $this->assertSame($affectedDocumentId, $result->affectedDocumentId);
        $this->assertFalse($result->isResolved);
    }

    /**
     * Test resolve audit finding.
     */
    public function test_resolve_audit_finding(): void
    {
        $findingId = 'finding-123';
        $resolution = 'Approval process corrected and documented';
        $resolvedBy = 'user-auditor';

        // Mock finding exists in audit log
        $this->auditLogRepository
            ->method('search')
            ->willReturn([
                [
                    'id' => $findingId,
                    'action' => 'audit_finding',
                    'metadata' => [
                        'control_area' => ControlArea::APPROVAL_LIMITS->value,
                        'severity' => AuditFindingSeverity::HIGH->value,
                        'description' => 'Original finding',
                        'is_resolved' => false,
                    ],
                ],
            ]);

        $this->auditLogManager
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->anything(),
                $this->stringContains('audit_finding_resolved'),
                $this->anything(),
            );

        $result = $this->service->resolveAuditFinding($findingId, $resolution, $resolvedBy);

        $this->assertInstanceOf(AuditFindingData::class, $result);
        $this->assertTrue($result->isResolved);
        $this->assertSame($resolution, $result->resolution);
        $this->assertSame($resolvedBy, $result->resolvedBy);
    }

    /**
     * Test resolve non-existent finding throws exception.
     */
    public function test_resolve_non_existent_finding_throws_exception(): void
    {
        $this->expectException(ProcurementAuditException::class);
        $this->expectExceptionMessage('not found');

        $this->auditLogRepository
            ->method('search')
            ->willReturn([]);

        $this->service->resolveAuditFinding('non-existent', 'Resolution', 'user-1');
    }

    /**
     * Test get audit exceptions filtered by severity.
     */
    public function test_get_audit_exceptions_filtered_by_severity(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-12-31');

        $this->auditLogRepository
            ->method('search')
            ->willReturn([
                [
                    'id' => 'finding-1',
                    'action' => 'audit_finding',
                    'created_at' => '2024-06-15',
                    'metadata' => [
                        'control_area' => ControlArea::APPROVAL_LIMITS->value,
                        'severity' => AuditFindingSeverity::HIGH->value,
                        'description' => 'High severity finding',
                        'is_resolved' => false,
                    ],
                ],
                [
                    'id' => 'finding-2',
                    'action' => 'audit_finding',
                    'created_at' => '2024-07-20',
                    'metadata' => [
                        'control_area' => ControlArea::THREE_WAY_MATCH->value,
                        'severity' => AuditFindingSeverity::LOW->value,
                        'description' => 'Low severity finding',
                        'is_resolved' => false,
                    ],
                ],
            ]);

        $result = $this->service->getAuditExceptions(
            $periodStart,
            $periodEnd,
            null,
            AuditFindingSeverity::HIGH,
        );

        $this->assertIsArray($result);
    }

    /**
     * Test get control test results for specific area.
     */
    public function test_get_control_test_results(): void
    {
        $controlArea = ControlArea::THREE_WAY_MATCH;
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-12-31');

        $this->auditLogRepository
            ->method('search')
            ->willReturn([]);

        $result = $this->service->getControlTestResults($controlArea, $periodStart, $periodEnd);

        $this->assertInstanceOf(ControlTestResultData::class, $result);
        $this->assertSame($controlArea, $result->controlArea);
        $this->assertIsInt($result->sampleSize);
        $this->assertIsInt($result->passedCount);
        $this->assertIsInt($result->failedCount);
    }

    /**
     * Test get management representation data.
     */
    public function test_get_management_representation_data(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-12-31');

        $this->auditLogRepository
            ->method('search')
            ->willReturn([]);

        $this->sodValidationService
            ->method('validateProcessSoD')
            ->willReturn(['is_compliant' => true, 'violations' => []]);

        $this->roleQuery
            ->method('findByTenantId')
            ->willReturn([]);

        $result = $this->service->getManagementRepresentationData($periodStart, $periodEnd);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('period_start', $result);
        $this->assertArrayHasKey('period_end', $result);
        $this->assertArrayHasKey('control_summary', $result);
        $this->assertArrayHasKey('significant_deficiencies', $result);
        $this->assertArrayHasKey('material_weaknesses', $result);
    }

    /**
     * Test get three-way match audit trail.
     */
    public function test_get_three_way_match_audit_trail(): void
    {
        $purchaseOrderId = 'po-12345';

        $this->auditLogRepository
            ->method('search')
            ->willReturn([
                [
                    'id' => 'log-1',
                    'action' => 'three_way_match.initiated',
                    'entity_id' => $purchaseOrderId,
                    'created_at' => '2024-06-15 10:00:00',
                    'metadata' => ['status' => 'pending'],
                ],
                [
                    'id' => 'log-2',
                    'action' => 'three_way_match.completed',
                    'entity_id' => $purchaseOrderId,
                    'created_at' => '2024-06-15 14:00:00',
                    'metadata' => ['status' => 'matched', 'variance' => 0],
                ],
            ]);

        $result = $this->service->getThreeWayMatchAuditTrail($purchaseOrderId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('purchase_order_id', $result);
        $this->assertArrayHasKey('audit_entries', $result);
    }
}
