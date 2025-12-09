<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Approval;

use Nexus\ProcurementOperations\Contracts\DelegationServiceInterface;
use Nexus\ProcurementOperations\DTOs\ApprovalRoutingRequest;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\ProcurementOperations\Services\Approval\ApprovalRoutingService;
use Nexus\Setting\Contracts\SettingsManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApprovalRoutingService.
 */
final class ApprovalRoutingServiceTest extends TestCase
{
    private SettingsManagerInterface&MockObject $settingsManager;
    private DelegationServiceInterface&MockObject $delegationService;
    private ApprovalRoutingService $service;

    protected function setUp(): void
    {
        $this->settingsManager = $this->createMock(SettingsManagerInterface::class);
        $this->delegationService = $this->createMock(DelegationServiceInterface::class);
        
        $this->service = new ApprovalRoutingService(
            $this->settingsManager,
            $this->delegationService
        );
    }

    /**
     * Test determines Level 1 for small amounts.
     */
    public function test_determines_level_1_for_small_amount(): void
    {
        $this->setupDefaultThresholds();
        $this->setupNoActiveDelegations();

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 300000, // $3,000 - under Level 1 threshold
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        $this->assertTrue($result->success);
        $this->assertSame(ApprovalLevel::LEVEL_1, $result->requiredLevel);
    }

    /**
     * Test determines Level 2 for medium amounts.
     */
    public function test_determines_level_2_for_medium_amount(): void
    {
        $this->setupDefaultThresholds();
        $this->setupNoActiveDelegations();

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 1500000, // $15,000 - between Level 1 and Level 2
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        $this->assertTrue($result->success);
        $this->assertSame(ApprovalLevel::LEVEL_2, $result->requiredLevel);
    }

    /**
     * Test determines Level 3 for large amounts.
     */
    public function test_determines_level_3_for_large_amount(): void
    {
        $this->setupDefaultThresholds();
        $this->setupNoActiveDelegations();

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 5000000, // $50,000 - between Level 2 and Level 3
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        $this->assertTrue($result->success);
        $this->assertSame(ApprovalLevel::LEVEL_3, $result->requiredLevel);
    }

    /**
     * Test uses custom thresholds from settings.
     */
    public function test_uses_custom_thresholds_from_settings(): void
    {
        // Custom: Level 1 = $1,000
        $this->settingsManager
            ->method('get')
            ->willReturnCallback(function (string $key, mixed $default) {
                return match ($key) {
                    'procurement.approval.threshold_level_1_cents' => 100000, // $1,000
                    'procurement.approval.threshold_level_2_cents' => 500000, // $5,000
                    default => $default,
                };
            });
        
        $this->setupNoActiveDelegations();

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 200000, // $2,000 - over custom Level 1 threshold
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        $this->assertSame(ApprovalLevel::LEVEL_2, $result->requiredLevel);
    }

    /**
     * Test builds approval chain with proper structure.
     */
    public function test_builds_approval_chain_with_structure(): void
    {
        $this->setupDefaultThresholds();
        $this->setupNoActiveDelegations();

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 300000,
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        $this->assertNotEmpty($result->approvalChain);
        $this->assertArrayHasKey('level', $result->approvalChain[0]);
        $this->assertArrayHasKey('required', $result->approvalChain[0]);
    }

    /**
     * Test returns escalation timeout from settings.
     */
    public function test_returns_escalation_timeout_from_settings(): void
    {
        $this->settingsManager
            ->method('get')
            ->willReturnCallback(function (string $key, mixed $default) {
                if ($key === 'procurement.approval.escalation_timeout_hours') {
                    return 72; // Custom timeout
                }
                return $default;
            });
        
        $this->setupNoActiveDelegations();

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 300000,
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        $this->assertSame(72, $result->escalationTimeoutHours);
    }

    /**
     * Test resolves delegation in approval chain.
     */
    public function test_resolves_delegation_in_approval_chain(): void
    {
        $this->setupDefaultThresholds();
        
        // Manager has delegation
        $this->delegationService
            ->method('hasActiveDelegation')
            ->willReturn(true);
        
        $this->delegationService
            ->method('resolveApprover')
            ->willReturn('delegate-manager');

        $request = new ApprovalRoutingRequest(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 300000,
            currency: 'MYR',
            requesterId: 'user-1',
            departmentId: 'dept-1'
        );

        $result = $this->service->determineRouting($request);

        // Approval chain should include delegation info
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->approvalChain);
    }

    /**
     * Setup default approval thresholds.
     */
    private function setupDefaultThresholds(): void
    {
        $this->settingsManager
            ->method('get')
            ->willReturnCallback(function (string $key, mixed $default) {
                return match ($key) {
                    'procurement.approval.threshold_level_1_cents' => 500000,   // $5,000
                    'procurement.approval.threshold_level_2_cents' => 2500000,  // $25,000
                    'procurement.approval.threshold_level_3_cents' => 10000000, // $100,000
                    'procurement.approval.escalation_timeout_hours' => 48,
                    default => $default,
                };
            });
    }

    /**
     * Setup no active delegations.
     */
    private function setupNoActiveDelegations(): void
    {
        $this->delegationService
            ->method('hasActiveDelegation')
            ->willReturn(false);
        
        $this->delegationService
            ->method('resolveApprover')
            ->willReturnArgument(0); // Return original approver ID
    }
}
