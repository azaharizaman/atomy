<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\PolicyAction;
use PHPUnit\Framework\TestCase;

final class PolicyActionTest extends TestCase
{
    public function test_allow_action_does_not_stop_flow(): void
    {
        $this->assertFalse(PolicyAction::ALLOW->stopsFlow());
    }

    public function test_flag_for_review_action_does_not_stop_flow(): void
    {
        $this->assertFalse(PolicyAction::FLAG_FOR_REVIEW->stopsFlow());
    }

    public function test_block_action_stops_flow(): void
    {
        $this->assertTrue(PolicyAction::BLOCK->stopsFlow());
    }

    public function test_require_approval_action_stops_flow(): void
    {
        $this->assertTrue(PolicyAction::REQUIRE_APPROVAL->stopsFlow());
    }

    public function test_route_to_exception_action_stops_flow(): void
    {
        $this->assertTrue(PolicyAction::ROUTE_TO_EXCEPTION->stopsFlow());
    }

    public function test_escalate_action_stops_flow(): void
    {
        $this->assertTrue(PolicyAction::ESCALATE->stopsFlow());
    }

    public function test_all_actions_have_labels(): void
    {
        foreach (PolicyAction::cases() as $action) {
            $this->assertNotEmpty($action->getLabel());
        }
    }

    public function test_action_labels_are_human_readable(): void
    {
        $this->assertSame('Allow', PolicyAction::ALLOW->getLabel());
        $this->assertSame('Block', PolicyAction::BLOCK->getLabel());
        $this->assertSame('Require Approval', PolicyAction::REQUIRE_APPROVAL->getLabel());
        $this->assertSame('Flag for Review', PolicyAction::FLAG_FOR_REVIEW->getLabel());
        $this->assertSame('Route to Exception', PolicyAction::ROUTE_TO_EXCEPTION->getLabel());
        $this->assertSame('Escalate', PolicyAction::ESCALATE->getLabel());
    }
}
