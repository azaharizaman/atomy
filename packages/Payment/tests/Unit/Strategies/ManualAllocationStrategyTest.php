<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Exceptions\AllocationException;
use Nexus\Payment\Strategies\ManualAllocationStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ManualAllocationStrategy::class)]
final class ManualAllocationStrategyTest extends TestCase
{
    private ManualAllocationStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ManualAllocationStrategy();
    }

    #[Test]
    public function it_returns_manual_allocation_method(): void
    {
        $this->assertSame(AllocationMethod::MANUAL, $this->strategy->getMethod());
    }

    #[Test]
    public function it_requires_user_input(): void
    {
        $this->assertTrue($this->strategy->requiresUserInput());
    }

    #[Test]
    public function it_throws_exception_when_manual_allocations_not_provided(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('Manual allocation method requires explicit allocation specifications');

        $this->strategy->allocate($paymentAmount, $documents);
    }

    #[Test]
    public function it_throws_exception_for_empty_manual_allocations(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('Manual allocation method requires explicit allocation specifications');

        $this->strategy->allocate($paymentAmount, $documents, []);
    }

    #[Test]
    public function it_applies_manual_allocations(): void
    {
        $paymentAmount = Money::of(150, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
            MockAllocatableDocument::create('doc-2', 100),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(75, 'USD'),
            'doc-2' => Money::of(75, 'USD'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);

        $allocations = $result->getAllocations();

        $this->assertTrue($allocations['doc-1']->equals(Money::of(75, 'USD')));
        $this->assertTrue($allocations['doc-2']->equals(Money::of(75, 'USD')));
        $this->assertTrue($result->isFullyAllocated());
    }

    #[Test]
    public function it_throws_exception_for_document_not_in_list(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $manualAllocations = [
            'doc-unknown' => Money::of(50, 'USD'),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage("Document with ID 'doc-unknown' not found in provided documents");

        $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);
    }

    #[Test]
    public function it_throws_exception_for_currency_mismatch(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100, currency: 'USD'),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(50, 'EUR'),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('Currency mismatch');

        $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);
    }

    #[Test]
    public function it_throws_exception_when_allocation_exceeds_outstanding(): void
    {
        $paymentAmount = Money::of(200, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(150, 'USD'),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('exceeds outstanding balance');

        $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);
    }

    #[Test]
    public function it_throws_exception_for_negative_allocation(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(-50, 'USD'),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('must be positive');

        $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);
    }

    #[Test]
    public function it_throws_exception_for_zero_allocation(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(0, 'USD'),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('must be positive');

        $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);
    }

    #[Test]
    public function it_fully_allocates_payment(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 50),
            MockAllocatableDocument::create('doc-2', 50),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(50, 'USD'),
            'doc-2' => Money::of(50, 'USD'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);

        $this->assertTrue($result->isFullyAllocated());
        $this->assertTrue($result->getUnallocatedAmount()->isZero());
    }

    #[Test]
    public function it_tracks_unallocated_amount(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-1', 100),
        ];

        $manualAllocations = [
            'doc-1' => Money::of(30, 'USD'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);

        $this->assertFalse($result->isFullyAllocated());
        $this->assertTrue($result->getUnallocatedAmount()->equals(Money::of(70, 'USD')));
    }

    #[Test]
    public function it_validates_documents(): void
    {
        $errors = $this->strategy->validate([]);

        $this->assertContains('No documents provided for allocation', $errors);
    }

    #[Test]
    public function it_ignores_fully_paid_documents_in_allocations(): void
    {
        $paymentAmount = Money::of(100, 'USD');
        $documents = [
            MockAllocatableDocument::create('doc-paid', 100, outstanding: 0),
            MockAllocatableDocument::create('doc-unpaid', 100),
        ];

        $manualAllocations = [
            'doc-unpaid' => Money::of(50, 'USD'),
        ];

        $result = $this->strategy->allocate($paymentAmount, $documents, $manualAllocations);

        $allocations = $result->getAllocations();

        $this->assertCount(1, $allocations);
        $this->assertArrayHasKey('doc-unpaid', $allocations);
    }
}
