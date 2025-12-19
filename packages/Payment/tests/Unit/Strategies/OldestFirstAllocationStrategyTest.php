<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Strategies\OldestFirstAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OldestFirstAllocationStrategy::class)]
final class OldestFirstAllocationStrategyTest extends TestCase
{
    private OldestFirstAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new OldestFirstAllocationStrategy();
    }

    #[Test]
    public function it_returns_oldest_first_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::OLDEST_FIRST, $this->strategy->getMethod());
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
    public function it_allocates_to_oldest_due_date_first(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-later', 100, documentDate: '2024-01-01', dueDate: '2024-02-28'),
            MockAllocatableDocument::create('doc-earliest', 100, documentDate: '2024-01-15', dueDate: '2024-01-31'),
            MockAllocatableDocument::create('doc-middle', 100, documentDate: '2024-01-10', dueDate: '2024-02-15'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // First allocation should be to earliest due date
        $this->assertSame('doc-earliest', $allocationKeys[0]);
        $this->assertTrue($allocations['doc-earliest']->equals(Money::of(100, 'USD')));
    }

    #[Test]
    public function it_puts_documents_without_due_date_at_end(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-no-due', 100, documentDate: '2024-01-01', dueDate: null),
            MockAllocatableDocument::create('doc-with-due', 100, documentDate: '2024-01-15', dueDate: '2024-02-01'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // Document with due date should be first
        $this->assertSame('doc-with-due', $allocationKeys[0]);
    }

    #[Test]
    public function it_uses_document_date_when_no_due_dates(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-newer', 100, documentDate: '2024-01-15', dueDate: null),
            MockAllocatableDocument::create('doc-older', 100, documentDate: '2024-01-01', dueDate: null),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        $allocations = $result->getAllocations();
        $allocationKeys = array_keys($allocations);

        // Falls back to document date ordering
        $this->assertSame('doc-older', $allocationKeys[0]);
    }

    #[Test]
    public function it_fully_allocates_when_payment_covers_all(): void
    {
        $paymentAmount = Money::of(300, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100, dueDate: '2024-01-31'),
            MockAllocatableDocument::create('doc-2', 100, dueDate: '2024-02-15'),
            MockAllocatableDocument::create('doc-3', 100, dueDate: '2024-02-28'),
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
            MockAllocatableDocument::create('doc-early', 100, dueDate: '2024-01-31'),
            MockAllocatableDocument::create('doc-late', 100, dueDate: '2024-02-28'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents);

        // Payment is fully allocated (all 150 was used), even though documents aren't fully paid
        $this->assertTrue($result->isFullyAllocated());
        $allocations = $result->getAllocations();

        // Earliest due date fully paid, later partially
        $this->assertTrue($allocations['doc-early']->equals(Money::of(100, 'USD')));
        $this->assertTrue($allocations['doc-late']->equals(Money::of(50, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }
}
