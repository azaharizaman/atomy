<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Enums;

use Nexus\Payment\Enums\DisbursementStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisbursementStatus::class)]
final class DisbursementStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = DisbursementStatus::cases();

        $this->assertCount(8, $cases);
        $this->assertContains(DisbursementStatus::DRAFT, $cases);
        $this->assertContains(DisbursementStatus::PENDING_APPROVAL, $cases);
        $this->assertContains(DisbursementStatus::APPROVED, $cases);
        $this->assertContains(DisbursementStatus::REJECTED, $cases);
        $this->assertContains(DisbursementStatus::PROCESSING, $cases);
        $this->assertContains(DisbursementStatus::COMPLETED, $cases);
        $this->assertContains(DisbursementStatus::FAILED, $cases);
        $this->assertContains(DisbursementStatus::CANCELLED, $cases);
    }

    #[Test]
    #[DataProvider('statusLabelsProvider')]
    public function it_returns_correct_labels(DisbursementStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    public static function statusLabelsProvider(): iterable
    {
        yield 'DRAFT' => [DisbursementStatus::DRAFT, 'Draft'];
        yield 'PENDING_APPROVAL' => [DisbursementStatus::PENDING_APPROVAL, 'Pending Approval'];
        yield 'APPROVED' => [DisbursementStatus::APPROVED, 'Approved'];
        yield 'REJECTED' => [DisbursementStatus::REJECTED, 'Rejected'];
        yield 'PROCESSING' => [DisbursementStatus::PROCESSING, 'Processing'];
        yield 'COMPLETED' => [DisbursementStatus::COMPLETED, 'Completed'];
        yield 'FAILED' => [DisbursementStatus::FAILED, 'Failed'];
        yield 'CANCELLED' => [DisbursementStatus::CANCELLED, 'Cancelled'];
    }

    #[Test]
    #[DataProvider('terminalStatusProvider')]
    public function it_correctly_identifies_terminal_status(DisbursementStatus $status, bool $isTerminal): void
    {
        $this->assertSame($isTerminal, $status->isTerminal());
    }

    public static function terminalStatusProvider(): iterable
    {
        yield 'DRAFT is not terminal' => [DisbursementStatus::DRAFT, false];
        yield 'PENDING_APPROVAL is not terminal' => [DisbursementStatus::PENDING_APPROVAL, false];
        yield 'APPROVED is not terminal' => [DisbursementStatus::APPROVED, false];
        yield 'REJECTED is terminal' => [DisbursementStatus::REJECTED, true];
        yield 'PROCESSING is not terminal' => [DisbursementStatus::PROCESSING, false];
        yield 'COMPLETED is terminal' => [DisbursementStatus::COMPLETED, true];
        yield 'FAILED is terminal' => [DisbursementStatus::FAILED, true];
        yield 'CANCELLED is terminal' => [DisbursementStatus::CANCELLED, true];
    }

    #[Test]
    #[DataProvider('cancellableStatusProvider')]
    public function it_correctly_identifies_cancellable_status(DisbursementStatus $status, bool $isCancellable): void
    {
        $this->assertSame($isCancellable, $status->canCancel());
    }

    public static function cancellableStatusProvider(): iterable
    {
        yield 'DRAFT is cancellable' => [DisbursementStatus::DRAFT, true];
        yield 'PENDING_APPROVAL is cancellable' => [DisbursementStatus::PENDING_APPROVAL, true];
        yield 'APPROVED is cancellable' => [DisbursementStatus::APPROVED, true];
        yield 'REJECTED is not cancellable' => [DisbursementStatus::REJECTED, false];
        yield 'PROCESSING is not cancellable' => [DisbursementStatus::PROCESSING, false];
        yield 'COMPLETED is not cancellable' => [DisbursementStatus::COMPLETED, false];
        yield 'FAILED is not cancellable' => [DisbursementStatus::FAILED, false];
        yield 'CANCELLED is not cancellable' => [DisbursementStatus::CANCELLED, false];
    }

    #[Test]
    public function draft_can_transition_to_pending_approval(): void
    {
        $this->assertTrue(DisbursementStatus::DRAFT->canTransitionTo(DisbursementStatus::PENDING_APPROVAL));
    }

    #[Test]
    public function draft_can_transition_to_cancelled(): void
    {
        $this->assertTrue(DisbursementStatus::DRAFT->canTransitionTo(DisbursementStatus::CANCELLED));
    }

    #[Test]
    public function pending_approval_can_transition_to_approved(): void
    {
        $this->assertTrue(DisbursementStatus::PENDING_APPROVAL->canTransitionTo(DisbursementStatus::APPROVED));
    }

    #[Test]
    public function pending_approval_can_transition_to_rejected(): void
    {
        $this->assertTrue(DisbursementStatus::PENDING_APPROVAL->canTransitionTo(DisbursementStatus::REJECTED));
    }

    #[Test]
    public function pending_approval_can_transition_to_cancelled(): void
    {
        $this->assertTrue(DisbursementStatus::PENDING_APPROVAL->canTransitionTo(DisbursementStatus::CANCELLED));
    }

    #[Test]
    public function approved_can_transition_to_processing(): void
    {
        $this->assertTrue(DisbursementStatus::APPROVED->canTransitionTo(DisbursementStatus::PROCESSING));
    }

    #[Test]
    public function approved_can_transition_to_cancelled(): void
    {
        $this->assertTrue(DisbursementStatus::APPROVED->canTransitionTo(DisbursementStatus::CANCELLED));
    }

    #[Test]
    public function processing_can_transition_to_completed(): void
    {
        $this->assertTrue(DisbursementStatus::PROCESSING->canTransitionTo(DisbursementStatus::COMPLETED));
    }

    #[Test]
    public function processing_can_transition_to_failed(): void
    {
        $this->assertTrue(DisbursementStatus::PROCESSING->canTransitionTo(DisbursementStatus::FAILED));
    }

    #[Test]
    public function terminal_statuses_cannot_transition(): void
    {
        $this->assertEmpty(DisbursementStatus::COMPLETED->getValidTransitions());
        $this->assertEmpty(DisbursementStatus::FAILED->getValidTransitions());
        $this->assertEmpty(DisbursementStatus::CANCELLED->getValidTransitions());
        $this->assertEmpty(DisbursementStatus::REJECTED->getValidTransitions());
    }

    #[Test]
    public function it_returns_allowed_transitions_for_draft(): void
    {
        $allowed = DisbursementStatus::DRAFT->getValidTransitions();

        $this->assertCount(2, $allowed);
        $this->assertContains(DisbursementStatus::PENDING_APPROVAL, $allowed);
        $this->assertContains(DisbursementStatus::CANCELLED, $allowed);
    }

    #[Test]
    public function it_returns_allowed_transitions_for_pending_approval(): void
    {
        $allowed = DisbursementStatus::PENDING_APPROVAL->getValidTransitions();

        $this->assertCount(3, $allowed);
        $this->assertContains(DisbursementStatus::APPROVED, $allowed);
        $this->assertContains(DisbursementStatus::REJECTED, $allowed);
        $this->assertContains(DisbursementStatus::CANCELLED, $allowed);
    }

    #[Test]
    public function it_returns_allowed_transitions_for_approved(): void
    {
        $allowed = DisbursementStatus::APPROVED->getValidTransitions();

        $this->assertCount(2, $allowed);
        $this->assertContains(DisbursementStatus::PROCESSING, $allowed);
        $this->assertContains(DisbursementStatus::CANCELLED, $allowed);
    }

    #[Test]
    public function it_returns_allowed_transitions_for_processing(): void
    {
        $allowed = DisbursementStatus::PROCESSING->getValidTransitions();

        $this->assertCount(2, $allowed);
        $this->assertContains(DisbursementStatus::COMPLETED, $allowed);
        $this->assertContains(DisbursementStatus::FAILED, $allowed);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $status = DisbursementStatus::from('pending_approval');

        $this->assertSame(DisbursementStatus::PENDING_APPROVAL, $status);
    }

    #[Test]
    public function it_returns_null_for_invalid_status(): void
    {
        $status = DisbursementStatus::tryFrom('invalid');

        $this->assertNull($status);
    }
}
