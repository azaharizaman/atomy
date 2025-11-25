<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Enums;

use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Tests\TestCase;

final class WorkOrderStatusTest extends TestCase
{
    public function testAllStatusesExist(): void
    {
        $this->assertCount(8, WorkOrderStatus::cases());

        $this->assertSame('draft', WorkOrderStatus::DRAFT->value);
        $this->assertSame('planned', WorkOrderStatus::PLANNED->value);
        $this->assertSame('released', WorkOrderStatus::RELEASED->value);
        $this->assertSame('in_progress', WorkOrderStatus::IN_PROGRESS->value);
        $this->assertSame('completed', WorkOrderStatus::COMPLETED->value);
        $this->assertSame('closed', WorkOrderStatus::CLOSED->value);
        $this->assertSame('cancelled', WorkOrderStatus::CANCELLED->value);
        $this->assertSame('on_hold', WorkOrderStatus::ON_HOLD->value);
    }

    public function testValidTransitionsFromDraft(): void
    {
        $draft = WorkOrderStatus::DRAFT;
        $transitions = $draft->getValidTransitions();

        $this->assertContains(WorkOrderStatus::PLANNED, $transitions);
        $this->assertContains(WorkOrderStatus::CANCELLED, $transitions);
        $this->assertNotContains(WorkOrderStatus::RELEASED, $transitions);
        $this->assertNotContains(WorkOrderStatus::IN_PROGRESS, $transitions);
        $this->assertNotContains(WorkOrderStatus::COMPLETED, $transitions);
        $this->assertNotContains(WorkOrderStatus::CLOSED, $transitions);
    }

    public function testValidTransitionsFromPlanned(): void
    {
        $planned = WorkOrderStatus::PLANNED;
        $transitions = $planned->getValidTransitions();

        $this->assertContains(WorkOrderStatus::RELEASED, $transitions);
        $this->assertContains(WorkOrderStatus::ON_HOLD, $transitions);
        $this->assertContains(WorkOrderStatus::CANCELLED, $transitions);
        $this->assertNotContains(WorkOrderStatus::DRAFT, $transitions);
        $this->assertNotContains(WorkOrderStatus::IN_PROGRESS, $transitions);
        $this->assertNotContains(WorkOrderStatus::COMPLETED, $transitions);
    }

    public function testValidTransitionsFromReleased(): void
    {
        $released = WorkOrderStatus::RELEASED;
        $transitions = $released->getValidTransitions();

        $this->assertContains(WorkOrderStatus::IN_PROGRESS, $transitions);
        $this->assertContains(WorkOrderStatus::ON_HOLD, $transitions);
        $this->assertContains(WorkOrderStatus::CANCELLED, $transitions);
        $this->assertNotContains(WorkOrderStatus::DRAFT, $transitions);
        $this->assertNotContains(WorkOrderStatus::PLANNED, $transitions);
        $this->assertNotContains(WorkOrderStatus::COMPLETED, $transitions);
    }

    public function testValidTransitionsFromInProgress(): void
    {
        $inProgress = WorkOrderStatus::IN_PROGRESS;
        $transitions = $inProgress->getValidTransitions();

        $this->assertContains(WorkOrderStatus::COMPLETED, $transitions);
        $this->assertContains(WorkOrderStatus::ON_HOLD, $transitions);
        $this->assertNotContains(WorkOrderStatus::DRAFT, $transitions);
        $this->assertNotContains(WorkOrderStatus::PLANNED, $transitions);
        $this->assertNotContains(WorkOrderStatus::RELEASED, $transitions);
        $this->assertNotContains(WorkOrderStatus::CANCELLED, $transitions);
    }

    public function testValidTransitionsFromCompleted(): void
    {
        $completed = WorkOrderStatus::COMPLETED;
        $transitions = $completed->getValidTransitions();

        $this->assertContains(WorkOrderStatus::CLOSED, $transitions);
        $this->assertNotContains(WorkOrderStatus::DRAFT, $transitions);
        $this->assertNotContains(WorkOrderStatus::PLANNED, $transitions);
        $this->assertNotContains(WorkOrderStatus::RELEASED, $transitions);
        $this->assertNotContains(WorkOrderStatus::IN_PROGRESS, $transitions);
        $this->assertNotContains(WorkOrderStatus::CANCELLED, $transitions);
    }

    public function testClosedIsFinalState(): void
    {
        $closed = WorkOrderStatus::CLOSED;
        $transitions = $closed->getValidTransitions();

        $this->assertEmpty($transitions);
    }

    public function testCancelledIsFinalState(): void
    {
        $cancelled = WorkOrderStatus::CANCELLED;
        $transitions = $cancelled->getValidTransitions();

        $this->assertEmpty($transitions);
    }

    public function testAllowsProduction(): void
    {
        $this->assertFalse(WorkOrderStatus::DRAFT->allowsProduction());
        $this->assertFalse(WorkOrderStatus::PLANNED->allowsProduction());
        $this->assertTrue(WorkOrderStatus::RELEASED->allowsProduction());
        $this->assertTrue(WorkOrderStatus::IN_PROGRESS->allowsProduction());
        $this->assertFalse(WorkOrderStatus::COMPLETED->allowsProduction());
        $this->assertFalse(WorkOrderStatus::CLOSED->allowsProduction());
        $this->assertFalse(WorkOrderStatus::CANCELLED->allowsProduction());
        $this->assertFalse(WorkOrderStatus::ON_HOLD->allowsProduction());
    }

    public function testAllowsModification(): void
    {
        $this->assertTrue(WorkOrderStatus::DRAFT->allowsModification());
        $this->assertTrue(WorkOrderStatus::PLANNED->allowsModification());
        $this->assertFalse(WorkOrderStatus::RELEASED->allowsModification());
        $this->assertFalse(WorkOrderStatus::IN_PROGRESS->allowsModification());
        $this->assertFalse(WorkOrderStatus::COMPLETED->allowsModification());
        $this->assertFalse(WorkOrderStatus::CLOSED->allowsModification());
        $this->assertFalse(WorkOrderStatus::CANCELLED->allowsModification());
        $this->assertTrue(WorkOrderStatus::ON_HOLD->allowsModification());
    }

    public function testIsTerminal(): void
    {
        $this->assertFalse(WorkOrderStatus::DRAFT->isTerminal());
        $this->assertFalse(WorkOrderStatus::PLANNED->isTerminal());
        $this->assertFalse(WorkOrderStatus::RELEASED->isTerminal());
        $this->assertFalse(WorkOrderStatus::IN_PROGRESS->isTerminal());
        $this->assertFalse(WorkOrderStatus::COMPLETED->isTerminal());
        $this->assertTrue(WorkOrderStatus::CLOSED->isTerminal());
        $this->assertTrue(WorkOrderStatus::CANCELLED->isTerminal());
        $this->assertFalse(WorkOrderStatus::ON_HOLD->isTerminal());
    }

    public function testLabel(): void
    {
        $this->assertSame('Draft', WorkOrderStatus::DRAFT->label());
        $this->assertSame('Planned', WorkOrderStatus::PLANNED->label());
        $this->assertSame('Released', WorkOrderStatus::RELEASED->label());
        $this->assertSame('In Progress', WorkOrderStatus::IN_PROGRESS->label());
        $this->assertSame('Completed', WorkOrderStatus::COMPLETED->label());
        $this->assertSame('Closed', WorkOrderStatus::CLOSED->label());
        $this->assertSame('Cancelled', WorkOrderStatus::CANCELLED->label());
        $this->assertSame('On Hold', WorkOrderStatus::ON_HOLD->label());
    }
}
