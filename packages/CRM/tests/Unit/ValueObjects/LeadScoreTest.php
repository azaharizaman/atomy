<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\ValueObjects;

use Nexus\CRM\ValueObjects\LeadScore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadScoreTest extends TestCase
{
    #[Test]
    public function it_creates_lead_score_with_valid_value(): void
    {
        $score = new LeadScore(75);

        $this->assertSame(75, $score->getValue());
        $this->assertSame(75, $score->value);
    }

    #[Test]
    public function it_creates_lead_score_with_factors(): void
    {
        $factors = [
            'engagement' => 30,
            'fit' => 25,
            'timing' => 20,
        ];

        $score = new LeadScore(75, $factors);

        $this->assertSame($factors, $score->getFactors());
        $this->assertSame($factors, $score->factors);
    }

    #[Test]
    public function it_creates_lead_score_with_calculated_at(): void
    {
        $calculatedAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $score = new LeadScore(50, [], $calculatedAt);

        $this->assertSame($calculatedAt, $score->getCalculatedAt());
        $this->assertSame($calculatedAt, $score->calculatedAt);
    }

    #[Test]
    public function it_throws_exception_for_negative_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lead score must be between 0 and 100');

        new LeadScore(-1);
    }

    #[Test]
    public function it_throws_exception_for_score_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lead score must be between 0 and 100');

        new LeadScore(101);
    }

    #[Test]
    public function it_accepts_boundary_values(): void
    {
        $minScore = new LeadScore(0);
        $maxScore = new LeadScore(100);

        $this->assertSame(0, $minScore->getValue());
        $this->assertSame(100, $maxScore->getValue());
    }

    #[Test]
    public function it_creates_from_factors(): void
    {
        $factors = [
            'engagement' => 30,
            'fit' => 25,
            'timing' => 20,
        ];

        $score = LeadScore::fromFactors($factors);

        $this->assertSame(75, $score->getValue());
        $this->assertSame($factors, $score->getFactors());
    }

    #[Test]
    public function it_caps_total_score_at_100(): void
    {
        $factors = [
            'engagement' => 50,
            'fit' => 40,
            'timing' => 30,
        ];

        $score = LeadScore::fromFactors($factors);

        $this->assertSame(100, $score->getValue());
    }

    #[Test]
    public function it_does_not_allow_negative_total_from_factors(): void
    {
        $factors = [
            'positive' => 10,
            'negative' => -20,
        ];

        $score = LeadScore::fromFactors($factors);

        $this->assertSame(0, $score->getValue());
    }

    #[Test]
    #[DataProvider('qualityTierProvider')]
    public function it_identifies_quality_tiers_correctly(int $value, string $expectedTier, bool $isHigh, bool $isMedium, bool $isLow): void
    {
        $score = new LeadScore($value);

        $this->assertSame($expectedTier, $score->getQualityTier());
        $this->assertSame($isHigh, $score->isHighQuality());
        $this->assertSame($isMedium, $score->isMediumQuality());
        $this->assertSame($isLow, $score->isLowQuality());
    }

    public static function qualityTierProvider(): array
    {
        return [
            'score 100 is high' => [100, 'High', true, false, false],
            'score 70 is high' => [70, 'High', true, false, false],
            'score 69 is medium' => [69, 'Medium', false, true, false],
            'score 50 is medium' => [50, 'Medium', false, true, false],
            'score 40 is medium' => [40, 'Medium', false, true, false],
            'score 39 is low' => [39, 'Low', false, false, true],
            'score 0 is low' => [0, 'Low', false, false, true],
        ];
    }

    #[Test]
    public function it_gets_specific_factor(): void
    {
        $factors = [
            'engagement' => 30,
            'fit' => 25,
        ];

        $score = new LeadScore(55, $factors);

        $this->assertSame(30, $score->getFactor('engagement'));
        $this->assertSame(25, $score->getFactor('fit'));
        $this->assertNull($score->getFactor('nonexistent'));
    }

    #[Test]
    public function it_checks_if_recalculation_is_needed(): void
    {
        $oldScore = new LeadScore(
            50,
            [],
            new \DateTimeImmutable('-25 hours')
        );

        $recentScore = new LeadScore(
            50,
            [],
            new \DateTimeImmutable('-12 hours')
        );

        $this->assertTrue($oldScore->needsRecalculation(24));
        $this->assertFalse($recentScore->needsRecalculation(24));
    }

    #[Test]
    public function it_checks_if_recalculation_is_needed_with_custom_max_age(): void
    {
        $score = new LeadScore(
            50,
            [],
            new \DateTimeImmutable('-5 hours')
        );

        $this->assertFalse($score->needsRecalculation(24));
        $this->assertTrue($score->needsRecalculation(4));
    }

    #[Test]
    public function it_compares_scores_correctly(): void
    {
        $highScore = new LeadScore(80);
        $lowScore = new LeadScore(30);
        $sameAsHigh = new LeadScore(80);

        // High is higher than low
        $this->assertTrue($highScore->isHigherThan($lowScore));
        // Same score is not higher than another instance with same value
        $this->assertFalse($highScore->isHigherThan($sameAsHigh));
        // Same score is not higher than itself
        $this->assertFalse($sameAsHigh->isHigherThan($highScore));

        // Low is lower than high
        $this->assertTrue($lowScore->isLowerThan($highScore));
        // Low is lower than sameAsHigh (which is 80)
        $this->assertTrue($lowScore->isLowerThan($sameAsHigh));
        // Same score is not lower than another instance with same value
        $this->assertFalse($sameAsHigh->isLowerThan($highScore));
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $score = new LeadScore(75);

        $this->assertSame('75', (string) $score);
        $this->assertSame('75', $score->__toString());
    }

    #[Test]
    public function it_is_readonly(): void
    {
        $score = new LeadScore(50);

        // Verify readonly properties by checking they exist
        $this->assertSame(50, $score->value);
        $this->assertSame([], $score->factors);
        $this->assertInstanceOf(\DateTimeImmutable::class, $score->calculatedAt);
    }

    #[Test]
    public function it_handles_empty_factors(): void
    {
        $score = new LeadScore(50, []);

        $this->assertSame([], $score->getFactors());
        $this->assertNull($score->getFactor('any_factor'));
    }

    #[Test]
    public function it_defaults_to_empty_factors(): void
    {
        $score = new LeadScore(50);

        $this->assertSame([], $score->getFactors());
    }

    #[Test]
    public function it_defaults_to_current_time_for_calculated_at(): void
    {
        $beforeCreation = new \DateTimeImmutable();
        $score = new LeadScore(50);
        $afterCreation = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $score->getCalculatedAt());
        $this->assertLessThanOrEqual($afterCreation, $score->getCalculatedAt());
    }

    #[Test]
    public function from_factors_creates_score_with_current_timestamp(): void
    {
        $beforeCreation = new \DateTimeImmutable();
        $score = LeadScore::fromFactors(['engagement' => 50]);
        $afterCreation = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($beforeCreation, $score->getCalculatedAt());
        $this->assertLessThanOrEqual($afterCreation, $score->getCalculatedAt());
    }
}
