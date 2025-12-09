<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Approval;

use Nexus\ProcurementOperations\Contracts\DelegationServiceInterface;
use Nexus\ProcurementOperations\Services\Approval\DelegationService;
use Nexus\Workflow\Contracts\DelegationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DelegationService.
 */
final class DelegationServiceTest extends TestCase
{
    private DelegationRepositoryInterface&MockObject $delegationRepository;
    private DelegationService $service;

    protected function setUp(): void
    {
        $this->delegationRepository = $this->createMock(DelegationRepositoryInterface::class);
        $this->service = new DelegationService($this->delegationRepository);
    }

    /**
     * Test returns original approver when no delegation exists.
     */
    public function test_returns_original_approver_when_no_delegation(): void
    {
        $this->delegationRepository
            ->method('findActiveForUser')
            ->willReturn(null);

        $result = $this->service->resolveApprover(
            approverId: 'approver-1',
            taskType: 'REQUISITION_APPROVAL',
            effectiveDate: new \DateTimeImmutable()
        );

        $this->assertSame('approver-1', $result);
    }

    /**
     * Test returns delegate when active delegation exists.
     */
    public function test_returns_delegate_when_delegation_exists(): void
    {
        $this->delegationRepository
            ->method('findActiveForUser')
            ->with('approver-1', 'REQUISITION_APPROVAL', $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn([
                'delegatee_id' => 'delegate-1',
                'delegation_id' => 'del-123',
            ]);

        // No further delegation for the delegate
        $this->delegationRepository
            ->method('findActiveForUser')
            ->with('delegate-1', $this->anything(), $this->anything())
            ->willReturn(null);

        $result = $this->service->resolveApprover(
            approverId: 'approver-1',
            taskType: 'REQUISITION_APPROVAL',
            effectiveDate: new \DateTimeImmutable()
        );

        $this->assertSame('delegate-1', $result);
    }

    /**
     * Test checks if user has active delegation.
     */
    public function test_has_active_delegation_returns_true_when_exists(): void
    {
        $this->delegationRepository
            ->method('findActiveForUser')
            ->willReturn([
                'delegatee_id' => 'delegate-1',
                'delegation_id' => 'del-123',
            ]);

        $result = $this->service->hasActiveDelegation(
            userId: 'approver-1',
            taskType: 'REQUISITION_APPROVAL',
            effectiveDate: new \DateTimeImmutable()
        );

        $this->assertTrue($result);
    }

    /**
     * Test has active delegation returns false when none exists.
     */
    public function test_has_active_delegation_returns_false_when_none(): void
    {
        $this->delegationRepository
            ->method('findActiveForUser')
            ->willReturn(null);

        $result = $this->service->hasActiveDelegation(
            userId: 'approver-1',
            taskType: 'REQUISITION_APPROVAL',
            effectiveDate: new \DateTimeImmutable()
        );

        $this->assertFalse($result);
    }

    /**
     * Test get delegation chain returns empty array when no delegations.
     */
    public function test_get_delegation_chain_returns_empty_when_no_delegations(): void
    {
        $this->delegationRepository
            ->method('getDelegationChain')
            ->willReturn([]);

        $result = $this->service->getDelegationChain('approver-1');

        $this->assertSame([], $result);
    }

    /**
     * Test get delegation chain returns chain of delegations.
     */
    public function test_get_delegation_chain_returns_chain(): void
    {
        $chain = [
            ['from' => 'approver-1', 'to' => 'delegate-1'],
            ['from' => 'delegate-1', 'to' => 'delegate-2'],
        ];

        $this->delegationRepository
            ->method('getDelegationChain')
            ->willReturn($chain);

        $result = $this->service->getDelegationChain('approver-1');

        $this->assertCount(2, $result);
        $this->assertSame('delegate-2', $result[1]['to']);
    }

    /**
     * Test delegation chain respects maximum depth.
     */
    public function test_delegation_chain_respects_max_depth(): void
    {
        // Create a deep chain that exceeds default max depth of 3
        $deepChain = [
            ['from' => 'a', 'to' => 'b'],
            ['from' => 'b', 'to' => 'c'],
            ['from' => 'c', 'to' => 'd'],
            ['from' => 'd', 'to' => 'e'], // Beyond max depth
        ];

        $this->delegationRepository
            ->method('getDelegationChain')
            ->willReturn($deepChain);

        $result = $this->service->getDelegationChain('a', maxDepth: 3);

        // Should only return first 3 delegations
        $this->assertCount(3, $result);
    }
}
