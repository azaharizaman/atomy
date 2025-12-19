<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocationStrategyInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Exceptions\AllocationException;
use Nexus\Payment\Services\AllocationEngine;
use Nexus\Payment\Tests\Unit\Strategies\MockAllocatableDocument;
use Nexus\Payment\ValueObjects\AllocationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(AllocationEngine::class)]
final class AllocationEngineTest extends TestCase
{
    private AllocationEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new AllocationEngine(new NullLogger());
    }

    #[Test]
    public function it_initializes_with_default_strategies(): void
    {
        $strategies = $this->engine->getAvailableStrategies();

        self::assertContains(AllocationMethod::FIFO, $strategies);
        self::assertContains(AllocationMethod::LIFO, $strategies);
        self::assertContains(AllocationMethod::OLDEST_FIRST, $strategies);
        self::assertContains(AllocationMethod::LARGEST_FIRST, $strategies);
        self::assertContains(AllocationMethod::SMALLEST_FIRST, $strategies);
        self::assertContains(AllocationMethod::PROPORTIONAL, $strategies);
        self::assertContains(AllocationMethod::MANUAL, $strategies);
    }

    #[Test]
    public function it_allocates_using_fifo_strategy(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 50, 50, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 75, 75, '2024-01-15', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::FIFO);

        self::assertInstanceOf(AllocationResult::class, $result);
        self::assertCount(2, $result->getAllocations());
        self::assertTrue($result->getTotalAllocated()->equals(Money::of(100, 'MYR')));
        self::assertTrue($result->getUnallocatedAmount()->equals(Money::of(0, 'MYR')));
    }

    #[Test]
    public function it_allocates_using_lifo_strategy(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 50, 50, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 75, 75, '2024-01-15', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::LIFO);

        self::assertInstanceOf(AllocationResult::class, $result);
        self::assertTrue($result->getTotalAllocated()->equals(Money::of(100, 'MYR')));
    }

    #[Test]
    public function it_allocates_using_oldest_first_strategy(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 60, 60, '2024-01-01', '2024-02-01', 'MYR'),
            MockAllocatableDocument::create('doc2', 80, 80, '2024-01-15', '2024-01-25', 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::OLDEST_FIRST);

        self::assertInstanceOf(AllocationResult::class, $result);
        self::assertTrue($result->getTotalAllocated()->equals(Money::of(100, 'MYR')));
    }

    #[Test]
    public function it_allocates_using_largest_first_strategy(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 30, 30, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 80, 80, '2024-01-15', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::LARGEST_FIRST);

        self::assertInstanceOf(AllocationResult::class, $result);
        // Should allocate 80 to doc2 first, then 20 to doc1
        $allocations = $result->getAllocations();
        self::assertTrue($allocations['doc2']->equals(Money::of(80, 'MYR')));
    }

    #[Test]
    public function it_allocates_using_smallest_first_strategy(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 30, 30, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 80, 80, '2024-01-15', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::SMALLEST_FIRST);

        self::assertInstanceOf(AllocationResult::class, $result);
        // Should allocate 30 to doc1 first, then 70 to doc2
        $allocations = $result->getAllocations();
        self::assertTrue($allocations['doc1']->equals(Money::of(30, 'MYR')));
    }

    #[Test]
    public function it_allocates_using_proportional_strategy(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 100, 100, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 100, 100, '2024-01-15', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::PROPORTIONAL);

        self::assertInstanceOf(AllocationResult::class, $result);
        self::assertCount(2, $result->getAllocations());
        // Each should get 50
        foreach ($result->getAllocations() as $documentId => $allocatedAmount) {
            self::assertTrue($allocatedAmount->equals(Money::of(50, 'MYR')));
        }
    }

    #[Test]
    public function it_allocates_using_manual_strategy_with_allocations(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 100, 100, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 100, 100, '2024-01-15', null, 'MYR'),
        ];
        $manualAllocations = [
            'doc1' => Money::of(60, 'MYR'),
            'doc2' => Money::of(40, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::MANUAL, $manualAllocations);

        self::assertInstanceOf(AllocationResult::class, $result);
        self::assertTrue($result->getTotalAllocated()->equals(Money::of(100, 'MYR')));
    }

    #[Test]
    public function it_throws_exception_for_manual_without_allocations(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 100, 100, '2024-01-01', null, 'MYR'),
        ];

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('Manual allocation method requires explicit allocation specifications');

        $this->engine->allocate($payment, $documents, AllocationMethod::MANUAL);
    }

    #[Test]
    public function it_throws_for_empty_documents(): void
    {
        $payment = Money::of(100, 'MYR');

        $this->expectException(AllocationException::class);
        $this->expectExceptionMessage('No documents provided for allocation');

        $this->engine->allocate($payment, [], AllocationMethod::FIFO);
    }

    #[Test]
    public function it_validates_allocation_errors(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 50, 50, '2024-01-01', null, 'USD'),
            MockAllocatableDocument::create('doc2', 50, 50, '2024-01-01', null, 'MYR'),
        ];

        $errors = $this->engine->validateAllocation($payment, $documents, AllocationMethod::FIFO);

        self::assertNotEmpty($errors);
        self::assertContains('All documents must have the same currency', $errors);
    }

    #[Test]
    public function it_validates_no_eligible_documents(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 50, 0, '2024-01-01', null, 'MYR'), // Fully paid
        ];

        $errors = $this->engine->validateAllocation($payment, $documents, AllocationMethod::FIFO);

        self::assertContains('All documents are already fully paid', $errors);
    }

    #[Test]
    public function it_validates_successful_allocation(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 200, 200, '2024-01-01', null, 'MYR'),
        ];

        $errors = $this->engine->validateAllocation($payment, $documents, AllocationMethod::FIFO);

        self::assertEmpty($errors);
    }

    #[Test]
    public function it_previews_allocation_without_side_effects(): void
    {
        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 50, 50, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 75, 75, '2024-01-15', null, 'MYR'),
        ];

        $preview = $this->engine->preview($payment, $documents, AllocationMethod::FIFO);

        self::assertInstanceOf(AllocationResult::class, $preview);
        self::assertCount(2, $preview->getAllocations());
    }

    #[Test]
    public function it_registers_custom_strategy(): void
    {
        $customStrategy = new class implements AllocationStrategyInterface {
            public function getMethod(): AllocationMethod
            {
                return AllocationMethod::FIFO; // Override FIFO for test
            }

            public function allocate(Money $paymentAmount, array $documents, ?array $manualAllocations = null): AllocationResult
            {
                return new AllocationResult(
                    totalAmount: $paymentAmount,
                    allocatedAmount: Money::zero($paymentAmount->getCurrency()),
                    unallocatedAmount: $paymentAmount,
                    allocations: [],
                    isFullyAllocated: false,
                );
            }

            public function requiresUserInput(): bool
            {
                return false;
            }

            public function validate(array $documents): array
            {
                return [];
            }
        };

        $this->engine->registerStrategy($customStrategy);

        $payment = Money::of(100, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 50, 50, '2024-01-01', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::FIFO);

        // Custom strategy returns empty allocations
        self::assertCount(0, $result->getAllocations());
        self::assertTrue($result->getUnallocatedAmount()->equals(Money::of(100, 'MYR')));
    }

    #[Test]
    public function it_checks_if_strategy_requires_user_input(): void
    {
        self::assertTrue($this->engine->requiresUserInput(AllocationMethod::MANUAL));
        self::assertFalse($this->engine->requiresUserInput(AllocationMethod::FIFO));
        self::assertFalse($this->engine->requiresUserInput(AllocationMethod::LIFO));
        self::assertFalse($this->engine->requiresUserInput(AllocationMethod::PROPORTIONAL));
    }

    #[Test]
    public function it_handles_payment_larger_than_total_outstanding(): void
    {
        $payment = Money::of(500, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 100, 100, '2024-01-01', null, 'MYR'),
            MockAllocatableDocument::create('doc2', 150, 150, '2024-01-15', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::FIFO);

        self::assertTrue($result->getTotalAllocated()->equals(Money::of(250, 'MYR')));
        self::assertTrue($result->getUnallocatedAmount()->equals(Money::of(250, 'MYR')));
    }

    #[Test]
    public function it_handles_partial_payment(): void
    {
        $payment = Money::of(30, 'MYR');
        $documents = [
            MockAllocatableDocument::create('doc1', 100, 100, '2024-01-01', null, 'MYR'),
        ];

        $result = $this->engine->allocate($payment, $documents, AllocationMethod::FIFO);

        self::assertTrue($result->getTotalAllocated()->equals(Money::of(30, 'MYR')));
        self::assertTrue($result->getUnallocatedAmount()->equals(Money::of(0, 'MYR')));
    }
}
