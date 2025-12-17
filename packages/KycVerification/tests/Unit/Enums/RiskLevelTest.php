<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Enums;

use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\Enums\RiskLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RiskLevel::class)]
final class RiskLevelTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $expectedCases = ['LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH', 'PROHIBITED'];

        $actualCases = array_map(fn(RiskLevel $case) => $case->name, RiskLevel::cases());

        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[Test]
    public function it_returns_score_thresholds(): void
    {
        $this->assertSame(0, RiskLevel::LOW->scoreThreshold());
        $this->assertSame(30, RiskLevel::MEDIUM->scoreThreshold());
        $this->assertSame(60, RiskLevel::HIGH->scoreThreshold());
        $this->assertSame(80, RiskLevel::VERY_HIGH->scoreThreshold());
        $this->assertSame(95, RiskLevel::PROHIBITED->scoreThreshold());
    }

    #[Test]
    #[DataProvider('scoreToRiskLevelProvider')]
    public function it_creates_risk_level_from_score(int $score, RiskLevel $expected): void
    {
        $this->assertSame($expected, RiskLevel::fromScore($score));
    }

    public static function scoreToRiskLevelProvider(): array
    {
        return [
            'score 0 is low' => [0, RiskLevel::LOW],
            'score 29 is low' => [29, RiskLevel::LOW],
            'score 30 is medium' => [30, RiskLevel::MEDIUM],
            'score 59 is medium' => [59, RiskLevel::MEDIUM],
            'score 60 is high' => [60, RiskLevel::HIGH],
            'score 79 is high' => [79, RiskLevel::HIGH],
            'score 80 is very high' => [80, RiskLevel::VERY_HIGH],
            'score 94 is very high' => [94, RiskLevel::VERY_HIGH],
            'score 95 is prohibited' => [95, RiskLevel::PROHIBITED],
            'score 100 is prohibited' => [100, RiskLevel::PROHIBITED],
        ];
    }

    #[Test]
    #[DataProvider('eddRequirementProvider')]
    public function it_identifies_edd_requirements(RiskLevel $level, bool $expected): void
    {
        $this->assertSame($expected, $level->requiresEdd());
    }

    public static function eddRequirementProvider(): array
    {
        return [
            'low does not require EDD' => [RiskLevel::LOW, false],
            'medium does not require EDD' => [RiskLevel::MEDIUM, false],
            'high requires EDD' => [RiskLevel::HIGH, true],
            'very high requires EDD' => [RiskLevel::VERY_HIGH, true],
            'prohibited requires EDD' => [RiskLevel::PROHIBITED, true],
        ];
    }

    #[Test]
    #[DataProvider('seniorApprovalProvider')]
    public function it_identifies_senior_approval_requirements(RiskLevel $level, bool $expected): void
    {
        $this->assertSame($expected, $level->requiresSeniorApproval());
    }

    public static function seniorApprovalProvider(): array
    {
        return [
            'low does not need senior approval' => [RiskLevel::LOW, false],
            'medium does not need senior approval' => [RiskLevel::MEDIUM, false],
            'high does not need senior approval' => [RiskLevel::HIGH, false],
            'very high needs senior approval' => [RiskLevel::VERY_HIGH, true],
            'prohibited needs senior approval' => [RiskLevel::PROHIBITED, true],
        ];
    }

    #[Test]
    public function it_identifies_transaction_blocking(): void
    {
        $this->assertFalse(RiskLevel::LOW->blocksTransactions());
        $this->assertFalse(RiskLevel::HIGH->blocksTransactions());
        $this->assertTrue(RiskLevel::PROHIBITED->blocksTransactions());
    }

    #[Test]
    public function it_returns_review_frequency_days(): void
    {
        $this->assertSame(365, RiskLevel::LOW->reviewFrequencyDays());
        $this->assertSame(180, RiskLevel::MEDIUM->reviewFrequencyDays());
        $this->assertSame(90, RiskLevel::HIGH->reviewFrequencyDays());
        $this->assertSame(30, RiskLevel::VERY_HIGH->reviewFrequencyDays());
        $this->assertSame(0, RiskLevel::PROHIBITED->reviewFrequencyDays());
    }

    #[Test]
    public function it_returns_due_diligence_level(): void
    {
        $this->assertSame(DueDiligenceLevel::SIMPLIFIED, RiskLevel::LOW->dueDiligenceLevel());
        $this->assertSame(DueDiligenceLevel::STANDARD, RiskLevel::MEDIUM->dueDiligenceLevel());
        $this->assertSame(DueDiligenceLevel::ENHANCED, RiskLevel::HIGH->dueDiligenceLevel());
        $this->assertSame(DueDiligenceLevel::ENHANCED, RiskLevel::VERY_HIGH->dueDiligenceLevel());
    }

    #[Test]
    public function it_returns_human_readable_label(): void
    {
        $this->assertSame('Low Risk', RiskLevel::LOW->label());
        $this->assertSame('Medium Risk', RiskLevel::MEDIUM->label());
        $this->assertSame('High Risk', RiskLevel::HIGH->label());
        $this->assertSame('Very High Risk', RiskLevel::VERY_HIGH->label());
        $this->assertSame('Prohibited', RiskLevel::PROHIBITED->label());
    }

    #[Test]
    public function it_is_backed_by_string_values(): void
    {
        $this->assertSame('low', RiskLevel::LOW->value);
        $this->assertSame('medium', RiskLevel::MEDIUM->value);
        $this->assertSame('high', RiskLevel::HIGH->value);
        $this->assertSame('very_high', RiskLevel::VERY_HIGH->value);
        $this->assertSame('prohibited', RiskLevel::PROHIBITED->value);
    }
}
