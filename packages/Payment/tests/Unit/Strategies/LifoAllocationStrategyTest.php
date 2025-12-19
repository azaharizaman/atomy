<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Strategies\LifoAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LifoAllocationStrategy::class)]
final class LifoAllocationStrategyTest extends TestCase
{
    private LifoAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new LifoAllocationStrategy();
    }

    #[Test]
    public function it_returns_lifo_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::LIFO, $this->strategy->getMethod());
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
    public function it_allocates_to_newest_document_first(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-oldest', 100, documentDate: '2024-01-01'),
            MockAllocatableDocument::create('doc-newest', 100, documentDate: '2024-01-15'),
            MockAllocatableDocument::create('doc-middle', 100, documentDate: '2024-01-10'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // First allocation should be to newest document
        $this->assertSame('doc-newest', $allocationKeys[0]);
        $this->assertTrue($allocations['doc-newest']->equals(Money::of(100, 'USD')));
    }

    #[Test]
    public function it_fully_allocates_when_payment_covers_all(): void
    {
        $paymentAmount = Money::of(300, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100, documentDate: '2024-01-01'),
            MockAllocatableDocument::create('doc-2', 100, documentDate: '2024-01-05'),
            MockAllocatableDocument::create('doc-3', 100, documentDate: '2024-01-10'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $this->assertTrue($result->isFullyAllocated());
        $this->assertCount(3, $result->getAllocations());
    }

    #[Test]
    public function it_partially_allocates_when_payment_is_insufficient(): void
    {
        $paymentAmount = Money::of(150, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-old', 100, documentDate: '2024-01-01'),
            MockAllocatableDocument::create('doc-new', 100, documentDate: '2024-01-15'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        // Payment is fully allocated (all 150 was used), even though documents aren't fully paid
        $this->assertTrue($result->isFullyAllocated());
        $allocations = $result->getAllocations();

        // Newest document fully paid first, oldest partially
        $this->assertTrue($allocations['doc-new']->equals(Money::of(100, 'USD')));
        $this->assertTrue($allocations['doc-old']->equals(Money::of(50, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }
}
