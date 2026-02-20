<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Enums;

use Nexus\Treasury\Enums\InvestmentStatus;
use PHPUnit\Framework\TestCase;

final class InvestmentStatusTest extends TestCase
{
    public function test_label_returns_correct_string(): void
    {
        $this->assertEquals('Pending', InvestmentStatus::PENDING->label());
        $this->assertEquals('Active', InvestmentStatus::ACTIVE->label());
        $this->assertEquals('Matured', InvestmentStatus::MATURED->label());
        $this->assertEquals('Cancelled', InvestmentStatus::CANCELLED->label());
    }

    public function test_is_pending_returns_true_for_pending(): void
    {
        $this->assertTrue(InvestmentStatus::PENDING->isPending());
        $this->assertFalse(InvestmentStatus::ACTIVE->isPending());
    }

    public function test_is_active_returns_true_for_active(): void
    {
        $this->assertTrue(InvestmentStatus::ACTIVE->isActive());
        $this->assertFalse(InvestmentStatus::PENDING->isActive());
    }

    public function test_is_matured_returns_true_for_matured(): void
    {
        $this->assertTrue(InvestmentStatus::MATURED->isMatured());
        $this->assertFalse(InvestmentStatus::ACTIVE->isMatured());
    }

    public function test_is_cancelled_returns_true_for_cancelled(): void
    {
        $this->assertTrue(InvestmentStatus::CANCELLED->isCancelled());
        $this->assertFalse(InvestmentStatus::ACTIVE->isCancelled());
    }

    public function test_is_final_returns_true_for_final_states(): void
    {
        $this->assertTrue(InvestmentStatus::MATURED->isFinal());
        $this->assertTrue(InvestmentStatus::CANCELLED->isFinal());
        $this->assertFalse(InvestmentStatus::PENDING->isFinal());
        $this->assertFalse(InvestmentStatus::ACTIVE->isFinal());
    }

    public function test_can_transition_to_from_pending(): void
    {
        $this->assertTrue(InvestmentStatus::PENDING->canTransitionTo(InvestmentStatus::ACTIVE));
        $this->assertTrue(InvestmentStatus::PENDING->canTransitionTo(InvestmentStatus::CANCELLED));
        $this->assertFalse(InvestmentStatus::PENDING->canTransitionTo(InvestmentStatus::MATURED));
    }

    public function test_can_transition_to_from_active(): void
    {
        $this->assertTrue(InvestmentStatus::ACTIVE->canTransitionTo(InvestmentStatus::MATURED));
        $this->assertTrue(InvestmentStatus::ACTIVE->canTransitionTo(InvestmentStatus::CANCELLED));
        $this->assertFalse(InvestmentStatus::ACTIVE->canTransitionTo(InvestmentStatus::PENDING));
    }

    public function test_can_transition_to_from_matured(): void
    {
        $this->assertFalse(InvestmentStatus::MATURED->canTransitionTo(InvestmentStatus::ACTIVE));
        $this->assertFalse(InvestmentStatus::MATURED->canTransitionTo(InvestmentStatus::CANCELLED));
    }
}
