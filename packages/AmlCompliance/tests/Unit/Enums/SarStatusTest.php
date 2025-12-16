<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Enums;

use Nexus\AmlCompliance\Enums\SarStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SarStatus::class)]
final class SarStatusTest extends TestCase
{
    public function test_all_nine_cases_exist(): void
    {
        $cases = SarStatus::cases();
        $this->assertCount(9, $cases);
    }

    public function test_draft_has_correct_value(): void
    {
        $this->assertSame('draft', SarStatus::DRAFT->value);
    }

    public function test_pending_review_has_correct_value(): void
    {
        $this->assertSame('pending_review', SarStatus::PENDING_REVIEW->value);
    }

    public function test_approved_has_correct_value(): void
    {
        $this->assertSame('approved', SarStatus::APPROVED->value);
    }

    public function test_submitted_has_correct_value(): void
    {
        $this->assertSame('submitted', SarStatus::SUBMITTED->value);
    }

    public function test_under_investigation_has_correct_value(): void
    {
        $this->assertSame('under_investigation', SarStatus::UNDER_INVESTIGATION->value);
    }

    public function test_information_requested_has_correct_value(): void
    {
        $this->assertSame('information_requested', SarStatus::INFORMATION_REQUESTED->value);
    }

    public function test_closed_has_correct_value(): void
    {
        $this->assertSame('closed', SarStatus::CLOSED->value);
    }

    public function test_rejected_has_correct_value(): void
    {
        $this->assertSame('rejected', SarStatus::REJECTED->value);
    }

    public function test_cancelled_has_correct_value(): void
    {
        $this->assertSame('cancelled', SarStatus::CANCELLED->value);
    }

    public function test_draft_can_transition_to_pending_review(): void
    {
        $this->assertTrue(SarStatus::DRAFT->canTransitionTo(SarStatus::PENDING_REVIEW));
    }

    public function test_draft_can_transition_to_cancelled(): void
    {
        $this->assertTrue(SarStatus::DRAFT->canTransitionTo(SarStatus::CANCELLED));
    }

    public function test_draft_cannot_transition_to_submitted(): void
    {
        $this->assertFalse(SarStatus::DRAFT->canTransitionTo(SarStatus::SUBMITTED));
    }

    public function test_pending_review_can_transition_to_approved(): void
    {
        $this->assertTrue(SarStatus::PENDING_REVIEW->canTransitionTo(SarStatus::APPROVED));
    }

    public function test_pending_review_can_transition_to_rejected(): void
    {
        $this->assertTrue(SarStatus::PENDING_REVIEW->canTransitionTo(SarStatus::REJECTED));
    }

    public function test_pending_review_can_transition_back_to_draft(): void
    {
        $this->assertTrue(SarStatus::PENDING_REVIEW->canTransitionTo(SarStatus::DRAFT));
    }

    public function test_approved_can_transition_to_submitted(): void
    {
        $this->assertTrue(SarStatus::APPROVED->canTransitionTo(SarStatus::SUBMITTED));
    }

    public function test_submitted_can_transition_to_under_investigation(): void
    {
        $this->assertTrue(SarStatus::SUBMITTED->canTransitionTo(SarStatus::UNDER_INVESTIGATION));
    }

    public function test_submitted_can_transition_to_information_requested(): void
    {
        $this->assertTrue(SarStatus::SUBMITTED->canTransitionTo(SarStatus::INFORMATION_REQUESTED));
    }

    public function test_closed_cannot_transition_anywhere(): void
    {
        $this->assertEmpty(SarStatus::CLOSED->getAllowedTransitions());
    }

    public function test_cancelled_cannot_transition_anywhere(): void
    {
        $this->assertEmpty(SarStatus::CANCELLED->getAllowedTransitions());
    }

    public function test_get_allowed_transitions_returns_array(): void
    {
        $transitions = SarStatus::DRAFT->getAllowedTransitions();
        $this->assertIsArray($transitions);
        $this->assertNotEmpty($transitions);
    }

    public function test_requires_approval(): void
    {
        $this->assertFalse(SarStatus::DRAFT->requiresApproval());
        $this->assertTrue(SarStatus::PENDING_REVIEW->requiresApproval());
        $this->assertFalse(SarStatus::APPROVED->requiresApproval());
        $this->assertTrue(SarStatus::INFORMATION_REQUESTED->requiresApproval());
    }

    public function test_is_final(): void
    {
        $this->assertFalse(SarStatus::DRAFT->isFinal());
        $this->assertFalse(SarStatus::PENDING_REVIEW->isFinal());
        $this->assertFalse(SarStatus::APPROVED->isFinal());
        $this->assertFalse(SarStatus::SUBMITTED->isFinal());
        $this->assertFalse(SarStatus::UNDER_INVESTIGATION->isFinal());
        $this->assertFalse(SarStatus::INFORMATION_REQUESTED->isFinal());
        $this->assertTrue(SarStatus::CLOSED->isFinal());
        $this->assertFalse(SarStatus::REJECTED->isFinal());
        $this->assertTrue(SarStatus::CANCELLED->isFinal());
    }

    public function test_is_editable(): void
    {
        $this->assertTrue(SarStatus::DRAFT->isEditable());
        $this->assertFalse(SarStatus::PENDING_REVIEW->isEditable());
        $this->assertFalse(SarStatus::APPROVED->isEditable());
        $this->assertFalse(SarStatus::SUBMITTED->isEditable());
        $this->assertTrue(SarStatus::REJECTED->isEditable());
    }

    public function test_is_submitted(): void
    {
        $this->assertFalse(SarStatus::DRAFT->isSubmitted());
        $this->assertFalse(SarStatus::PENDING_REVIEW->isSubmitted());
        $this->assertFalse(SarStatus::APPROVED->isSubmitted());
        $this->assertTrue(SarStatus::SUBMITTED->isSubmitted());
        $this->assertTrue(SarStatus::UNDER_INVESTIGATION->isSubmitted());
        $this->assertTrue(SarStatus::INFORMATION_REQUESTED->isSubmitted());
        $this->assertTrue(SarStatus::CLOSED->isSubmitted());
    }

    public function test_is_active(): void
    {
        $this->assertTrue(SarStatus::DRAFT->isActive());
        $this->assertTrue(SarStatus::PENDING_REVIEW->isActive());
        $this->assertTrue(SarStatus::APPROVED->isActive());
        $this->assertTrue(SarStatus::SUBMITTED->isActive());
        $this->assertFalse(SarStatus::CLOSED->isActive());
        $this->assertFalse(SarStatus::REJECTED->isActive());
        $this->assertFalse(SarStatus::CANCELLED->isActive());
    }

    public function test_is_pending(): void
    {
        $this->assertFalse(SarStatus::DRAFT->isPending());
        $this->assertTrue(SarStatus::PENDING_REVIEW->isPending());
        $this->assertFalse(SarStatus::APPROVED->isPending());
        $this->assertTrue(SarStatus::INFORMATION_REQUESTED->isPending());
    }

    public function test_get_sla_hours(): void
    {
        $this->assertSame(168, SarStatus::DRAFT->getSlaHours());
        $this->assertSame(48, SarStatus::PENDING_REVIEW->getSlaHours());
        $this->assertSame(24, SarStatus::APPROVED->getSlaHours());
        $this->assertSame(72, SarStatus::INFORMATION_REQUESTED->getSlaHours());
    }

    public function test_get_description(): void
    {
        $this->assertIsString(SarStatus::DRAFT->getDescription());
        $this->assertNotEmpty(SarStatus::DRAFT->getDescription());
    }

    public function test_get_color(): void
    {
        $this->assertIsString(SarStatus::DRAFT->getColor());
        $this->assertNotEmpty(SarStatus::DRAFT->getColor());
    }

    public function test_get_phase(): void
    {
        $this->assertIsString(SarStatus::DRAFT->getPhase());
        $this->assertNotEmpty(SarStatus::DRAFT->getPhase());
    }

    public function test_get_priority(): void
    {
        $this->assertIsInt(SarStatus::DRAFT->getPriority());
        $this->assertIsInt(SarStatus::PENDING_REVIEW->getPriority());
    }

    public function test_get_icon(): void
    {
        $this->assertIsString(SarStatus::DRAFT->getIcon());
        $this->assertNotEmpty(SarStatus::DRAFT->getIcon());
    }

    public function test_get_filing_deadline_days(): void
    {
        $this->assertIsInt(SarStatus::DRAFT->getFilingDeadlineDays());
        $this->assertGreaterThanOrEqual(0, SarStatus::DRAFT->getFilingDeadlineDays());
    }

    public function test_workflow_order_returns_all_statuses_in_order(): void
    {
        $order = SarStatus::workflowOrder();
        $this->assertIsArray($order);
        $this->assertNotEmpty($order);
        $this->assertContainsOnlyInstancesOf(SarStatus::class, $order);
    }

    public function test_terminal_statuses_returns_final_states(): void
    {
        $terminal = SarStatus::terminalStatuses();
        $this->assertIsArray($terminal);
        $this->assertContains(SarStatus::CLOSED, $terminal);
        $this->assertContains(SarStatus::CANCELLED, $terminal);
    }

    public function test_action_required_returns_statuses_needing_action(): void
    {
        $actionRequired = SarStatus::actionRequired();
        $this->assertIsArray($actionRequired);
        $this->assertNotEmpty($actionRequired);
    }
}
