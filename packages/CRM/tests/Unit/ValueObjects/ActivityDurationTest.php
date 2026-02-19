<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\ValueObjects;

use Nexus\CRM\ValueObjects\ActivityDuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActivityDurationTest extends TestCase
{
    #[Test]
    public function it_creates_duration_from_minutes(): void
    {
        $duration = new ActivityDuration(90);

        $this->assertSame(90, $duration->getMinutes());
        $this->assertSame(90, $duration->minutes);
    }

    #[Test]
    public function it_throws_exception_for_negative_minutes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration cannot be negative');

        new ActivityDuration(-1);
    }

    #[Test]
    public function it_accepts_zero_minutes(): void
    {
        $duration = new ActivityDuration(0);

        $this->assertSame(0, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_minutes_factory(): void
    {
        $duration = ActivityDuration::fromMinutes(45);

        $this->assertSame(45, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_hours(): void
    {
        $duration = ActivityDuration::fromHours(1.5);

        $this->assertSame(90, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_hours_rounds_correctly(): void
    {
        $duration = ActivityDuration::fromHours(1.25);

        // 1.25 * 60 = 75
        $this->assertSame(75, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_hours_and_minutes(): void
    {
        $duration = ActivityDuration::fromHoursAndMinutes(2, 30);

        $this->assertSame(150, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_hours_and_minutes_with_zero_minutes(): void
    {
        $duration = ActivityDuration::fromHoursAndMinutes(3);

        $this->assertSame(180, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_seconds(): void
    {
        $duration = ActivityDuration::fromSeconds(3600);

        $this->assertSame(60, $duration->getMinutes());
    }

    #[Test]
    public function it_creates_from_seconds_rounds_correctly(): void
    {
        $duration = ActivityDuration::fromSeconds(90);

        // 90 / 60 = 1.5, rounds to 2
        $this->assertSame(2, $duration->getMinutes());
    }

    #[Test]
    public function it_returns_hours_as_decimal(): void
    {
        $duration = new ActivityDuration(90);

        $this->assertSame(1.5, $duration->getHours());
    }

    #[Test]
    public function it_returns_seconds(): void
    {
        $duration = new ActivityDuration(5);

        $this->assertSame(300, $duration->getSeconds());
    }

    #[Test]
    public function it_returns_hours_component(): void
    {
        $duration = new ActivityDuration(150); // 2h 30m

        $this->assertSame(2, $duration->getHoursComponent());
    }

    #[Test]
    public function it_returns_minutes_component(): void
    {
        $duration = new ActivityDuration(150); // 2h 30m

        $this->assertSame(30, $duration->getMinutesComponent());
    }

    #[Test]
    public function it_returns_correct_components_for_exact_hours(): void
    {
        $duration = new ActivityDuration(120); // 2h 0m

        $this->assertSame(2, $duration->getHoursComponent());
        $this->assertSame(0, $duration->getMinutesComponent());
    }

    #[Test]
    public function it_returns_correct_components_for_minutes_only(): void
    {
        $duration = new ActivityDuration(45); // 0h 45m

        $this->assertSame(0, $duration->getHoursComponent());
        $this->assertSame(45, $duration->getMinutesComponent());
    }

    #[Test]
    #[DataProvider('formatProvider')]
    public function it_formats_duration_correctly(int $minutes, string $expectedFormat): void
    {
        $duration = new ActivityDuration($minutes);

        $this->assertSame($expectedFormat, $duration->format());
    }

    public static function formatProvider(): array
    {
        return [
            'zero minutes' => [0, '0m'],
            'minutes only' => [45, '45m'],
            'exact hours' => [120, '2h'],
            'hours and minutes' => [150, '2h 30m'],
            'one hour' => [60, '1h'],
            'large duration' => [495, '8h 15m'],
        ];
    }

    #[Test]
    #[DataProvider('hhmmFormatProvider')]
    public function it_formats_as_hhmm_correctly(int $minutes, string $expectedFormat): void
    {
        $duration = new ActivityDuration($minutes);

        $this->assertSame($expectedFormat, $duration->formatHHMM());
    }

    public static function hhmmFormatProvider(): array
    {
        return [
            'zero minutes' => [0, '00:00'],
            'minutes only' => [45, '00:45'],
            'exact hours' => [120, '02:00'],
            'hours and minutes' => [150, '02:30'],
            'large duration' => [495, '08:15'],
        ];
    }

    #[Test]
    public function it_identifies_zero_duration(): void
    {
        $zero = new ActivityDuration(0);
        $nonZero = new ActivityDuration(1);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    #[Test]
    #[DataProvider('durationCategoryProvider')]
    public function it_categorizes_duration_correctly(int $minutes, bool $isShort, bool $isMedium, bool $isLong): void
    {
        $duration = new ActivityDuration($minutes);

        $this->assertSame($isShort, $duration->isShort());
        $this->assertSame($isMedium, $duration->isMedium());
        $this->assertSame($isLong, $duration->isLong());
    }

    public static function durationCategoryProvider(): array
    {
        return [
            '0 minutes is short' => [0, true, false, false],
            '14 minutes is short' => [14, true, false, false],
            '15 minutes is medium' => [15, false, true, false],
            '30 minutes is medium' => [30, false, true, false],
            '60 minutes is medium' => [60, false, true, false],
            '61 minutes is long' => [61, false, false, true],
            '120 minutes is long' => [120, false, false, true],
        ];
    }

    #[Test]
    public function it_adds_durations(): void
    {
        $duration1 = new ActivityDuration(30);
        $duration2 = new ActivityDuration(45);

        $result = $duration1->add($duration2);

        $this->assertSame(75, $result->getMinutes());
    }

    #[Test]
    public function it_subtracts_durations(): void
    {
        $duration1 = new ActivityDuration(60);
        $duration2 = new ActivityDuration(20);

        $result = $duration1->subtract($duration2);

        $this->assertSame(40, $result->getMinutes());
    }

    #[Test]
    public function it_does_not_go_negative_when_subtracting(): void
    {
        $duration1 = new ActivityDuration(30);
        $duration2 = new ActivityDuration(60);

        $result = $duration1->subtract($duration2);

        $this->assertSame(0, $result->getMinutes());
    }

    #[Test]
    public function it_compares_durations_correctly(): void
    {
        $longer = new ActivityDuration(60);
        $shorter = new ActivityDuration(30);
        $sameAsLonger = new ActivityDuration(60);

        // Longer is longer than shorter
        $this->assertTrue($longer->isLongerThan($shorter));
        // Same duration is not longer than another instance with same value
        $this->assertFalse($longer->isLongerThan($sameAsLonger));
        // Same duration is not longer than itself
        $this->assertFalse($sameAsLonger->isLongerThan($longer));

        // Shorter is shorter than longer
        $this->assertTrue($shorter->isShorterThan($longer));
        // Shorter is shorter than sameAsLonger (which is 60)
        $this->assertTrue($shorter->isShorterThan($sameAsLonger));
        // Same duration is not shorter than another instance with same value
        $this->assertFalse($sameAsLonger->isShorterThan($longer));
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $duration1 = new ActivityDuration(45);
        $duration2 = new ActivityDuration(45);
        $duration3 = new ActivityDuration(30);

        $this->assertTrue($duration1->equals($duration2));
        $this->assertFalse($duration1->equals($duration3));
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $duration = new ActivityDuration(90);

        $this->assertSame('1h 30m', (string) $duration);
        $this->assertSame('1h 30m', $duration->__toString());
    }

    #[Test]
    public function it_is_readonly(): void
    {
        $duration = new ActivityDuration(45);

        $this->assertSame(45, $duration->minutes);
    }

    #[Test]
    public function add_returns_new_instance(): void
    {
        $duration1 = new ActivityDuration(30);
        $duration2 = new ActivityDuration(45);

        $result = $duration1->add($duration2);

        $this->assertNotSame($duration1, $result);
        $this->assertNotSame($duration2, $result);
    }

    #[Test]
    public function subtract_returns_new_instance(): void
    {
        $duration1 = new ActivityDuration(60);
        $duration2 = new ActivityDuration(20);

        $result = $duration1->subtract($duration2);

        $this->assertNotSame($duration1, $result);
        $this->assertNotSame($duration2, $result);
    }

    #[Test]
    public function it_handles_large_durations(): void
    {
        $duration = new ActivityDuration(600); // 10 hours

        $this->assertSame(600, $duration->getMinutes());
        $this->assertSame(10.0, $duration->getHours());
        $this->assertSame(10, $duration->getHoursComponent());
        $this->assertSame(0, $duration->getMinutesComponent());
        $this->assertSame('10h', $duration->format());
        $this->assertSame('10:00', $duration->formatHHMM());
    }

    #[Test]
    public function categories_are_mutually_exclusive(): void
    {
        foreach ([0, 14, 15, 30, 60, 61, 120] as $minutes) {
            $duration = new ActivityDuration($minutes);
            $categories = array_filter([
                $duration->isShort(),
                $duration->isMedium(),
                $duration->isLong(),
            ]);

            $this->assertCount(1, $categories, sprintf(
                'Duration %d minutes should belong to exactly one category',
                $minutes
            ));
        }
    }
}
