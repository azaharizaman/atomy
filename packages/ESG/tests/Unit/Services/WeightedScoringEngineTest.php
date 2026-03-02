<?php

declare(strict_types=1);

namespace Nexus\ESG\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\ESG\Services\WeightedScoringEngine;
use Nexus\ESG\Strategies\DefaultWeightingStrategy;
use Nexus\ESG\ValueObjects\SustainabilityScore;

final class WeightedScoringEngineTest extends TestCase
{
    private $engine;

    protected function setUp(): void
    {
        $this->engine = new WeightedScoringEngine(new DefaultWeightingStrategy());
    }

    public function test_calculates_composite_score_with_custom_weights(): void
    {
        $scores = [
            'environmental' => 80.0,
            'social' => 70.0,
            'governance' => 90.0,
        ];

        $weights = [
            'environmental' => 0.5,
            'social' => 0.3,
            'governance' => 0.2,
        ];

        $result = $this->engine->calculateCompositeScore($scores, $weights);

        // (80 * 0.5) + (70 * 0.3) + (90 * 0.2) = 40 + 21 + 18 = 79
        $this->assertEquals(79.0, $result->value);
        $this->assertSame('B', $result->getGrade());
    }

    public function test_uses_default_weights_if_none_provided(): void
    {
        $scores = [
            'environmental' => 100.0,
            'social' => 100.0,
            'governance' => 100.0,
        ];

        $result = $this->engine->calculateCompositeScore($scores);

        $this->assertEquals(100.0, $result->value);
    }
}
