<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Strategies\ProportionalAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProportionalAllocationStrategy::class)]
final class ProportionalAllocationStrategyTest extends TestCase
{
    private ProportionalAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ProportionalAllocationStrategy();
    }

    #[Test]
    public function it_returns_proportional_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::PROPORTIONAL, $this->strategy->getMethod());
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
    public function it_allocates_proportionally_based_on_outstanding(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100), // 25%
            MockAllocatableDocument::create('doc-2', 300), // 75%
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // 25% of 100 = 25
        $this->assertTrue($allocations['doc-1']->equals(Money::of(25, 'USD')));
        // 75% of 100 = 75
        $this->assertTrue($allocations['doc-2']->equals(Money::of(75, 'USD')));
    }

    #[Test]
    public function it_allocates_equally_for_equal_amounts(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
            MockAllocatableDocument::create('doc-2', 100),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // Each should get 50
        $this->assertTrue($allocations['doc-1']->equals(Money::of(50, 'USD')));
        $this->assertTrue($allocations['doc-2']->equals(Money::of(50, 'USD')));
    }

    #[Test]
    public function it_fully_allocates_when_payment_covers_all(): void
    {
        $paymentAmount = Money::of(400, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
            MockAllocatableDocument::create('doc-2', 300),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $this->assertTrue($result->isFullyAllocated());
        $this->assertCount(2, $result->getAllocations());

        $allocations = $result->getAllocations();
        $this->assertTrue($allocations['doc-1']->equals(Money::of(100, 'USD')));
        $this->assertTrue($allocations['doc-2']->equals(Money::of(300, 'USD')));
    }

    #[Test]
    public function it_handles_rounding_correctly(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100), // 33.33%
            MockAllocatableDocument::create('doc-2', 100), // 33.33%
            MockAllocatableDocument::create('doc-3', 100), // 33.33%
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // Total should equal payment amount exactly
        $totalAllocated = Money::of(0, 'USD');
        foreach ($allocations as $amount) {
            $totalAllocated = $totalAllocated->add($amount);
        }

        $this->assertTrue($totalAllocated->equals($paymentAmount));
    }

    #[Test]
    public function it_does_not_exceed_outstanding_balance(): void
    {
        $paymentAmount = Money::of(1000, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 50),
            MockAllocatableDocument::create('doc-2', 150),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // Should not exceed each document's outstanding
        $this->assertTrue($allocations['doc-1']->equals(Money::of(50, 'USD')));
        $this->assertTrue($allocations['doc-2']->equals(Money::of(150, 'USD')));

        // Should have unallocated amount
        $this->assertTrue($result->getUnallocatedAmount()->equals(Money::of(800, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }

    #[Test]
    public function it_handles_mixed_partial_payments(): void
    {
        $paymentAmount = Money::of(50, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 200, outstanding: 100),
            MockAllocatableDocument::create('doc-2', 200, outstanding: 100),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();

        // Each should get 25 (50% each of 50)
        $this->assertTrue($allocations['doc-1']->equals(Money::of(25, 'USD')));
        $this->assertTrue($allocations['doc-2']->equals(Money::of(25, 'USD')));
    }
}
