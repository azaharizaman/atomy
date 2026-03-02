<?php

declare(strict_types=1);

namespace Nexus\ESGOperations\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\ESGOperations\Services\ScenarioPlanningService;
use Nexus\MachineLearning\Contracts\PredictionServiceInterface;
use Psr\Log\LoggerInterface;

final class ScenarioPlanningServiceTest extends TestCase
{
    private $predictionService;
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->predictionService = $this->createMock(PredictionServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new ScenarioPlanningService($this->predictionService, $this->logger);
    }

    public function test_forecast_returns_projected_values(): void
    {
        $result = $this->service->forecast('carbon_emissions', [], new \DateTimeImmutable('+1 year'));

        $this->assertArrayHasKey('forecasted_value', $result);
        $this->assertGreaterThan(0, $result['forecasted_value']);
        $this->assertSame(0.92, $result['confidence_score']);
    }
}
