<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Enums;

use Nexus\CRM\Enums\ActivityType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActivityTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_required_cases(): void
    {
        $cases = ActivityType::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(ActivityType::Call, $cases);
        $this->assertContains(ActivityType::Email, $cases);
        $this->assertContains(ActivityType::Meeting, $cases);
        $this->assertContains(ActivityType::Task, $cases);
        $this->assertContains(ActivityType::Note, $cases);
    }

    #[Test]
    public function it_has_correct_string_values(): void
    {
        $this->assertSame('call', ActivityType::Call->value);
        $this->assertSame('email', ActivityType::Email->value);
        $this->assertSame('meeting', ActivityType::Meeting->value);
        $this->assertSame('task', ActivityType::Task->value);
        $this->assertSame('note', ActivityType::Note->value);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Phone Call', ActivityType::Call->label());
        $this->assertSame('Email', ActivityType::Email->label());
        $this->assertSame('Meeting', ActivityType::Meeting->label());
        $this->assertSame('Task', ActivityType::Task->label());
        $this->assertSame('Note', ActivityType::Note->label());
    }

    #[Test]
    #[DataProvider('schedulingRequiredProvider')]
    public function it_identifies_activities_requiring_scheduling(ActivityType $type, bool $expectedRequiresScheduling): void
    {
        $this->assertSame($expectedRequiresScheduling, $type->requiresScheduling());
    }

    public static function schedulingRequiredProvider(): array
    {
        return [
            'call requires scheduling' => [ActivityType::Call, true],
            'meeting requires scheduling' => [ActivityType::Meeting, true],
            'task requires scheduling' => [ActivityType::Task, true],
            'email does not require scheduling' => [ActivityType::Email, false],
            'note does not require scheduling' => [ActivityType::Note, false],
        ];
    }

    #[Test]
    #[DataProvider('hasDurationProvider')]
    public function it_identifies_activities_with_duration(ActivityType $type, bool $expectedHasDuration): void
    {
        $this->assertSame($expectedHasDuration, $type->hasDuration());
    }

    public static function hasDurationProvider(): array
    {
        return [
            'call has duration' => [ActivityType::Call, true],
            'meeting has duration' => [ActivityType::Meeting, true],
            'task does not have duration' => [ActivityType::Task, false],
            'email does not have duration' => [ActivityType::Email, false],
            'note does not have duration' => [ActivityType::Note, false],
        ];
    }

    #[Test]
    #[DataProvider('communicationProvider')]
    public function it_identifies_communication_activities(ActivityType $type, bool $expectedIsCommunication): void
    {
        $this->assertSame($expectedIsCommunication, $type->isCommunication());
    }

    public static function communicationProvider(): array
    {
        return [
            'call is communication' => [ActivityType::Call, true],
            'email is communication' => [ActivityType::Email, true],
            'meeting is communication' => [ActivityType::Meeting, true],
            'task is not communication' => [ActivityType::Task, false],
            'note is not communication' => [ActivityType::Note, false],
        ];
    }

    #[Test]
    public function it_identifies_standalone_activity(): void
    {
        $this->assertTrue(ActivityType::Note->isStandalone());
        $this->assertFalse(ActivityType::Call->isStandalone());
        $this->assertFalse(ActivityType::Email->isStandalone());
        $this->assertFalse(ActivityType::Meeting->isStandalone());
        $this->assertFalse(ActivityType::Task->isStandalone());
    }

    #[Test]
    #[DataProvider('defaultDurationProvider')]
    public function it_returns_correct_default_durations(ActivityType $type, ?int $expectedDuration): void
    {
        $this->assertSame($expectedDuration, $type->getDefaultDurationMinutes());
    }

    public static function defaultDurationProvider(): array
    {
        return [
            'call default is 15 minutes' => [ActivityType::Call, 15],
            'meeting default is 60 minutes' => [ActivityType::Meeting, 60],
            'task default is 30 minutes' => [ActivityType::Task, 30],
            'email has no default duration' => [ActivityType::Email, null],
            'note has no default duration' => [ActivityType::Note, null],
        ];
    }

    #[Test]
    public function it_returns_correct_icons(): void
    {
        $this->assertSame('phone', ActivityType::Call->getIcon());
        $this->assertSame('envelope', ActivityType::Email->getIcon());
        $this->assertSame('calendar', ActivityType::Meeting->getIcon());
        $this->assertSame('check-square', ActivityType::Task->getIcon());
        $this->assertSame('file-text', ActivityType::Note->getIcon());
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $type = ActivityType::from('meeting');

        $this->assertSame(ActivityType::Meeting, $type);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string_value(): void
    {
        $this->expectException(\ValueError::class);

        ActivityType::from('invalid_type');
    }

    #[Test]
    public function it_can_try_from_string_safely(): void
    {
        $type = ActivityType::tryFrom('email');

        $this->assertSame(ActivityType::Email, $type);
    }

    #[Test]
    public function it_returns_null_for_invalid_try_from(): void
    {
        $type = ActivityType::tryFrom('invalid_type');

        $this->assertNull($type);
    }

    #[Test]
    public function activities_with_duration_also_require_scheduling(): void
    {
        foreach (ActivityType::cases() as $type) {
            if ($type->hasDuration()) {
                $this->assertTrue(
                    $type->requiresScheduling(),
                    sprintf('%s has duration but does not require scheduling', $type->name)
                );
            }
        }
    }

    #[Test]
    public function communication_activities_include_call_email_and_meeting(): void
    {
        $communicationTypes = [
            ActivityType::Call,
            ActivityType::Email,
            ActivityType::Meeting,
        ];

        foreach ($communicationTypes as $type) {
            $this->assertTrue(
                $type->isCommunication(),
                sprintf('%s should be a communication type', $type->name)
            );
        }
    }
}
