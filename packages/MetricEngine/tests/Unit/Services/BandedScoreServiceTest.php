<?php

declare(strict_types=1);

namespace Nexus\MetricEngine\Tests\Unit\Services;

use Nexus\MetricEngine\Exceptions\FormulaValidationException;
use Nexus\MetricEngine\Services\BandedScoreService;
use Nexus\MetricEngine\Services\NumericValueService;
use Nexus\MetricEngine\ValueObjects\BandDefinition;
use PHPUnit\Framework\TestCase;

class BandedScoreServiceTest extends TestCase
{
    private BandedScoreService $service;

    protected function setUp(): void
    {
        $this->service = new BandedScoreService(new NumericValueService());
    }

    public function test_matches_caller_supplied_band(): void
    {
        $score = $this->service->score(82, [
            new BandDefinition('low', 0, 49.99),
            new BandDefinition('medium', 50, 79.99),
            new BandDefinition('high', 80, 100),
        ]);

        $this->assertSame(82.0, $score->value);
        $this->assertSame('high', $score->bandLabel);
    }

    public function test_rejects_score_without_matching_band(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Score [120] does not match any supplied band.');

        $this->service->score(120, [
            new BandDefinition('low', 0, 100),
        ]);
    }

    public function test_rejects_empty_band_label(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage('Band label is required.');

        new BandDefinition('', 0, 100);
    }

    public function test_matches_boundary_values(): void
    {
        $score = $this->service->score(50, [
            new BandDefinition('band', 50, 50),
        ]);

        $this->assertSame(50.0, $score->value);
        $this->assertSame('band', $score->bandLabel);
    }

    public function test_rejects_band_with_min_greater_than_max(): void
    {
        $this->expectException(FormulaValidationException::class);
        $this->expectExceptionMessage("Band [bad] minimum must be less than or equal to maximum.");

        $this->service->score(50, [
            new BandDefinition('bad', 100, 50),
        ]);
    }
}
