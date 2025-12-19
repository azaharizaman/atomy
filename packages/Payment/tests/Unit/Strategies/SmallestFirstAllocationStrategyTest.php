<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Strategies\SmallestFirstAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SmallestFirstAllocationStrategy::class)]
final class SmallestFirstAllocationStrategyTest extends TestCase
{
    private SmallestFirstAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new SmallestFirstAllocationStrategy();
    }

    #[Test]
    public function it_returns_smallest_first_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::SMALLEST_FIRST, $this->strategy->getMethod());
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
    public function it_allocates_to_smallest_outstanding_first(): void
    {
        $paymentAmount = Money::of(50, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-large', 200),
            MockAllocatableDocument::create('doc-small', 50),
            MockAllocatableDocument::create('doc-medium', 100),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // First allocation should be to smallest outstanding balance
        $this->assertSame('doc-small', $allocationKeys[0]);
        $this->assertTrue($allocations['doc-small']->equals(Money::of(50, 'USD')));
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
    public function it_clears_multiple_small_invoices_efficiently(): void
    {
        $paymentAmount = Money::of(150, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-large', 200),
            MockAllocatableDocument::create('doc-small-1', 50),
            MockAllocatableDocument::create('doc-small-2', 50),
            MockAllocatableDocument::create('doc-small-3', 50),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // Should clear all three small invoices
        $this->assertTrue($allocations['doc-small-1']->equals(Money::of(50, 'USD')));
        $this->assertTrue($allocations['doc-small-2']->equals(Money::of(50, 'USD')));
        $this->assertTrue($allocations['doc-small-3']->equals(Money::of(50, 'USD')));

        // Large invoice not touched
        $this->assertArrayNotHasKey('doc-large', $allocations);
    }

    #[Test]
    public function it_partially_allocates_when_payment_is_insufficient(): void
    {
        $paymentAmount = Money::of(75, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-large', 100),
            MockAllocatableDocument::create('doc-small', 50),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        // Payment is fully allocated (all 75 was used), even though documents aren't fully paid
        $this->assertTrue($result->isFullyAllocated());
        $allocations = $result->getAllocations();

        // Smallest fully paid, larger partially
        $this->assertTrue($allocations['doc-small']->equals(Money::of(50, 'USD')));
        $this->assertTrue($allocations['doc-large']->equals(Money::of(25, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }
}
