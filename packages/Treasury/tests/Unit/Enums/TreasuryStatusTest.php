<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Enums;

use Nexus\Treasury\Enums\TreasuryStatus;
use PHPUnit\Framework\TestCase;

final class TreasuryStatusTest extends TestCase
{
    public function test_label_returns_correct_string(): void
    {
        $this->assertEquals('Active', TreasuryStatus::ACTIVE->label());
        $this->assertEquals('Inactive', TreasuryStatus::INACTIVE->label());
        $this->assertEquals('Pending', TreasuryStatus::PENDING->label());
        $this->assertEquals('Suspended', TreasuryStatus::SUSPENDED->label());
        $this->assertEquals('Closed', TreasuryStatus::CLOSED->label());
    }

    public function test_is_active_returns_true_for_active(): void
    {
        $this->assertTrue(TreasuryStatus::ACTIVE->isActive());
        $this->assertFalse(TreasuryStatus::INACTIVE->isActive());
    }

    public function test_is_inactive_returns_true_for_inactive(): void
    {
        $this->assertTrue(TreasuryStatus::INACTIVE->isInactive());
        $this->assertFalse(TreasuryStatus::ACTIVE->isInactive());
    }

    public function test_is_pending_returns_true_for_pending(): void
    {
        $this->assertTrue(TreasuryStatus::PENDING->isPending());
        $this->assertFalse(TreasuryStatus::ACTIVE->isPending());
    }

    public function test_is_suspended_returns_true_for_suspended(): void
    {
        $this->assertTrue(TreasuryStatus::SUSPENDED->isSuspended());
        $this->assertFalse(TreasuryStatus::ACTIVE->isSuspended());
    }

    public function test_is_closed_returns_true_for_closed(): void
    {
        $this->assertTrue(TreasuryStatus::CLOSED->isClosed());
        $this->assertFalse(TreasuryStatus::ACTIVE->isClosed());
    }

    public function test_can_transition_to_from_pending(): void
    {
        $this->assertTrue(TreasuryStatus::PENDING->canTransitionTo(TreasuryStatus::ACTIVE));
        $this->assertTrue(TreasuryStatus::PENDING->canTransitionTo(TreasuryStatus::INACTIVE));
        $this->assertTrue(TreasuryStatus::PENDING->canTransitionTo(TreasuryStatus::SUSPENDED));
        $this->assertTrue(TreasuryStatus::PENDING->canTransitionTo(TreasuryStatus::CLOSED));
        $this->assertFalse(TreasuryStatus::PENDING->canTransitionTo(TreasuryStatus::PENDING));
    }

    public function test_can_transition_to_from_active(): void
    {
        $this->assertFalse(TreasuryStatus::ACTIVE->canTransitionTo(TreasuryStatus::PENDING));
        $this->assertTrue(TreasuryStatus::ACTIVE->canTransitionTo(TreasuryStatus::INACTIVE));
        $this->assertTrue(TreasuryStatus::ACTIVE->canTransitionTo(TreasuryStatus::SUSPENDED));
        $this->assertTrue(TreasuryStatus::ACTIVE->canTransitionTo(TreasuryStatus::CLOSED));
    }

    public function test_can_transition_to_from_closed(): void
    {
        $this->assertFalse(TreasuryStatus::CLOSED->canTransitionTo(TreasuryStatus::ACTIVE));
        $this->assertFalse(TreasuryStatus::CLOSED->canTransitionTo(TreasuryStatus::INACTIVE));
        $this->assertFalse(TreasuryStatus::CLOSED->canTransitionTo(TreasuryStatus::PENDING));
    }
}
