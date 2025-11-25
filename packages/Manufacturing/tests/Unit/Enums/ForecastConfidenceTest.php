<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Enums;

use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\Tests\TestCase;

final class ForecastConfidenceTest extends TestCase
{
    public function testAllLevelsExist(): void
    {
        $this->assertCount(5, ForecastConfidence::cases());

        $this->assertSame('high', ForecastConfidence::HIGH->value);
        $this->assertSame('medium', ForecastConfidence::MEDIUM->value);
        $this->assertSame('low', ForecastConfidence::LOW->value);
        $this->assertSame('fallback', ForecastConfidence::FALLBACK->value);
        $this->assertSame('unknown', ForecastConfidence::UNKNOWN->value);
    }

    public function testLabel(): void
    {
        $this->assertSame('High Confidence', ForecastConfidence::HIGH->label());
        $this->assertSame('Medium Confidence', ForecastConfidence::MEDIUM->label());
        $this->assertSame('Low Confidence', ForecastConfidence::LOW->label());
        $this->assertSame('Fallback (Historical)', ForecastConfidence::FALLBACK->label());
        $this->assertSame('Unknown', ForecastConfidence::UNKNOWN->label());
    }

    public function testGetScore(): void
    {
        $this->assertSame(0.9, ForecastConfidence::HIGH->getScore());
        $this->assertSame(0.7, ForecastConfidence::MEDIUM->getScore());
        $this->assertSame(0.5, ForecastConfidence::LOW->getScore());
        $this->assertSame(0.4, ForecastConfidence::FALLBACK->getScore());
        $this->assertSame(0.2, ForecastConfidence::UNKNOWN->getScore());
    }

    public function testGetSafetyStockMultiplier(): void
    {
        $this->assertSame(1.0, ForecastConfidence::HIGH->getSafetyStockMultiplier());
        $this->assertSame(1.2, ForecastConfidence::MEDIUM->getSafetyStockMultiplier());
        $this->assertSame(1.5, ForecastConfidence::LOW->getSafetyStockMultiplier());
        $this->assertSame(1.8, ForecastConfidence::FALLBACK->getSafetyStockMultiplier());
        $this->assertSame(2.0, ForecastConfidence::UNKNOWN->getSafetyStockMultiplier());
    }

    public function testFromScore(): void
    {
        $this->assertSame(ForecastConfidence::HIGH, ForecastConfidence::fromScore(0.90));
        $this->assertSame(ForecastConfidence::HIGH, ForecastConfidence::fromScore(0.85));
        $this->assertSame(ForecastConfidence::MEDIUM, ForecastConfidence::fromScore(0.70));
        $this->assertSame(ForecastConfidence::MEDIUM, ForecastConfidence::fromScore(0.65));
        $this->assertSame(ForecastConfidence::LOW, ForecastConfidence::fromScore(0.50));
        $this->assertSame(ForecastConfidence::FALLBACK, ForecastConfidence::fromScore(0.35));
        $this->assertSame(ForecastConfidence::UNKNOWN, ForecastConfidence::fromScore(0.20));
    }

    public function testRequiresReview(): void
    {
        $this->assertFalse(ForecastConfidence::HIGH->requiresReview());
        $this->assertFalse(ForecastConfidence::MEDIUM->requiresReview());
        $this->assertTrue(ForecastConfidence::LOW->requiresReview());
        $this->assertTrue(ForecastConfidence::FALLBACK->requiresReview());
        $this->assertTrue(ForecastConfidence::UNKNOWN->requiresReview());
    }

    public function testDescription(): void
    {
        $this->assertStringContainsString('ML', ForecastConfidence::HIGH->description());
        $this->assertStringContainsString('Reliable', ForecastConfidence::MEDIUM->description());
        $this->assertStringContainsString('Limited', ForecastConfidence::LOW->description());
        $this->assertStringContainsString('historical', ForecastConfidence::FALLBACK->description());
        $this->assertStringContainsString('Insufficient', ForecastConfidence::UNKNOWN->description());
    }
}
