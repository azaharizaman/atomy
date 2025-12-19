<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Enums;

use Nexus\Payment\Enums\AllocationMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllocationMethod::class)]
final class AllocationMethodTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = AllocationMethod::cases();

        $this->assertCount(7, $cases);
        $this->assertContains(AllocationMethod::FIFO, $cases);
        $this->assertContains(AllocationMethod::LIFO, $cases);
        $this->assertContains(AllocationMethod::PROPORTIONAL, $cases);
        $this->assertContains(AllocationMethod::MANUAL, $cases);
        $this->assertContains(AllocationMethod::OLDEST_FIRST, $cases);
        $this->assertContains(AllocationMethod::SMALLEST_FIRST, $cases);
        $this->assertContains(AllocationMethod::LARGEST_FIRST, $cases);
    }

    #[Test]
    #[DataProvider('methodLabelsProvider')]
    public function it_returns_correct_labels(AllocationMethod $method, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $method->label());
    }

    public static function methodLabelsProvider(): iterable
    {
        yield 'FIFO' => [AllocationMethod::FIFO, 'First-In-First-Out'];
        yield 'LIFO' => [AllocationMethod::LIFO, 'Last-In-First-Out'];
        yield 'PROPORTIONAL' => [AllocationMethod::PROPORTIONAL, 'Proportional'];
        yield 'MANUAL' => [AllocationMethod::MANUAL, 'Manual Allocation'];
        yield 'OLDEST_FIRST' => [AllocationMethod::OLDEST_FIRST, 'Oldest First'];
        yield 'SMALLEST_FIRST' => [AllocationMethod::SMALLEST_FIRST, 'Smallest First'];
        yield 'LARGEST_FIRST' => [AllocationMethod::LARGEST_FIRST, 'Largest First'];
    }

    #[Test]
    #[DataProvider('methodDescriptionsProvider')]
    public function it_returns_correct_descriptions(AllocationMethod $method, string $expectedDescription): void
    {
        $this->assertSame($expectedDescription, $method->description());
    }

    public static function methodDescriptionsProvider(): iterable
    {
        yield 'FIFO' => [AllocationMethod::FIFO, 'Allocate payment to oldest invoices/bills first based on document date'];
        yield 'LIFO' => [AllocationMethod::LIFO, 'Allocate payment to newest invoices/bills first based on document date'];
        yield 'PROPORTIONAL' => [AllocationMethod::PROPORTIONAL, 'Distribute payment proportionally across all outstanding documents'];
        yield 'MANUAL' => [AllocationMethod::MANUAL, 'Allow user to specify exact allocation amounts per document'];
        yield 'OLDEST_FIRST' => [AllocationMethod::OLDEST_FIRST, 'Allocate payment to documents with oldest due dates first'];
        yield 'SMALLEST_FIRST' => [AllocationMethod::SMALLEST_FIRST, 'Allocate payment to documents with smallest balances first'];
        yield 'LARGEST_FIRST' => [AllocationMethod::LARGEST_FIRST, 'Allocate payment to documents with largest balances first'];
    }

    #[Test]
    #[DataProvider('automaticMethodsProvider')]
    public function it_correctly_identifies_automatic_methods(AllocationMethod $method, bool $isAutomatic): void
    {
        $this->assertSame($isAutomatic, $method->isAutomatic());
    }

    public static function automaticMethodsProvider(): iterable
    {
        yield 'FIFO is automatic' => [AllocationMethod::FIFO, true];
        yield 'LIFO is automatic' => [AllocationMethod::LIFO, true];
        yield 'PROPORTIONAL is automatic' => [AllocationMethod::PROPORTIONAL, true];
        yield 'MANUAL is not automatic' => [AllocationMethod::MANUAL, false];
        yield 'OLDEST_FIRST is automatic' => [AllocationMethod::OLDEST_FIRST, true];
        yield 'SMALLEST_FIRST is automatic' => [AllocationMethod::SMALLEST_FIRST, true];
        yield 'LARGEST_FIRST is automatic' => [AllocationMethod::LARGEST_FIRST, true];
    }

    #[Test]
    #[DataProvider('userInputRequirementProvider')]
    public function it_correctly_identifies_user_input_requirement(AllocationMethod $method, bool $requiresUserInput): void
    {
        $this->assertSame($requiresUserInput, $method->requiresUserInput());
    }

    public static function userInputRequirementProvider(): iterable
    {
        yield 'FIFO does not require user input' => [AllocationMethod::FIFO, false];
        yield 'LIFO does not require user input' => [AllocationMethod::LIFO, false];
        yield 'PROPORTIONAL does not require user input' => [AllocationMethod::PROPORTIONAL, false];
        yield 'MANUAL requires user input' => [AllocationMethod::MANUAL, true];
        yield 'OLDEST_FIRST does not require user input' => [AllocationMethod::OLDEST_FIRST, false];
        yield 'SMALLEST_FIRST does not require user input' => [AllocationMethod::SMALLEST_FIRST, false];
        yield 'LARGEST_FIRST does not require user input' => [AllocationMethod::LARGEST_FIRST, false];
    }

    #[Test]
    public function it_returns_correct_automatic_methods(): void
    {
        $automaticMethods = AllocationMethod::automaticMethods();

        $this->assertCount(6, $automaticMethods);
        $this->assertContains(AllocationMethod::FIFO, $automaticMethods);
        $this->assertContains(AllocationMethod::LIFO, $automaticMethods);
        $this->assertContains(AllocationMethod::PROPORTIONAL, $automaticMethods);
        $this->assertContains(AllocationMethod::OLDEST_FIRST, $automaticMethods);
        $this->assertContains(AllocationMethod::SMALLEST_FIRST, $automaticMethods);
        $this->assertContains(AllocationMethod::LARGEST_FIRST, $automaticMethods);
        $this->assertNotContains(AllocationMethod::MANUAL, $automaticMethods);
    }

    #[Test]
    public function it_returns_default_method(): void
    {
        $this->assertSame(AllocationMethod::FIFO, AllocationMethod::default());
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $method = AllocationMethod::from('fifo');

        $this->assertSame(AllocationMethod::FIFO, $method);
    }

    #[Test]
    public function it_returns_null_for_invalid_method(): void
    {
        $method = AllocationMethod::tryFrom('invalid');

        $this->assertNull($method);
    }
}
