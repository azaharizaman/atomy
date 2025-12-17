<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Enums;

use Nexus\KycVerification\Enums\ReviewTrigger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReviewTrigger::class)]
final class ReviewTriggerTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $expectedCases = [
            'SCHEDULED', 'RISK_CHANGE', 'DOCUMENT_EXPIRED', 'SUSPICIOUS_ACTIVITY',
            'REGULATORY', 'MANUAL_REQUEST', 'INFORMATION_CHANGE', 'TRANSACTION_THRESHOLD',
            'ADVERSE_MEDIA', 'SANCTIONS_UPDATE', 'UBO_CHANGE', 'NEW_JURISDICTION', 'ONBOARDING',
        ];

        $actualCases = array_map(fn(ReviewTrigger $case) => $case->name, ReviewTrigger::cases());

        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[Test]
    #[DataProvider('urgentTriggersProvider')]
    public function it_identifies_urgent_triggers(ReviewTrigger $trigger, bool $expected): void
    {
        $this->assertSame($expected, $trigger->isUrgent());
    }

    public static function urgentTriggersProvider(): array
    {
        return [
            'suspicious activity is urgent' => [ReviewTrigger::SUSPICIOUS_ACTIVITY, true],
            'sanctions update is urgent' => [ReviewTrigger::SANCTIONS_UPDATE, true],
            'adverse media is urgent' => [ReviewTrigger::ADVERSE_MEDIA, true],
            'scheduled is not urgent' => [ReviewTrigger::SCHEDULED, false],
            'document expired is not urgent' => [ReviewTrigger::DOCUMENT_EXPIRED, false],
        ];
    }

    #[Test]
    #[DataProvider('priorityProvider')]
    public function it_returns_priority_levels(ReviewTrigger $trigger, int $expectedPriority): void
    {
        $this->assertSame($expectedPriority, $trigger->priority());
    }

    public static function priorityProvider(): array
    {
        return [
            'suspicious activity is priority 1' => [ReviewTrigger::SUSPICIOUS_ACTIVITY, 1],
            'sanctions update is priority 1' => [ReviewTrigger::SANCTIONS_UPDATE, 1],
            'adverse media is priority 2' => [ReviewTrigger::ADVERSE_MEDIA, 2],
            'regulatory is priority 2' => [ReviewTrigger::REGULATORY, 2],
            'document expired is priority 3' => [ReviewTrigger::DOCUMENT_EXPIRED, 3],
            'risk change is priority 3' => [ReviewTrigger::RISK_CHANGE, 3],
            'information change is priority 4' => [ReviewTrigger::INFORMATION_CHANGE, 4],
            'scheduled is priority 5' => [ReviewTrigger::SCHEDULED, 5],
            'onboarding is priority 5' => [ReviewTrigger::ONBOARDING, 5],
        ];
    }

    #[Test]
    #[DataProvider('slaProvider')]
    public function it_returns_sla_hours(ReviewTrigger $trigger, int $expectedSla): void
    {
        $this->assertSame($expectedSla, $trigger->slaHours());
    }

    public static function slaProvider(): array
    {
        return [
            'suspicious activity is 4 hours' => [ReviewTrigger::SUSPICIOUS_ACTIVITY, 4],
            'sanctions update is 24 hours' => [ReviewTrigger::SANCTIONS_UPDATE, 24],
            'adverse media is 48 hours' => [ReviewTrigger::ADVERSE_MEDIA, 48],
            'regulatory is 72 hours' => [ReviewTrigger::REGULATORY, 72],
            'document expired is 7 days' => [ReviewTrigger::DOCUMENT_EXPIRED, 168],
            'scheduled is 14 days' => [ReviewTrigger::SCHEDULED, 336],
        ];
    }

    #[Test]
    #[DataProvider('automationProvider')]
    public function it_identifies_automatable_triggers(ReviewTrigger $trigger, bool $expected): void
    {
        $this->assertSame($expected, $trigger->canAutomate());
    }

    public static function automationProvider(): array
    {
        return [
            'scheduled can automate' => [ReviewTrigger::SCHEDULED, true],
            'document expired can automate' => [ReviewTrigger::DOCUMENT_EXPIRED, true],
            'transaction threshold can automate' => [ReviewTrigger::TRANSACTION_THRESHOLD, true],
            'suspicious activity cannot automate' => [ReviewTrigger::SUSPICIOUS_ACTIVITY, false],
            'regulatory cannot automate' => [ReviewTrigger::REGULATORY, false],
        ];
    }

    #[Test]
    public function it_is_backed_by_string_values(): void
    {
        $this->assertSame('scheduled', ReviewTrigger::SCHEDULED->value);
        $this->assertSame('risk_change', ReviewTrigger::RISK_CHANGE->value);
        $this->assertSame('suspicious_activity', ReviewTrigger::SUSPICIOUS_ACTIVITY->value);
    }
}
