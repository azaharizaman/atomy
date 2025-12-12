<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\SOXPerformanceMonitorInterface;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationRequest;
use Nexus\ProcurementOperations\DTOs\SOX\SOXOverrideRequest;
use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use Nexus\ProcurementOperations\Services\SODValidationServiceInterface;
use Nexus\ProcurementOperations\Services\SOXControlPointService;
use Nexus\ProcurementOperations\Services\SOXOverrideStorageInterface;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(SOXControlPointService::class)]
final class SOXControlPointServiceTest extends TestCase
{
    private SOXControlPointService $service;
    private MockObject&SettingsManagerInterface $settings;
    private MockObject&EventDispatcherInterface $eventDispatcher;
    private MockObject&SOXPerformanceMonitorInterface $performanceMonitor;
    private MockObject&SOXOverrideStorageInterface $overrideStorage;
    private MockObject&SODValidationServiceInterface $sodValidator;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(SettingsManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->performanceMonitor = $this->createMock(SOXPerformanceMonitorInterface::class);
        $this->overrideStorage = $this->createMock(SOXOverrideStorageInterface::class);
        $this->sodValidator = $this->createMock(SODValidationServiceInterface::class);

        $this->service = new SOXControlPointService(
            settings: $this->settings,
            eventDispatcher: $this->eventDispatcher,
            performanceMonitor: $this->performanceMonitor,
            overrideStorage: $this->overrideStorage,
            sodValidator: $this->sodValidator,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function validate_returns_skipped_when_sox_disabled(): void
    {
        $tenantId = 'tenant-123';

        $this->settings
            ->method('get')
            ->with("sox.enabled.{$tenantId}", false)
            ->willReturn(false);

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(1000, 'USD'),
        );

        $response = $this->service->validate($request);

        $this->assertFalse($response->allPassed);
        $this->assertTrue($response->skipped);
    }

    #[Test]
    public function validate_returns_passed_when_all_controls_pass(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureAllControlsEnabled($tenantId);

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(1000, 'USD'),
            approvers: ['approver-001'],
        );

        // Mock SOD validation to pass
        $this->sodValidator
            ->method('validateSegregation')
            ->willReturn(true);

        $response = $this->service->validate($request);

        // Should not be skipped when SOX is enabled
        $this->assertFalse($response->skipped);
    }

    #[Test]
    public function validateControl_returns_passed_for_valid_budget_check(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureControlEnabled($tenantId, SOXControlPoint::REQ_BUDGET_CHECK);

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(1000, 'USD'),
            budgetAvailable: Money::of(5000, 'USD'),
        );

        $result = $this->service->validateControl($request, SOXControlPoint::REQ_BUDGET_CHECK);

        $this->assertEquals(SOXControlResult::PASSED, $result->result);
    }

    #[Test]
    public function validateControl_returns_failed_for_insufficient_budget(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureControlEnabled($tenantId, SOXControlPoint::REQ_BUDGET_CHECK);

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(10000, 'USD'),
            budgetAvailable: Money::of(5000, 'USD'),
        );

        $result = $this->service->validateControl($request, SOXControlPoint::REQ_BUDGET_CHECK);

        $this->assertEquals(SOXControlResult::FAILED, $result->result);
        $this->assertStringContainsString('Budget', $result->message);
    }

    #[Test]
    public function validateControl_returns_failed_for_sod_violation(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureControlEnabled($tenantId, SOXControlPoint::REQ_SOD_CHECK);

        $this->sodValidator
            ->method('validateSegregation')
            ->willReturn(false);

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(1000, 'USD'),
            approvers: ['user-001'], // Same as user - SOD violation
        );

        $result = $this->service->validateControl($request, SOXControlPoint::REQ_SOD_CHECK);

        $this->assertEquals(SOXControlResult::FAILED, $result->result);
    }

    #[Test]
    public function validateControl_records_performance_metrics(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureControlEnabled($tenantId, SOXControlPoint::REQ_BUDGET_CHECK);

        $this->performanceMonitor
            ->expects($this->once())
            ->method('recordValidation')
            ->with(
                $tenantId,
                SOXControlPoint::REQ_BUDGET_CHECK,
                true,
                $this->anything(),
                $this->anything(),
            );

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(1000, 'USD'),
            budgetAvailable: Money::of(5000, 'USD'),
        );

        $this->service->validateControl($request, SOXControlPoint::REQ_BUDGET_CHECK);
    }

    #[Test]
    public function validateControl_dispatches_event_on_failure(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureControlEnabled($tenantId, SOXControlPoint::REQ_BUDGET_CHECK);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof \Nexus\ProcurementOperations\Events\SOX\SOXControlFailedEvent;
            }));

        $request = SOXControlValidationRequest::forRequisition(
            tenantId: $tenantId,
            entityId: 'req-001',
            userId: 'user-001',
            amount: Money::of(10000, 'USD'),
            budgetAvailable: Money::of(5000, 'USD'),
        );

        $this->service->validateControl($request, SOXControlPoint::REQ_BUDGET_CHECK);
    }

    #[Test]
    public function requestOverride_stores_pending_override(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);

        $this->overrideStorage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (array $override) {
                return $override['status'] === 'pending'
                    && $override['control_point'] === SOXControlPoint::REQ_BUDGET_CHECK->value;
            }))
            ->willReturn('override-001');

        $overrideRequest = new SOXOverrideRequest(
            tenantId: $tenantId,
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            entityType: 'requisition',
            entityId: 'req-001',
            requesterId: 'user-001',
            justification: 'Emergency procurement required',
        );

        $result = $this->service->requestOverride($overrideRequest);

        $this->assertEquals('pending', $result->status);
    }

    #[Test]
    public function approveOverride_enforces_sod(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);

        $this->overrideStorage
            ->method('find')
            ->willReturn([
                'id' => 'override-001',
                'tenant_id' => $tenantId,
                'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                'entity_type' => 'requisition',
                'entity_id' => 'req-001',
                'requester_id' => 'user-001',
                'status' => 'pending',
            ]);

        // Same user trying to approve their own override
        $result = $this->service->approveOverride('override-001', 'user-001');

        $this->assertEquals('denied', $result->status);
        $this->assertStringContainsString('segregation', strtolower($result->message));
    }

    #[Test]
    public function approveOverride_allows_different_user(): void
    {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);

        $this->overrideStorage
            ->method('find')
            ->willReturn([
                'id' => 'override-001',
                'tenant_id' => $tenantId,
                'control_point' => SOXControlPoint::REQ_BUDGET_CHECK->value,
                'entity_type' => 'requisition',
                'entity_id' => 'req-001',
                'requester_id' => 'user-001',
                'status' => 'pending',
            ]);

        $this->overrideStorage
            ->expects($this->once())
            ->method('update');

        // Different user approving
        $result = $this->service->approveOverride('override-001', 'manager-001');

        $this->assertEquals('approved', $result->status);
    }

    #[Test]
    public function isSOXComplianceEnabled_returns_setting_value(): void
    {
        $tenantId = 'tenant-123';

        $this->settings
            ->method('get')
            ->with("sox.enabled.{$tenantId}", false)
            ->willReturn(true);

        $this->assertTrue($this->service->isSOXComplianceEnabled($tenantId));
    }

    #[Test]
    #[DataProvider('controlPointProvider')]
    public function validateControl_handles_all_control_points(
        SOXControlPoint $controlPoint,
        P2PStep $expectedStep,
    ): void {
        $tenantId = 'tenant-123';

        $this->configureSoxEnabled($tenantId);
        $this->configureControlEnabled($tenantId, $controlPoint);

        $request = $this->createRequestForStep($expectedStep, $tenantId);

        // Should not throw exception
        $result = $this->service->validateControl($request, $controlPoint);

        $this->assertInstanceOf(
            \Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResult::class,
            $result,
        );
    }

    /**
     * @return iterable<array{SOXControlPoint, P2PStep}>
     */
    public static function controlPointProvider(): iterable
    {
        yield 'REQ_BUDGET_CHECK' => [SOXControlPoint::REQ_BUDGET_CHECK, P2PStep::REQUISITION];
        yield 'REQ_APPROVAL_AUTHORITY' => [SOXControlPoint::REQ_APPROVAL_AUTHORITY, P2PStep::REQUISITION];
        yield 'REQ_SOD_CHECK' => [SOXControlPoint::REQ_SOD_CHECK, P2PStep::REQUISITION];
        yield 'PO_VENDOR_COMPLIANCE' => [SOXControlPoint::PO_VENDOR_COMPLIANCE, P2PStep::PO_CREATION];
        yield 'PO_PRICE_VARIANCE' => [SOXControlPoint::PO_PRICE_VARIANCE, P2PStep::PO_CREATION];
        yield 'GR_QUANTITY_TOLERANCE' => [SOXControlPoint::GR_QUANTITY_TOLERANCE, P2PStep::GOODS_RECEIPT];
        yield 'INV_THREE_WAY_MATCH' => [SOXControlPoint::INV_THREE_WAY_MATCH, P2PStep::INVOICE_MATCH];
        yield 'PAY_DUAL_APPROVAL' => [SOXControlPoint::PAY_DUAL_APPROVAL, P2PStep::PAYMENT];
    }

    private function configureSoxEnabled(string $tenantId): void
    {
        $this->settings
            ->method('get')
            ->willReturnCallback(function (string $key, $default) use ($tenantId) {
                if ($key === "sox.enabled.{$tenantId}") {
                    return true;
                }
                if (str_starts_with($key, 'sox.control.')) {
                    return true;
                }
                if (str_contains($key, 'timeout')) {
                    return 200;
                }
                return $default;
            });
    }

    private function configureControlEnabled(string $tenantId, SOXControlPoint $control): void
    {
        $this->settings
            ->method('get')
            ->willReturnCallback(function (string $key, $default) use ($tenantId, $control) {
                if ($key === "sox.enabled.{$tenantId}") {
                    return true;
                }
                if ($key === "sox.control.{$tenantId}.{$control->value}") {
                    return true;
                }
                if (str_contains($key, 'timeout')) {
                    return 200;
                }
                return $default;
            });
    }

    private function configureAllControlsEnabled(string $tenantId): void
    {
        $this->settings
            ->method('get')
            ->willReturnCallback(function (string $key, $default) use ($tenantId) {
                if ($key === "sox.enabled.{$tenantId}") {
                    return true;
                }
                if (str_starts_with($key, "sox.control.{$tenantId}.")) {
                    return true;
                }
                if (str_contains($key, 'timeout')) {
                    return 200;
                }
                return $default;
            });
    }

    private function createRequestForStep(P2PStep $step, string $tenantId): SOXControlValidationRequest
    {
        return match ($step) {
            P2PStep::REQUISITION => SOXControlValidationRequest::forRequisition(
                tenantId: $tenantId,
                entityId: 'req-001',
                userId: 'user-001',
                amount: Money::of(1000, 'USD'),
                budgetAvailable: Money::of(5000, 'USD'),
                approvers: ['approver-001'],
            ),
            P2PStep::PO_CREATION => SOXControlValidationRequest::forPOCreation(
                tenantId: $tenantId,
                entityId: 'po-001',
                userId: 'user-001',
                amount: Money::of(1000, 'USD'),
                vendorId: 'vendor-001',
            ),
            P2PStep::GOODS_RECEIPT => SOXControlValidationRequest::forGoodsReceipt(
                tenantId: $tenantId,
                entityId: 'gr-001',
                userId: 'user-001',
                poId: 'po-001',
            ),
            P2PStep::INVOICE_MATCH => SOXControlValidationRequest::forInvoiceMatch(
                tenantId: $tenantId,
                entityId: 'inv-001',
                userId: 'user-001',
                invoiceAmount: Money::of(1000, 'USD'),
                vendorId: 'vendor-001',
            ),
            P2PStep::PAYMENT => SOXControlValidationRequest::forPayment(
                tenantId: $tenantId,
                entityId: 'pay-001',
                userId: 'user-001',
                amount: Money::of(1000, 'USD'),
                vendorId: 'vendor-001',
                invoiceId: 'inv-001',
            ),
        };
    }
}
