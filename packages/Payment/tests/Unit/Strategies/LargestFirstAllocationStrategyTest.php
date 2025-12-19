<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Strategies\LargestFirstAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LargestFirstAllocationStrategy::class)]
final class LargestFirstAllocationStrategyTest extends TestCase
{
    private LargestFirstAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new LargestFirstAllocationStrategy();
    }

    #[Test]
    public function it_returns_largest_first_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::LARGEST_FIRST, $this->strategy->getMethod());
    }

    #[Test]
    public function it_does_not_require_user_input(): void
    {
        $this->assertFalse($this->strategy->requiresUserInput());
    }

    #[Test]
    public function it_returns_empty_result_for_no_documents(): void
    {
        $paymentAmount = Money::of(100, 'USD');

        $result = $this->strategy->allocate($paymentAmount, []);

        $this->assertFalse($result->isFullyAllocated());
        $this->assertCount(0, $result->getAllocations());
    }

    #[Test]
    public function it_allocates_to_largest_outstanding_first(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-small', 50),
            MockAllocatableDocument::create('doc-large', 200),
            MockAllocatableDocument::create('doc-medium', 100),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // First allocation should be to largest outstanding balance
        $this->assertSame('doc-large', $allocationKeys[0]);
        $this->assertTrue($allocations['doc-large']->equals(Money::of(100, 'USD')));
    }

    #[Test]
    public function it_fully_allocates_when_payment_covers_all(): void
    {
        $paymentAmount = Money::of(350, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 50),
            MockAllocatableDocument::create('doc-2', 100),
            MockAllocatableDocument::create('doc-3', 200),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $this->assertTrue($result->isFullyAllocated());
        $this->assertCount(3, $result->getAllocations());
    }

    #[Test]
    public function it_partially_allocates_when_payment_is_insufficient(): void
    {
        $paymentAmount = Money::of(250, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-large', 200),
            MockAllocatableDocument::create('doc-small', 100),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        // Payment is fully allocated (all 250 was used), even though documents aren't fully paid
        $this->assertTrue($result->isFullyAllocated());
        $allocations = $result->getAllocations();

        // Largest fully paid, small partially
        $this->assertTrue($allocations['doc-large']->equals(Money::of(200, 'USD')));
        $this->assertTrue($allocations['doc-small']->equals(Money::of(50, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }

    #[Test]
    public function it_handles_equal_amounts(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
            MockAllocatableDocument::create('doc-2', 100),
            MockAllocatableDocument::create('doc-3', 100),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // Should allocate to first document when amounts are equal
        $this->assertCount(1, $allocations);
        $this->assertArrayHasKey('doc-1', $allocations);
    }
}
