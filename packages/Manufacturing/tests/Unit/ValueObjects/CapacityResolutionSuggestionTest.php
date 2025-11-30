<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\ValueObjects;

use Nexus\Manufacturing\Enums\ResolutionAction;
use Nexus\Manufacturing\ValueObjects\CapacityResolutionSuggestion;
use Nexus\Manufacturing\Tests\TestCase;

final class CapacityResolutionSuggestionTest extends TestCase
{
    public function testCreateSuggestion(): void
    {
        $suggestion = new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Add 4 hours of overtime',
            resolvesHours: 4.0,
            priority: 3,
            estimatedCost: 300.0,
            leadTimeImpact: 0,
            requiresApproval: false,
            canAutoApply: false,
        );

        $this->assertSame(ResolutionAction::OVERTIME, $suggestion->action);
        $this->assertSame('Add 4 hours of overtime', $suggestion->description);
        $this->assertSame(4.0, $suggestion->resolvesHours);
        $this->assertSame(3, $suggestion->priority);
        $this->assertSame(300.0, $suggestion->estimatedCost);
        $this->assertSame(0, $suggestion->leadTimeImpact);
        $this->assertFalse($suggestion->requiresApproval);
        $this->assertFalse($suggestion->canAutoApply);
    }

    public function testCreateRescheduleSuggestion(): void
    {
        $newDate = new \DateTimeImmutable('2024-02-15');

        $suggestion = CapacityResolutionSuggestion::reschedule(
            newDate: $newDate,
            resolvesHours: 8.0,
            daysDelayed: 5
        );

        $this->assertSame(ResolutionAction::RESCHEDULE, $suggestion->action);
        $this->assertSame(8.0, $suggestion->resolvesHours);
        $this->assertSame(5, $suggestion->leadTimeImpact);
        $this->assertSame($newDate, $suggestion->suggestedDate);
        $this->assertFalse($suggestion->requiresApproval);
        $this->assertTrue($suggestion->canAutoApply);
        $this->assertSame(0.0, $suggestion->estimatedCost);
    }

    public function testCreateAlternativeWorkCenterSuggestion(): void
    {
        $suggestion = CapacityResolutionSuggestion::alternativeWorkCenter(
            alternativeWorkCenterId: 'wc-alt-001',
            resolvesHours: 16.0,
            additionalCost: 500.0
        );

        $this->assertSame(ResolutionAction::ALTERNATIVE_WORK_CENTER, $suggestion->action);
        $this->assertSame('wc-alt-001', $suggestion->targetResourceId);
        $this->assertSame(16.0, $suggestion->resolvesHours);
        $this->assertSame(500.0, $suggestion->estimatedCost);
        $this->assertFalse($suggestion->requiresApproval);
        $this->assertTrue($suggestion->canAutoApply);
    }

    public function testCreateOvertimeSuggestion(): void
    {
        $suggestion = CapacityResolutionSuggestion::overtime(
            overtimeHours: 4.0,
            overtimeCostPerHour: 75.0
        );

        $this->assertSame(ResolutionAction::OVERTIME, $suggestion->action);
        $this->assertSame(4.0, $suggestion->resolvesHours);
        $this->assertSame(300.0, $suggestion->estimatedCost); // 4 * 75
        $this->assertFalse($suggestion->requiresApproval);
        $this->assertFalse($suggestion->canAutoApply);
        $this->assertSame(4.0, $suggestion->parameters['overtimeHours']);
        $this->assertSame(75.0, $suggestion->parameters['costPerHour']);
    }

    public function testFullyResolves(): void
    {
        $suggestion = new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Add overtime',
            resolvesHours: 8.0,
        );

        $this->assertTrue($suggestion->fullyResolves(8.0));
        $this->assertTrue($suggestion->fullyResolves(6.0));
        $this->assertFalse($suggestion->fullyResolves(10.0));
    }

    public function testGetEffectiveness(): void
    {
        $suggestion = new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Add overtime',
            resolvesHours: 8.0,
        );

        $this->assertSame(100.0, $suggestion->getEffectiveness(8.0));
        $this->assertSame(80.0, $suggestion->getEffectiveness(10.0));
        $this->assertSame(100.0, $suggestion->getEffectiveness(0.0));
    }

    public function testGetCostPerHour(): void
    {
        $suggestion = new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Add overtime',
            resolvesHours: 4.0,
            estimatedCost: 300.0,
        );

        $this->assertSame(75.0, $suggestion->getCostPerHour());
    }

    public function testIsLowCost(): void
    {
        $lowCost = new CapacityResolutionSuggestion(
            action: ResolutionAction::RESCHEDULE,
            description: 'Reschedule',
            resolvesHours: 8.0,
            estimatedCost: 0.0,
        );

        $highCost = new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Overtime',
            resolvesHours: 8.0,
            estimatedCost: 500.0,
        );

        $this->assertTrue($lowCost->isLowCost());
        $this->assertFalse($highCost->isLowCost());
    }

    public function testImprovesDelivery(): void
    {
        $faster = new CapacityResolutionSuggestion(
            action: ResolutionAction::EXPEDITE,
            description: 'Expedite',
            resolvesHours: 8.0,
            leadTimeImpact: -2,
        );

        $slower = new CapacityResolutionSuggestion(
            action: ResolutionAction::RESCHEDULE,
            description: 'Reschedule',
            resolvesHours: 8.0,
            leadTimeImpact: 5,
        );

        $this->assertTrue($faster->improvesDelivery());
        $this->assertFalse($slower->improvesDelivery());
    }

    public function testHasHigherPriorityThan(): void
    {
        $high = new CapacityResolutionSuggestion(
            action: ResolutionAction::ALTERNATIVE_WORK_CENTER,
            description: 'Alternative WC',
            resolvesHours: 8.0,
            priority: 1,
        );

        $low = new CapacityResolutionSuggestion(
            action: ResolutionAction::REDUCE_QUANTITY,
            description: 'Reduce qty',
            resolvesHours: 8.0,
            priority: 8,
        );

        $this->assertTrue($high->hasHigherPriorityThan($low));
        $this->assertFalse($low->hasHigherPriorityThan($high));
    }

    public function testWithPriority(): void
    {
        $suggestion = new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Overtime',
            resolvesHours: 8.0,
            priority: 5,
        );

        $newSuggestion = $suggestion->withPriority(2);

        $this->assertSame(2, $newSuggestion->priority);
        $this->assertSame(8.0, $newSuggestion->resolvesHours);
    }

    public function testToArray(): void
    {
        $suggestion = CapacityResolutionSuggestion::overtime(
            overtimeHours: 4.0,
            overtimeCostPerHour: 75.0
        );

        $array = $suggestion->toArray();

        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('actionLabel', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('resolvesHours', $array);
        $this->assertArrayHasKey('priority', $array);
        $this->assertArrayHasKey('estimatedCost', $array);
        $this->assertArrayHasKey('requiresApproval', $array);
        $this->assertArrayHasKey('canAutoApply', $array);
        $this->assertArrayHasKey('costPerHour', $array);

        $this->assertSame('overtime', $array['action']);
        $this->assertSame(4.0, $array['resolvesHours']);
        $this->assertSame(300.0, $array['estimatedCost']);
    }

    public function testFromArray(): void
    {
        $data = [
            'action' => 'reschedule',
            'description' => 'Reschedule to available date',
            'resolvesHours' => 8.0,
            'priority' => 2,
            'estimatedCost' => 0.0,
            'leadTimeImpact' => 3,
            'requiresApproval' => false,
            'canAutoApply' => true,
        ];

        $suggestion = CapacityResolutionSuggestion::fromArray($data);

        $this->assertSame(ResolutionAction::RESCHEDULE, $suggestion->action);
        $this->assertSame(8.0, $suggestion->resolvesHours);
        $this->assertSame(2, $suggestion->priority);
    }

    public function testThrowsExceptionForNegativeResolvesHours(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resolved hours cannot be negative');

        new CapacityResolutionSuggestion(
            action: ResolutionAction::OVERTIME,
            description: 'Invalid',
            resolvesHours: -5.0,
        );
    }
}
