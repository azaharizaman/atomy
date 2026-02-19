<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Enums;

use Nexus\CRM\Enums\LeadStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $cases = LeadStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(LeadStatus::New, $cases);
        $this->assertContains(LeadStatus::Contacted, $cases);
        $this->assertContains(LeadStatus::Qualified, $cases);
        $this->assertContains(LeadStatus::Disqualified, $cases);
        $this->assertContains(LeadStatus::Converted, $cases);
    }

    #[Test]
    public function it_has_correct_string_values(): void
    {
        $this->assertSame('new', LeadStatus::New->value);
        $this->assertSame('contacted', LeadStatus::Contacted->value);
        $this->assertSame('qualified', LeadStatus::Qualified->value);
        $this->assertSame('disqualified', LeadStatus::Disqualified->value);
        $this->assertSame('converted', LeadStatus::Converted->value);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('New', LeadStatus::New->label());
        $this->assertSame('Contacted', LeadStatus::Contacted->label());
        $this->assertSame('Qualified', LeadStatus::Qualified->label());
        $this->assertSame('Disqualified', LeadStatus::Disqualified->label());
        $this->assertSame('Converted', LeadStatus::Converted->label());
    }

    #[Test]
    #[DataProvider('activeStatusProvider')]
    public function it_identifies_active_statuses_correctly(LeadStatus $status, bool $expectedIsActive): void
    {
        $this->assertSame($expectedIsActive, $status->isActive());
    }

    public static function activeStatusProvider(): array
    {
        return [
            'new is active' => [LeadStatus::New, true],
            'contacted is active' => [LeadStatus::Contacted, true],
            'qualified is active' => [LeadStatus::Qualified, true],
            'disqualified is not active' => [LeadStatus::Disqualified, false],
            'converted is not active' => [LeadStatus::Converted, false],
        ];
    }

    #[Test]
    #[DataProvider('finalStatusProvider')]
    public function it_identifies_final_statuses_correctly(LeadStatus $status, bool $expectedIsFinal): void
    {
        $this->assertSame($expectedIsFinal, $status->isFinal());
    }

    public static function finalStatusProvider(): array
    {
        return [
            'new is not final' => [LeadStatus::New, false],
            'contacted is not final' => [LeadStatus::Contacted, false],
            'qualified is not final' => [LeadStatus::Qualified, false],
            'disqualified is final' => [LeadStatus::Disqualified, true],
            'converted is final' => [LeadStatus::Converted, true],
        ];
    }

    #[Test]
    #[DataProvider('convertibleStatusProvider')]
    public function it_identifies_convertible_statuses_correctly(LeadStatus $status, bool $expectedIsConvertible): void
    {
        $this->assertSame($expectedIsConvertible, $status->isConvertible());
    }

    public static function convertibleStatusProvider(): array
    {
        return [
            'new is not convertible' => [LeadStatus::New, false],
            'contacted is not convertible' => [LeadStatus::Contacted, false],
            'qualified is convertible' => [LeadStatus::Qualified, true],
            'disqualified is not convertible' => [LeadStatus::Disqualified, false],
            'converted is not convertible' => [LeadStatus::Converted, false],
        ];
    }

    #[Test]
    public function it_returns_correct_valid_transitions_for_new(): void
    {
        $transitions = LeadStatus::New->getValidTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(LeadStatus::Contacted, $transitions);
        $this->assertContains(LeadStatus::Disqualified, $transitions);
    }

    #[Test]
    public function it_returns_correct_valid_transitions_for_contacted(): void
    {
        $transitions = LeadStatus::Contacted->getValidTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(LeadStatus::Qualified, $transitions);
        $this->assertContains(LeadStatus::Disqualified, $transitions);
    }

    #[Test]
    public function it_returns_correct_valid_transitions_for_qualified(): void
    {
        $transitions = LeadStatus::Qualified->getValidTransitions();

        $this->assertCount(2, $transitions);
        $this->assertContains(LeadStatus::Converted, $transitions);
        $this->assertContains(LeadStatus::Disqualified, $transitions);
    }

    #[Test]
    public function it_returns_empty_transitions_for_final_statuses(): void
    {
        $this->assertEmpty(LeadStatus::Disqualified->getValidTransitions());
        $this->assertEmpty(LeadStatus::Converted->getValidTransitions());
    }

    #[Test]
    #[DataProvider('validTransitionProvider')]
    public function it_validates_transitions_correctly(LeadStatus $from, LeadStatus $to, bool $expectedCanTransition): void
    {
        $this->assertSame($expectedCanTransition, $from->canTransitionTo($to));
    }

    public static function validTransitionProvider(): array
    {
        return [
            // Valid transitions from New
            'new to contacted' => [LeadStatus::New, LeadStatus::Contacted, true],
            'new to disqualified' => [LeadStatus::New, LeadStatus::Disqualified, true],
            'new to qualified (invalid)' => [LeadStatus::New, LeadStatus::Qualified, false],
            'new to converted (invalid)' => [LeadStatus::New, LeadStatus::Converted, false],

            // Valid transitions from Contacted
            'contacted to qualified' => [LeadStatus::Contacted, LeadStatus::Qualified, true],
            'contacted to disqualified' => [LeadStatus::Contacted, LeadStatus::Disqualified, true],
            'contacted to new (invalid - backwards)' => [LeadStatus::Contacted, LeadStatus::New, false],
            'contacted to converted (invalid)' => [LeadStatus::Contacted, LeadStatus::Converted, false],

            // Valid transitions from Qualified
            'qualified to converted' => [LeadStatus::Qualified, LeadStatus::Converted, true],
            'qualified to disqualified' => [LeadStatus::Qualified, LeadStatus::Disqualified, true],
            'qualified to new (invalid - backwards)' => [LeadStatus::Qualified, LeadStatus::New, false],
            'qualified to contacted (invalid - backwards)' => [LeadStatus::Qualified, LeadStatus::Contacted, false],

            // No transitions from final states
            'disqualified to new (invalid)' => [LeadStatus::Disqualified, LeadStatus::New, false],
            'disqualified to contacted (invalid)' => [LeadStatus::Disqualified, LeadStatus::Contacted, false],
            'converted to anything (invalid)' => [LeadStatus::Converted, LeadStatus::New, false],
        ];
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $status = LeadStatus::from('qualified');

        $this->assertSame(LeadStatus::Qualified, $status);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string_value(): void
    {
        $this->expectException(\ValueError::class);

        LeadStatus::from('invalid_status');
    }

    #[Test]
    public function it_can_try_from_string_safely(): void
    {
        $status = LeadStatus::tryFrom('qualified');

        $this->assertSame(LeadStatus::Qualified, $status);
    }

    #[Test]
    public function it_returns_null_for_invalid_try_from(): void
    {
        $status = LeadStatus::tryFrom('invalid_status');

        $this->assertNull($status);
    }
}
