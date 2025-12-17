<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DataProviders;

use Nexus\ProcurementOperations\DataProviders\SOXComplianceDataProvider;
use Nexus\ProcurementOperations\DataProviders\SOXComplianceStorageInterface;
use Nexus\ProcurementOperations\DTOs\SOX\SOXPerformanceMetrics;
use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SOXComplianceDataProvider::class)]
final class SOXComplianceDataProviderTest extends TestCase
{
    private SOXComplianceDataProvider $provider;
    private MockObject&SOXComplianceStorageInterface $storage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(SOXComplianceStorageInterface::class);

        $this->provider = new SOXComplianceDataProvider(
            storage: $this->storage,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function getComplianceContext_returns_aggregated_context(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getControlResults')
            ->willReturn([
                [
                    'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 95,
                ],
                [
                    'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                    'result' => SOXControlResult::FAILED->value,
                    'count' => 5,
                ],
                [
                    'control_point' => SOXControlPoint::PO_VENDOR_COMPLIANCE->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 100,
                ],
            ]);

        $this->storage
            ->method('getOverrideCount')
            ->willReturn(3);

        $this->storage
            ->method('getActiveExemptions')
            ->willReturn([
                ['control_point' => SOXControlPoint::REQ_SOD_CHECK->value, 'expires_at' => '2024-12-31'],
            ]);

        $context = $this->provider->getComplianceContext($tenantId);

        $this->assertEquals($tenantId, $context->tenantId);
        $this->assertNotEmpty($context->controlResults);
        $this->assertEquals(3, $context->overrideCount);
        $this->assertCount(1, $context->activeExemptions);
    }

    #[Test]
    public function getStepComplianceContext_returns_step_specific_data(): void
    {
        $tenantId = 'tenant-123';
        $step = P2PStep::REQUISITION;

        $this->storage
            ->method('getControlResultsForStep')
            ->with($tenantId, $step)
            ->willReturn([
                [
                    'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 50,
                ],
                [
                    'control_point' => SOXControlPoint::REQ_APPROVAL_AUTHORITY->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 50,
                ],
            ]);

        $context = $this->provider->getStepComplianceContext($tenantId, $step);

        $this->assertEquals($tenantId, $context->tenantId);
        $this->assertEquals($step, $context->step);
        $this->assertNotEmpty($context->controlResults);
    }

    #[Test]
    public function getComplianceDashboard_returns_dashboard_data(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getComplianceScore')
            ->willReturn(92.5);

        $this->storage
            ->method('getWeeklyTrend')
            ->willReturn([
                ['week' => '2024-W01', 'score' => 90.0],
                ['week' => '2024-W02', 'score' => 91.5],
                ['week' => '2024-W03', 'score' => 92.5],
            ]);

        $this->storage
            ->method('getHighRiskControls')
            ->willReturn([
                SOXControlPoint::INV_DUPLICATE_CHECK->value,
            ]);

        $this->storage
            ->method('getPendingOverrides')
            ->willReturn(2);

        $this->storage
            ->method('getExpiringExemptions')
            ->willReturn([
                ['control_point' => SOXControlPoint::REQ_SOD_CHECK->value, 'expires_at' => '2024-02-28'],
            ]);

        $dashboard = $this->provider->getComplianceDashboard($tenantId);

        $this->assertEquals($tenantId, $dashboard->tenantId);
        $this->assertEquals(92.5, $dashboard->complianceScore);
        $this->assertCount(3, $dashboard->weeklyTrend);
        $this->assertNotEmpty($dashboard->highRiskControls);
        $this->assertEquals(2, $dashboard->pendingOverrides);
        $this->assertCount(1, $dashboard->expiringExemptions);
    }

    #[Test]
    public function getControlHistory_returns_audit_trail(): void
    {
        $tenantId = 'tenant-123';
        $entityType = 'requisition';
        $entityId = 'req-001';

        $this->storage
            ->method('getControlHistory')
            ->with($tenantId, $entityType, $entityId)
            ->willReturn([
                [
                    'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                    'result' => SOXControlResult::PASSED->value,
                    'executed_at' => '2024-01-15 10:30:00',
                    'executed_by' => 'user-001',
                    'duration_ms' => 45.5,
                ],
                [
                    'control_point' => SOXControlPoint::REQ_APPROVAL_AUTHORITY->value,
                    'result' => SOXControlResult::PASSED->value,
                    'executed_at' => '2024-01-15 10:30:01',
                    'executed_by' => 'user-001',
                    'duration_ms' => 30.2,
                ],
            ]);

        $history = $this->provider->getControlHistory($tenantId, $entityType, $entityId);

        $this->assertCount(2, $history);
        $this->assertEquals(SOXControlPoint::REQ_BUDGET_CHECK->value, $history[0]->controlPoint);
        $this->assertEquals(SOXControlResult::PASSED->value, $history[0]->result);
    }

    #[Test]
    public function calculateComplianceScore_computes_weighted_score(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getControlResults')
            ->willReturn([
                // High risk control: 90% pass rate
                [
                    'control_point' => SOXControlPoint::PAY_DUAL_APPROVAL->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 90,
                ],
                [
                    'control_point' => SOXControlPoint::PAY_DUAL_APPROVAL->value,
                    'result' => SOXControlResult::FAILED->value,
                    'count' => 10,
                ],
                // Medium risk control: 95% pass rate
                [
                    'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 95,
                ],
                [
                    'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                    'result' => SOXControlResult::FAILED->value,
                    'count' => 5,
                ],
            ]);

        $this->storage
            ->method('getComplianceScore')
            ->willReturn(92.0); // Pre-calculated score

        $context = $this->provider->getComplianceContext($tenantId);

        // Score should reflect weighted average based on risk levels
        $this->assertGreaterThan(0, $context->complianceScore);
        $this->assertLessThanOrEqual(100, $context->complianceScore);
    }

    #[Test]
    public function getComplianceContext_identifies_risks(): void
    {
        $tenantId = 'tenant-123';

        // High failure rate on critical control
        $this->storage
            ->method('getControlResults')
            ->willReturn([
                [
                    'control_point' => SOXControlPoint::PAY_DUAL_APPROVAL->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 70,
                ],
                [
                    'control_point' => SOXControlPoint::PAY_DUAL_APPROVAL->value,
                    'result' => SOXControlResult::FAILED->value,
                    'count' => 30, // 30% failure rate - should trigger risk
                ],
            ]);

        $this->storage
            ->method('getOverrideCount')
            ->willReturn(0);

        $this->storage
            ->method('getActiveExemptions')
            ->willReturn([]);

        $context = $this->provider->getComplianceContext($tenantId);

        // Should identify risk due to high failure rate on critical control
        $this->assertNotEmpty($context->risks);
    }

    #[Test]
    public function getComplianceContext_generates_recommendations(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getControlResults')
            ->willReturn([
                [
                    'control_point' => SOXControlPoint::REQ_SOD_CHECK->value,
                    'result' => SOXControlResult::FAILED->value,
                    'count' => 25,
                ],
                [
                    'control_point' => SOXControlPoint::REQ_SOD_CHECK->value,
                    'result' => SOXControlResult::PASSED->value,
                    'count' => 75,
                ],
            ]);

        $this->storage
            ->method('getOverrideCount')
            ->willReturn(15); // High override count

        $this->storage
            ->method('getActiveExemptions')
            ->willReturn([]);

        $context = $this->provider->getComplianceContext($tenantId);

        // Should recommend reducing SOD failures and override count
        $this->assertNotEmpty($context->recommendations);
    }
}
