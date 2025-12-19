<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Strategies\FifoAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FifoAllocationStrategy::class)]
final class FifoAllocationStrategyTest extends TestCase
{
    private FifoAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new FifoAllocationStrategy();
    }

    #[Test]
    public function it_returns_fifo_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::FIFO, $this->strategy->getMethod());
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
    public function it_returns_empty_result_when_all_documents_fully_paid(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100, outstanding: 0),
            MockAllocatableDocument::create('doc-2', 200, outstanding: 0),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $this->assertFalse($result->isFullyAllocated());
        $this->assertCount(0, $result->getAllocations());
    }

    #[Test]
    public function it_allocates_to_oldest_document_first(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-newer', 100, documentDate: '2024-01-15'),
            MockAllocatableDocument::create('doc-oldest', 100, documentDate: '2024-01-01'),
            MockAllocatableDocument::create('doc-middle', 100, documentDate: '2024-01-10'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // First allocation should be to oldest document
        $this->assertSame('doc-oldest', $allocationKeys[0]);
        $this->assertTrue($allocations['doc-oldest']->equals(Money::of(100, 'USD')));
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
            MockAllocatableDocument::create('doc-1', 100, documentDate: '2024-01-01'),
            MockAllocatableDocument::create('doc-2', 100, documentDate: '2024-01-05'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        // Payment is fully allocated (all 150 was used), even though documents aren't fully paid
        $this->assertTrue($result->isFullyAllocated());
        $allocations = $result->getAllocations();

        // First document fully paid, second partially paid
        $this->assertTrue($allocations['doc-1']->equals(Money::of(100, 'USD')));
        $this->assertTrue($allocations['doc-2']->equals(Money::of(50, 'USD')));
    }

    #[Test]
    public function it_handles_payment_exceeding_total_outstanding(): void
    {
        $paymentAmount = Money::of(500, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100, documentDate: '2024-01-01'),
            MockAllocatableDocument::create('doc-2', 100, documentDate: '2024-01-05'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        // Should have unallocated amount
        $this->assertTrue($result->getUnallocatedAmount()->equals(Money::of(300, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }

    #[Test]
    public function it_validates_currency_mismatch(): void
    {
        $documents = [
            MockAllocatableDocument::create('doc-1', 100, currency: 'USD'),
            MockAllocatableDocument::create('doc-2', 100, currency: 'EUR'),
        ];

        $errors = $this->strategy->validate($documents);

        $this->assertContains('All documents must have the same currency', $errors);
    }
}
