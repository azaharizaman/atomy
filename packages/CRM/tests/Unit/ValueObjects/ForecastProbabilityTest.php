<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\ValueObjects;

use Nexus\CRM\ValueObjects\ForecastProbability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ForecastProbabilityTest extends TestCase
{
    #[Test]
    public function it_creates_forecast_probability_with_valid_percentage(): void
    {
        $probability = new ForecastProbability(75);

        $this->assertSame(75, $probability->getPercentage());
        $this->assertSame(75, $probability->percentage);
    }

    #[Test]
    public function it_creates_forecast_probability_with_reason(): void
    {
        $probability = new ForecastProbability(80, 'Strong buying signals');

        $this->assertSame(80, $probability->getPercentage());
        $this->assertSame('Strong buying signals', $probability->getReason());
        $this->assertSame('Strong buying signals', $probability->reason);
    }

    #[Test]
    public function it_throws_exception_for_negative_percentage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Probability must be between 0 and 100');

        new ForecastProbability(-1);
    }

    #[Test]
    public function it_throws_exception_for_percentage_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Probability must be between 0 and 100');

        new ForecastProbability(101);
    }

    #[Test]
    public function it_accepts_boundary_values(): void
    {
        $min = new ForecastProbability(0);
        $max = new ForecastProbability(100);

        $this->assertSame(0, $min->getPercentage());
        $this->assertSame(100, $max->getPercentage());
    }

    #[Test]
    public function it_creates_from_decimal(): void
    {
        $probability = ForecastProbability::fromDecimal(0.75);

        $this->assertSame(75, $probability->getPercentage());
    }

    #[Test]
    public function it_creates_from_decimal_with_reason(): void
    {
        $probability = ForecastProbability::fromDecimal(0.5, 'Based on historical data');

        $this->assertSame(50, $probability->getPercentage());
        $this->assertSame('Based on historical data', $probability->getReason());
    }

    #[Test]
    public function it_throws_exception_for_negative_decimal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal probability must be between 0.0 and 1.0');

        ForecastProbability::fromDecimal(-0.1);
    }

    #[Test]
    public function it_throws_exception_for_decimal_above_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal probability must be between 0.0 and 1.0');

        ForecastProbability::fromDecimal(1.1);
    }

    #[Test]
    public function it_accepts_decimal_boundary_values(): void
    {
        $min = ForecastProbability::fromDecimal(0.0);
        $max = ForecastProbability::fromDecimal(1.0);

        $this->assertSame(0, $min->getPercentage());
        $this->assertSame(100, $max->getPercentage());
    }

    #[Test]
    public function it_rounds_decimal_correctly(): void
    {
        $probability = ForecastProbability::fromDecimal(0.755);

        // 0.755 * 100 = 75.5, rounds to 76
        $this->assertSame(76, $probability->getPercentage());
    }

    #[Test]
    public function it_creates_guaranteed_win(): void
    {
        $probability = ForecastProbability::guaranteed();

        $this->assertSame(100, $probability->getPercentage());
        $this->assertSame('Deal closed won', $probability->getReason());
    }

    #[Test]
    public function it_creates_guaranteed_win_with_custom_reason(): void
    {
        $probability = ForecastProbability::guaranteed('Contract signed');

        $this->assertSame(100, $probability->getPercentage());
        $this->assertSame('Contract signed', $probability->getReason());
    }

    #[Test]
    public function it_creates_guaranteed_loss(): void
    {
        $probability = ForecastProbability::lost();

        $this->assertSame(0, $probability->getPercentage());
        $this->assertSame('Deal closed lost', $probability->getReason());
    }

    #[Test]
    public function it_creates_guaranteed_loss_with_custom_reason(): void
    {
        $probability = ForecastProbability::lost('Competitor selected');

        $this->assertSame(0, $probability->getPercentage());
        $this->assertSame('Competitor selected', $probability->getReason());
    }

    #[Test]
    public function it_returns_decimal(): void
    {
        $probability = new ForecastProbability(75);

        $this->assertSame(0.75, $probability->getDecimal());
    }

    #[Test]
    public function it_calculates_weighted_value(): void
    {
        $probability = new ForecastProbability(75);

        $this->assertSame(75000, $probability->calculateWeightedValue(100000));
        $this->assertSame(75, $probability->calculateWeightedValue(100));
    }

    #[Test]
    public function it_calculates_weighted_value_with_zero_probability(): void
    {
        $probability = new ForecastProbability(0);

        $this->assertSame(0, $probability->calculateWeightedValue(100000));
    }

    #[Test]
    public function it_calculates_weighted_value_with_hundred_probability(): void
    {
        $probability = new ForecastProbability(100);

        $this->assertSame(100000, $probability->calculateWeightedValue(100000));
    }

    #[Test]
    public function it_identifies_guaranteed_win(): void
    {
        $guaranteed = new ForecastProbability(100);
        $notGuaranteed = new ForecastProbability(99);

        $this->assertTrue($guaranteed->isGuaranteed());
        $this->assertFalse($notGuaranteed->isGuaranteed());
    }

    #[Test]
    public function it_identifies_guaranteed_loss(): void
    {
        $lost = new ForecastProbability(0);
        $notLost = new ForecastProbability(1);

        $this->assertTrue($lost->isLost());
        $this->assertFalse($notLost->isLost());
    }

    #[Test]
    #[DataProvider('confidenceLevelProvider')]
    public function it_identifies_confidence_levels_correctly(int $percentage, bool $isHigh, bool $isMedium, bool $isLow): void
    {
        $probability = new ForecastProbability($percentage);

        $this->assertSame($isHigh, $probability->isHigh());
        $this->assertSame($isMedium, $probability->isMedium());
        $this->assertSame($isLow, $probability->isLow());
    }

    public static function confidenceLevelProvider(): array
    {
        return [
            '100% is high' => [100, true, false, false],
            '70% is high' => [70, true, false, false],
            '69% is medium' => [69, false, true, false],
            '50% is medium' => [50, false, true, false],
            '40% is medium' => [40, false, true, false],
            '39% is low' => [39, false, false, true],
            '1% is low' => [1, false, false, true],
            '0% is low (but also lost)' => [0, false, false, true],
        ];
    }

    #[Test]
    #[DataProvider('categoryProvider')]
    public function it_returns_correct_categories(int $percentage, string $expectedCategory): void
    {
        $probability = new ForecastProbability($percentage);

        $this->assertSame($expectedCategory, $probability->getCategory());
    }

    public static function categoryProvider(): array
    {
        return [
            '100% is Won' => [100, 'Won'],
            '0% is Lost' => [0, 'Lost'],
            '85% is High Confidence' => [85, 'High Confidence'],
            '70% is High Confidence' => [70, 'High Confidence'],
            '60% is Medium Confidence' => [60, 'Medium Confidence'],
            '40% is Medium Confidence' => [40, 'Medium Confidence'],
            '30% is Low Confidence' => [30, 'Low Confidence'],
            '1% is Low Confidence' => [1, 'Low Confidence'],
        ];
    }

    #[Test]
    public function it_compares_probabilities_correctly(): void
    {
        $high = new ForecastProbability(80);
        $low = new ForecastProbability(30);
        $sameAsHigh = new ForecastProbability(80);

        // High is higher than low
        $this->assertTrue($high->isHigherThan($low));
        // Same probability is not higher than another instance with same value
        $this->assertFalse($high->isHigherThan($sameAsHigh));
        // Same probability is not higher than itself
        $this->assertFalse($sameAsHigh->isHigherThan($high));

        // Low is lower than high
        $this->assertTrue($low->isLowerThan($high));
        // Low is lower than sameAsHigh (which is 80)
        $this->assertTrue($low->isLowerThan($sameAsHigh));
        // Same probability is not lower than another instance with same value
        $this->assertFalse($sameAsHigh->isLowerThan($high));
    }

    #[Test]
    public function it_creates_new_probability_with_updated_percentage(): void
    {
        $original = new ForecastProbability(50, 'Initial assessment');
        $updated = $original->withPercentage(75);

        $this->assertSame(75, $updated->getPercentage());
        $this->assertSame('Initial assessment', $updated->getReason());
    }

    #[Test]
    public function it_creates_new_probability_with_updated_percentage_and_reason(): void
    {
        $original = new ForecastProbability(50, 'Initial assessment');
        $updated = $original->withPercentage(75, 'Updated after meeting');

        $this->assertSame(75, $updated->getPercentage());
        $this->assertSame('Updated after meeting', $updated->getReason());
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $probability = new ForecastProbability(75);

        $this->assertSame('75%', (string) $probability);
        $this->assertSame('75%', $probability->__toString());
    }

    #[Test]
    public function it_converts_boundary_values_to_string(): void
    {
        $zero = new ForecastProbability(0);
        $hundred = new ForecastProbability(100);

        $this->assertSame('0%', (string) $zero);
        $this->assertSame('100%', (string) $hundred);
    }

    #[Test]
    public function it_is_readonly(): void
    {
        $probability = new ForecastProbability(75, 'Test reason');

        $this->assertSame(75, $probability->percentage);
        $this->assertSame('Test reason', $probability->reason);
    }

    #[Test]
    public function it_handles_null_reason(): void
    {
        $probability = new ForecastProbability(50);

        $this->assertNull($probability->getReason());
    }

    #[Test]
    public function weighted_value_rounds_correctly(): void
    {
        $probability = new ForecastProbability(33);

        // 100000 * 0.33 = 33000
        $this->assertSame(33000, $probability->calculateWeightedValue(100000));
    }
}
