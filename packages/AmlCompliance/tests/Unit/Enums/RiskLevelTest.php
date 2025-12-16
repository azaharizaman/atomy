<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Enums;

use Nexus\AmlCompliance\Enums\RiskLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RiskLevel::class)]
final class RiskLevelTest extends TestCase
{
    public function test_low_has_correct_value(): void
    {
        $this->assertSame('low', RiskLevel::LOW->value);
    }

    public function test_medium_has_correct_value(): void
    {
        $this->assertSame('medium', RiskLevel::MEDIUM->value);
    }

    public function test_high_has_correct_value(): void
    {
        $this->assertSame('high', RiskLevel::HIGH->value);
    }

    public function test_all_cases_exist(): void
    {
        $cases = RiskLevel::cases();
        $this->assertCount(3, $cases);
    }

    #[DataProvider('scoreToLevelProvider')]
    public function test_from_score_returns_correct_level(int $score, RiskLevel $expected): void
    {
        $this->assertSame($expected, RiskLevel::fromScore($score));
    }

    public static function scoreToLevelProvider(): array
    {
        return [
            'score 0' => [0, RiskLevel::LOW],
            'score 39' => [39, RiskLevel::LOW],
            'score 40' => [40, RiskLevel::MEDIUM],
            'score 69' => [69, RiskLevel::MEDIUM],
            'score 70' => [70, RiskLevel::HIGH],
            'score 100' => [100, RiskLevel::HIGH],
            'negative score' => [-10, RiskLevel::LOW],
        ];
    }

    public function test_get_min_score(): void
    {
        $this->assertSame(0, RiskLevel::LOW->getMinScore());
        $this->assertSame(40, RiskLevel::MEDIUM->getMinScore());
        $this->assertSame(70, RiskLevel::HIGH->getMinScore());
    }

    public function test_get_max_score(): void
    {
        $this->assertSame(39, RiskLevel::LOW->getMaxScore());
        $this->assertSame(69, RiskLevel::MEDIUM->getMaxScore());
        $this->assertSame(100, RiskLevel::HIGH->getMaxScore());
    }

    public function test_requires_edd(): void
    {
        $this->assertFalse(RiskLevel::LOW->requiresEdd());
        $this->assertFalse(RiskLevel::MEDIUM->requiresEdd());
        $this->assertTrue(RiskLevel::HIGH->requiresEdd());
    }

    public function test_requires_enhanced_monitoring(): void
    {
        $this->assertFalse(RiskLevel::LOW->requiresEnhancedMonitoring());
        $this->assertTrue(RiskLevel::MEDIUM->requiresEnhancedMonitoring());
        $this->assertTrue(RiskLevel::HIGH->requiresEnhancedMonitoring());
    }

    public function test_get_review_frequency_days(): void
    {
        $this->assertIsInt(RiskLevel::LOW->getReviewFrequencyDays());
        $this->assertIsInt(RiskLevel::MEDIUM->getReviewFrequencyDays());
        $this->assertIsInt(RiskLevel::HIGH->getReviewFrequencyDays());
        // High risk should be reviewed more frequently
        $this->assertLessThan(
            RiskLevel::LOW->getReviewFrequencyDays(),
            RiskLevel::HIGH->getReviewFrequencyDays()
        );
    }

    public function test_get_description(): void
    {
        $this->assertIsString(RiskLevel::LOW->getDescription());
        $this->assertNotEmpty(RiskLevel::LOW->getDescription());
    }

    public function test_get_severity_weight(): void
    {
        $lowWeight = RiskLevel::LOW->getSeverityWeight();
        $mediumWeight = RiskLevel::MEDIUM->getSeverityWeight();
        $highWeight = RiskLevel::HIGH->getSeverityWeight();

        $this->assertLessThan($mediumWeight, $lowWeight);
        $this->assertLessThan($highWeight, $mediumWeight);
    }

    public function test_is_higher_than(): void
    {
        $this->assertFalse(RiskLevel::LOW->isHigherThan(RiskLevel::MEDIUM));
        $this->assertTrue(RiskLevel::HIGH->isHigherThan(RiskLevel::LOW));
        $this->assertFalse(RiskLevel::HIGH->isHigherThan(RiskLevel::HIGH));
    }

    public function test_is_at_least(): void
    {
        $this->assertTrue(RiskLevel::HIGH->isAtLeast(RiskLevel::LOW));
        $this->assertTrue(RiskLevel::MEDIUM->isAtLeast(RiskLevel::MEDIUM));
        $this->assertFalse(RiskLevel::LOW->isAtLeast(RiskLevel::HIGH));
    }

    public function test_ascending_returns_ordered_array(): void
    {
        $ascending = RiskLevel::ascending();
        $this->assertIsArray($ascending);
        $this->assertCount(3, $ascending);
        $this->assertSame(RiskLevel::LOW, $ascending[0]);
    }
}
