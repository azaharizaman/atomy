<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Enums;

use Nexus\Manufacturing\Enums\ResolutionAction;
use Nexus\Manufacturing\Tests\TestCase;

final class ResolutionActionTest extends TestCase
{
    public function testAllActionsExist(): void
    {
        $this->assertCount(8, ResolutionAction::cases());

        $this->assertSame('alternative_work_center', ResolutionAction::ALTERNATIVE_WORK_CENTER->value);
        $this->assertSame('overtime', ResolutionAction::OVERTIME->value);
        $this->assertSame('reschedule', ResolutionAction::RESCHEDULE->value);
        $this->assertSame('subcontract', ResolutionAction::SUBCONTRACT->value);
        $this->assertSame('split_order', ResolutionAction::SPLIT_ORDER->value);
        $this->assertSame('additional_shift', ResolutionAction::ADDITIONAL_SHIFT->value);
        $this->assertSame('expedite', ResolutionAction::EXPEDITE->value);
        $this->assertSame('reduce_quantity', ResolutionAction::REDUCE_QUANTITY->value);
    }

    public function testLabel(): void
    {
        $this->assertSame('Use Alternative Work Center', ResolutionAction::ALTERNATIVE_WORK_CENTER->label());
        $this->assertSame('Add Overtime', ResolutionAction::OVERTIME->label());
        $this->assertSame('Reschedule Order', ResolutionAction::RESCHEDULE->label());
        $this->assertSame('Subcontract Operation', ResolutionAction::SUBCONTRACT->label());
        $this->assertSame('Split Order', ResolutionAction::SPLIT_ORDER->label());
        $this->assertSame('Add Additional Shift', ResolutionAction::ADDITIONAL_SHIFT->label());
        $this->assertSame('Expedite Operations', ResolutionAction::EXPEDITE->label());
        $this->assertSame('Reduce Quantity', ResolutionAction::REDUCE_QUANTITY->label());
    }

    public function testCanBeAutomated(): void
    {
        $this->assertTrue(ResolutionAction::RESCHEDULE->canBeAutomated());
        $this->assertTrue(ResolutionAction::ALTERNATIVE_WORK_CENTER->canBeAutomated());
        $this->assertTrue(ResolutionAction::SPLIT_ORDER->canBeAutomated());
        $this->assertFalse(ResolutionAction::OVERTIME->canBeAutomated());
        $this->assertFalse(ResolutionAction::SUBCONTRACT->canBeAutomated());
        $this->assertFalse(ResolutionAction::ADDITIONAL_SHIFT->canBeAutomated());
        $this->assertFalse(ResolutionAction::EXPEDITE->canBeAutomated());
        $this->assertFalse(ResolutionAction::REDUCE_QUANTITY->canBeAutomated());
    }

    public function testRequiresApproval(): void
    {
        $this->assertFalse(ResolutionAction::RESCHEDULE->requiresApproval());
        $this->assertFalse(ResolutionAction::ALTERNATIVE_WORK_CENTER->requiresApproval());
        $this->assertFalse(ResolutionAction::SPLIT_ORDER->requiresApproval());
        $this->assertFalse(ResolutionAction::OVERTIME->requiresApproval());
        $this->assertFalse(ResolutionAction::EXPEDITE->requiresApproval());
        $this->assertTrue(ResolutionAction::SUBCONTRACT->requiresApproval());
        $this->assertTrue(ResolutionAction::ADDITIONAL_SHIFT->requiresApproval());
        $this->assertTrue(ResolutionAction::REDUCE_QUANTITY->requiresApproval());
    }

    public function testGetDefaultPriority(): void
    {
        // Lower priority number = higher priority
        $alternativeWc = ResolutionAction::ALTERNATIVE_WORK_CENTER->getDefaultPriority();
        $reschedule = ResolutionAction::RESCHEDULE->getDefaultPriority();
        $reduceQuantity = ResolutionAction::REDUCE_QUANTITY->getDefaultPriority();

        $this->assertSame(1, $alternativeWc);
        $this->assertSame(2, $reschedule);
        $this->assertSame(8, $reduceQuantity);

        $this->assertLessThan($reduceQuantity, $alternativeWc);
        $this->assertLessThan($reduceQuantity, $reschedule);
    }

    public function testGetCostImpact(): void
    {
        $this->assertSame(1, ResolutionAction::RESCHEDULE->getCostImpact());
        $this->assertSame(2, ResolutionAction::SPLIT_ORDER->getCostImpact());
        $this->assertSame(2, ResolutionAction::ALTERNATIVE_WORK_CENTER->getCostImpact());
        $this->assertSame(3, ResolutionAction::OVERTIME->getCostImpact());
        $this->assertSame(4, ResolutionAction::SUBCONTRACT->getCostImpact());
        $this->assertSame(5, ResolutionAction::REDUCE_QUANTITY->getCostImpact());
    }

    public function testGetLeadTimeImpact(): void
    {
        $this->assertSame(-2, ResolutionAction::EXPEDITE->getLeadTimeImpact());
        $this->assertSame(0, ResolutionAction::OVERTIME->getLeadTimeImpact());
        $this->assertSame(0, ResolutionAction::ADDITIONAL_SHIFT->getLeadTimeImpact());
        $this->assertSame(5, ResolutionAction::RESCHEDULE->getLeadTimeImpact());
    }
}
