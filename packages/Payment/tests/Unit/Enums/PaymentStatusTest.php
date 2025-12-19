<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Nexus\Payment\Enums\PaymentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PaymentStatus::class)]
final class PaymentStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = PaymentStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertContains(PaymentStatus::PENDING, $cases);
        $this->assertContains(PaymentStatus::PROCESSING, $cases);
        $this->assertContains(PaymentStatus::COMPLETED, $cases);
        $this->assertContains(PaymentStatus::FAILED, $cases);
        $this->assertContains(PaymentStatus::CANCELLED, $cases);
        $this->assertContains(PaymentStatus::REVERSED, $cases);
    }

    #[Test]
    #[DataProvider('statusLabelsProvider')]
    public function it_returns_correct_labels(PaymentStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    public static function statusLabelsProvider(): iterable
    {
        yield 'PENDING' => [PaymentStatus::PENDING, 'Pending'];
        yield 'PROCESSING' => [PaymentStatus::PROCESSING, 'Processing'];
        yield 'COMPLETED' => [PaymentStatus::COMPLETED, 'Completed'];
        yield 'FAILED' => [PaymentStatus::FAILED, 'Failed'];
        yield 'CANCELLED' => [PaymentStatus::CANCELLED, 'Cancelled'];
        yield 'REVERSED' => [PaymentStatus::REVERSED, 'Reversed'];
    }

    #[Test]
    #[DataProvider('terminalStatusProvider')]
    public function it_correctly_identifies_terminal_status(PaymentStatus $status, bool $isTerminal): void
    {
        $this->assertSame($isTerminal, $status->isTerminal());
    }

    public static function terminalStatusProvider(): iterable
    {
        yield 'PENDING is not terminal' => [PaymentStatus::PENDING, false];
        yield 'PROCESSING is not terminal' => [PaymentStatus::PROCESSING, false];
        yield 'COMPLETED is terminal' => [PaymentStatus::COMPLETED, true];
        yield 'FAILED is terminal' => [PaymentStatus::FAILED, true];
        yield 'CANCELLED is terminal' => [PaymentStatus::CANCELLED, true];
        yield 'REVERSED is terminal' => [PaymentStatus::REVERSED, true];
    }

    #[Test]
    public function it_returns_correct_terminal_statuses(): void
    {
        $terminalStatuses = PaymentStatus::terminalStatuses();

        $this->assertCount(4, $terminalStatuses);
        $this->assertContains(PaymentStatus::COMPLETED, $terminalStatuses);
        $this->assertContains(PaymentStatus::FAILED, $terminalStatuses);
        $this->assertContains(PaymentStatus::CANCELLED, $terminalStatuses);
        $this->assertContains(PaymentStatus::REVERSED, $terminalStatuses);
    }

    #[Test]
    public function pending_can_transition_to_processing(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::PROCESSING));
    }

    #[Test]
    public function pending_can_transition_to_cancelled(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::CANCELLED));
    }

    #[Test]
    public function pending_cannot_transition_to_completed_directly(): void
    {
        $this->assertFalse(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::COMPLETED));
    }

    #[Test]
    public function pending_cannot_transition_to_reversed(): void
    {
        $this->assertFalse(PaymentStatus::PENDING->canTransitionTo(PaymentStatus::REVERSED));
    }

    #[Test]
    public function processing_can_transition_to_completed(): void
    {
        $this->assertTrue(PaymentStatus::PROCESSING->canTransitionTo(PaymentStatus::COMPLETED));
    }

    #[Test]
    public function processing_can_transition_to_failed(): void
    {
        $this->assertTrue(PaymentStatus::PROCESSING->canTransitionTo(PaymentStatus::FAILED));
    }

    #[Test]
    public function completed_can_transition_to_reversed(): void
    {
        $this->assertTrue(PaymentStatus::COMPLETED->canTransitionTo(PaymentStatus::REVERSED));
    }

    #[Test]
    public function terminal_statuses_cannot_transition_except_completed_to_reversed(): void
    {
        // FAILED cannot transition anywhere
        $this->assertEmpty(PaymentStatus::FAILED->allowedTransitions());

        // CANCELLED cannot transition anywhere
        $this->assertEmpty(PaymentStatus::CANCELLED->allowedTransitions());

        // REVERSED cannot transition anywhere
        $this->assertEmpty(PaymentStatus::REVERSED->allowedTransitions());
    }

    #[Test]
    public function it_returns_allowed_transitions_for_pending(): void
    {
        $allowed = PaymentStatus::PENDING->allowedTransitions();

        $this->assertCount(2, $allowed);
        $this->assertContains(PaymentStatus::PROCESSING, $allowed);
        $this->assertContains(PaymentStatus::CANCELLED, $allowed);
    }

    #[Test]
    public function it_returns_allowed_transitions_for_processing(): void
    {
        $allowed = PaymentStatus::PROCESSING->allowedTransitions();

        $this->assertCount(2, $allowed);
        $this->assertContains(PaymentStatus::COMPLETED, $allowed);
        $this->assertContains(PaymentStatus::FAILED, $allowed);
    }

    #[Test]
    public function it_returns_allowed_transitions_for_completed(): void
    {
        $allowed = PaymentStatus::COMPLETED->allowedTransitions();

        $this->assertCount(1, $allowed);
        $this->assertContains(PaymentStatus::REVERSED, $allowed);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $status = PaymentStatus::from('pending');

        $this->assertSame(PaymentStatus::PENDING, $status);
    }

    #[Test]
    public function it_returns_null_for_invalid_status(): void
    {
        $status = PaymentStatus::tryFrom('invalid');

        $this->assertNull($status);
    }
}
