<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\ValueObjects\AllocationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllocationResult::class)]
final class AllocationResultTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_all_fields(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocatedAmount = Money::of(800, 'USD');
        $unallocatedAmount = Money::of(200, 'USD');
        $allocations = [
            'doc-1' => Money::of(500, 'USD'),
            'doc-2' => Money::of(300, 'USD'),
        ];

        $result = new AllocationResult(
            $totalAmount,
            $allocatedAmount,
            $unallocatedAmount,
            $allocations,
            false,
        );

        $this->assertTrue($result->totalAmount->equals($totalAmount));
        $this->assertTrue($result->allocatedAmount->equals($allocatedAmount));
        $this->assertTrue($result->unallocatedAmount->equals($unallocatedAmount));
        $this->assertCount(2, $result->allocations);
        $this->assertFalse($result->isFullyAllocated);
        $this->assertFalse($result->hasOverpayment);
    }

    #[Test]
    public function it_can_create_empty_result(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $result = AllocationResult::empty($totalAmount);

        $this->assertTrue($result->totalAmount->equals($totalAmount));
        $this->assertTrue($result->allocatedAmount->isZero());
        $this->assertTrue($result->unallocatedAmount->equals($totalAmount));
        $this->assertEmpty($result->allocations);
        $this->assertFalse($result->isFullyAllocated);
    }

    #[Test]
    public function it_can_create_fully_allocated_result(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocations = [
            'doc-1' => Money::of(600, 'USD'),
            'doc-2' => Money::of(400, 'USD'),
        ];

        $result = AllocationResult::fullyAllocated($totalAmount, $allocations);

        $this->assertTrue($result->totalAmount->equals($totalAmount));
        $this->assertTrue($result->allocatedAmount->equals($totalAmount));
        $this->assertTrue($result->unallocatedAmount->isZero());
        $this->assertTrue($result->isFullyAllocated);
    }

    #[Test]
    public function it_can_create_partial_allocation_result(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocatedAmount = Money::of(700, 'USD');
        $allocations = [
            'doc-1' => Money::of(700, 'USD'),
        ];

        $result = AllocationResult::partial($totalAmount, $allocatedAmount, $allocations);

        $this->assertTrue($result->totalAmount->equals($totalAmount));
        $this->assertTrue($result->allocatedAmount->equals($allocatedAmount));
        $this->assertTrue($result->unallocatedAmount->equals(Money::of(300, 'USD')));
        $this->assertFalse($result->isFullyAllocated);
    }

    #[Test]
    public function it_returns_allocation_count(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocations = [
            'doc-1' => Money::of(400, 'USD'),
            'doc-2' => Money::of(300, 'USD'),
            'doc-3' => Money::of(300, 'USD'),
        ];

        $result = AllocationResult::fullyAllocated($totalAmount, $allocations);

        $this->assertSame(3, $result->getAllocationCount());
    }

    #[Test]
    public function it_gets_allocation_for_document(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocations = [
            'doc-1' => Money::of(600, 'USD'),
            'doc-2' => Money::of(400, 'USD'),
        ];

        $result = AllocationResult::fullyAllocated($totalAmount, $allocations);

        $this->assertTrue($result->getAllocationFor('doc-1')->equals(Money::of(600, 'USD')));
        $this->assertTrue($result->getAllocationFor('doc-2')->equals(Money::of(400, 'USD')));
        $this->assertNull($result->getAllocationFor('doc-3'));
    }

    #[Test]
    public function it_checks_if_has_allocation_for_document(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocations = [
            'doc-1' => Money::of(1000, 'USD'),
        ];

        $result = AllocationResult::fullyAllocated($totalAmount, $allocations);

        $this->assertTrue($result->hasAllocationFor('doc-1'));
        $this->assertFalse($result->hasAllocationFor('doc-2'));
    }

    #[Test]
    public function it_calculates_allocation_percentage(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocatedAmount = Money::of(750, 'USD');
        $allocations = ['doc-1' => Money::of(750, 'USD')];

        $result = AllocationResult::partial($totalAmount, $allocatedAmount, $allocations);

        $this->assertSame(75.0, $result->getAllocationPercentage());
    }

    #[Test]
    public function it_returns_zero_percentage_for_zero_total(): void
    {
        $totalAmount = Money::zero('USD');
        $result = AllocationResult::empty($totalAmount);

        $this->assertSame(0.0, $result->getAllocationPercentage());
    }

    #[Test]
    public function it_returns_document_ids(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocations = [
            'doc-1' => Money::of(400, 'USD'),
            'doc-2' => Money::of(300, 'USD'),
            'doc-3' => Money::of(300, 'USD'),
        ];

        $result = AllocationResult::fullyAllocated($totalAmount, $allocations);

        $this->assertSame(['doc-1', 'doc-2', 'doc-3'], $result->getDocumentIds());
    }

    #[Test]
    public function it_can_be_marked_as_overpayment(): void
    {
        $totalAmount = Money::of(1000, 'USD');
        $allocatedAmount = Money::of(800, 'USD');
        $unallocatedAmount = Money::of(200, 'USD');
        $allocations = ['doc-1' => Money::of(800, 'USD')];

        $result = new AllocationResult(
            $totalAmount,
            $allocatedAmount,
            $unallocatedAmount,
            $allocations,
            false,
            true, // hasOverpayment
        );

        $this->assertTrue($result->hasOverpayment);
    }
}
