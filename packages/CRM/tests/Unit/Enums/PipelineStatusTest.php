<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Enums;

use Nexus\CRM\Enums\PipelineStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PipelineStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $cases = PipelineStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(PipelineStatus::Active, $cases);
        $this->assertContains(PipelineStatus::Inactive, $cases);
        $this->assertContains(PipelineStatus::Archived, $cases);
    }

    #[Test]
    public function it_has_correct_string_values(): void
    {
        $this->assertSame('active', PipelineStatus::Active->value);
        $this->assertSame('inactive', PipelineStatus::Inactive->value);
        $this->assertSame('archived', PipelineStatus::Archived->value);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Active', PipelineStatus::Active->label());
        $this->assertSame('Inactive', PipelineStatus::Inactive->label());
        $this->assertSame('Archived', PipelineStatus::Archived->label());
    }

    #[Test]
    public function it_identifies_active_status_correctly(): void
    {
        $this->assertTrue(PipelineStatus::Active->isActive());
        $this->assertFalse(PipelineStatus::Inactive->isActive());
        $this->assertFalse(PipelineStatus::Archived->isActive());
    }

    #[Test]
    #[DataProvider('usableStatusProvider')]
    public function it_identifies_usable_statuses_correctly(PipelineStatus $status, bool $expectedIsUsable): void
    {
        $this->assertSame($expectedIsUsable, $status->isUsable());
    }

    public static function usableStatusProvider(): array
    {
        return [
            'active is usable' => [PipelineStatus::Active, true],
            'inactive is not usable' => [PipelineStatus::Inactive, false],
            'archived is not usable' => [PipelineStatus::Archived, false],
        ];
    }

    #[Test]
    public function it_identifies_archived_status_correctly(): void
    {
        $this->assertTrue(PipelineStatus::Archived->isArchived());
        $this->assertFalse(PipelineStatus::Active->isArchived());
        $this->assertFalse(PipelineStatus::Inactive->isArchived());
    }

    #[Test]
    public function it_returns_correct_valid_transitions_for_active(): void
    {
        $transitions = PipelineStatus::Active->getValidTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(PipelineStatus::Inactive, $transitions);
        $this->assertContains(PipelineStatus::Archived, $transitions);
    }

    #[Test]
    public function it_returns_correct_valid_transitions_for_inactive(): void
    {
        $transitions = PipelineStatus::Inactive->getValidTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(PipelineStatus::Active, $transitions);
        $this->assertContains(PipelineStatus::Archived, $transitions);
    }

    #[Test]
    public function it_returns_empty_transitions_for_archived(): void
    {
        $transitions = PipelineStatus::Archived->getValidTransitions();

        $this->assertEmpty($transitions);
    }

    #[Test]
    #[DataProvider('validTransitionProvider')]
    public function it_validates_transitions_correctly(PipelineStatus $from, PipelineStatus $to, bool $expectedCanTransition): void
    {
        $this->assertSame($expectedCanTransition, $from->canTransitionTo($to));
    }

    public static function validTransitionProvider(): array
    {
        return [
            // Valid transitions from Active
            'active to inactive' => [PipelineStatus::Active, PipelineStatus::Inactive, true],
            'active to archived' => [PipelineStatus::Active, PipelineStatus::Archived, true],
            'active to active (invalid)' => [PipelineStatus::Active, PipelineStatus::Active, false],

            // Valid transitions from Inactive
            'inactive to active' => [PipelineStatus::Inactive, PipelineStatus::Active, true],
            'inactive to archived' => [PipelineStatus::Inactive, PipelineStatus::Archived, true],
            'inactive to inactive (invalid)' => [PipelineStatus::Inactive, PipelineStatus::Inactive, false],

            // No transitions from Archived
            'archived to active (invalid)' => [PipelineStatus::Archived, PipelineStatus::Active, false],
            'archived to inactive (invalid)' => [PipelineStatus::Archived, PipelineStatus::Inactive, false],
            'archived to archived (invalid)' => [PipelineStatus::Archived, PipelineStatus::Archived, false],
        ];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $status = PipelineStatus::from('inactive');

        $this->assertSame(PipelineStatus::Inactive, $status);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string_value(): void
    {
        $this->expectException(\ValueError::class);

        PipelineStatus::from('invalid_status');
    }

    #[Test]
    public function it_can_try_from_string_safely(): void
    {
        $status = PipelineStatus::tryFrom('archived');

        $this->assertSame(PipelineStatus::Archived, $status);
    }

    #[Test]
    public function it_returns_null_for_invalid_try_from(): void
    {
        $status = PipelineStatus::tryFrom('invalid_status');

        $this->assertNull($status);
    }

    #[Test]
    public function archived_is_a_final_state(): void
    {
        $this->assertEmpty(PipelineStatus::Archived->getValidTransitions());
    }

    #[Test]
    public function active_and_inactive_can_both_transition_to_archived(): void
    {
        $activeTransitions = PipelineStatus::Active->getValidTransitions();
        $inactiveTransitions = PipelineStatus::Inactive->getValidTransitions();

        $this->assertContains(PipelineStatus::Archived, $activeTransitions);
        $this->assertContains(PipelineStatus::Archived, $inactiveTransitions);
    }

    #[Test]
    public function usable_status_is_same_as_active(): void
    {
        // isUsable should be equivalent to isActive
        foreach (PipelineStatus::cases() as $status) {
            $this->assertSame(
                $status->isActive(),
                $status->isUsable(),
                sprintf('%s: isUsable should equal isActive', $status->name)
            );
        }
    }
}
