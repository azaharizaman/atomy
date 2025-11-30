<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Services;

use Nexus\Manufacturing\Contracts\ForecastFallbackInterface;
use Nexus\Manufacturing\Contracts\ForecastProviderInterface;
use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\Exceptions\ForecastUnavailableException;
use Nexus\Manufacturing\Services\DemandForecaster;
use Nexus\Manufacturing\Tests\TestCase;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class DemandForecasterTest extends TestCase
{
    private ForecastProviderInterface&MockObject $mlProvider;
    private ForecastFallbackInterface&MockObject $fallbackProvider;
    private LoggerInterface&MockObject $logger;
    private \DateTimeImmutable $startDate;
    private \DateTimeImmutable $endDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mlProvider = $this->createMock(ForecastProviderInterface::class);
        $this->fallbackProvider = $this->createMock(ForecastFallbackInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->startDate = new \DateTimeImmutable('2024-01-01');
        $this->endDate = new \DateTimeImmutable('2024-03-31');
    }

    public function testForecastUsesMLProviderWhenAvailable(): void
    {
        $forecast = $this->createForecast('prod-001', 1000.0, 'ml', ForecastConfidence::HIGH);

        $this->mlProvider
            ->expects($this->once())
            ->method('generateForecast')
            ->willReturn($forecast);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $result = $forecaster->forecast('prod-001', $this->startDate, $this->endDate);

        $this->assertSame('prod-001', $result->productId);
        $this->assertSame(1000.0, $result->quantity);
        $this->assertSame('ml', $result->source);
    }

    public function testForecastFallsBackToHistoricalWhenMLFails(): void
    {
        $fallbackForecast = $this->createForecast('prod-001', 800.0, 'historical', ForecastConfidence::MEDIUM);

        $this->mlProvider
            ->expects($this->once())
            ->method('generateForecast')
            ->willThrowException(new \RuntimeException('ML service unavailable'));

        $this->fallbackProvider
            ->expects($this->once())
            ->method('generateFromHistory')
            ->willReturn($fallbackForecast);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $result = $forecaster->forecast('prod-001', $this->startDate, $this->endDate);

        $this->assertSame('prod-001', $result->productId);
        $this->assertSame(800.0, $result->quantity);
        $this->assertSame('historical', $result->source);
    }

    public function testForecastThrowsExceptionWhenNoProviderAvailable(): void
    {
        $forecaster = new DemandForecaster(
            null,
            null,
            $this->logger
        );

        $this->expectException(ForecastUnavailableException::class);

        $forecaster->forecast('prod-001', $this->startDate, $this->endDate);
    }

    public function testForecastThrowsExceptionWhenBothProvidersFail(): void
    {
        $this->mlProvider
            ->method('generateForecast')
            ->willThrowException(new \RuntimeException('ML failed'));

        $this->fallbackProvider
            ->method('generateFromHistory')
            ->willThrowException(new \RuntimeException('Fallback failed'));

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $this->expectException(ForecastUnavailableException::class);

        $forecaster->forecast('prod-001', $this->startDate, $this->endDate);
    }

    public function testForecastMultipleProducts(): void
    {
        $forecast1 = $this->createForecast('prod-001', 1000.0, 'ml', ForecastConfidence::HIGH);
        $forecast2 = $this->createForecast('prod-002', 500.0, 'ml', ForecastConfidence::MEDIUM);

        $this->mlProvider
            ->method('generateForecast')
            ->willReturnOnConsecutiveCalls($forecast1, $forecast2);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $results = $forecaster->forecastMultiple(
            ['prod-001', 'prod-002'],
            $this->startDate,
            $this->endDate
        );

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('prod-001', $results);
        $this->assertArrayHasKey('prod-002', $results);
    }

    public function testIsMlAvailable(): void
    {
        $this->mlProvider
            ->method('isHealthy')
            ->willReturn(true);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $this->assertTrue($forecaster->isMlAvailable());
    }

    public function testIsMlAvailableReturnsFalseWhenNoProvider(): void
    {
        $forecaster = new DemandForecaster(
            null,
            $this->fallbackProvider,
            $this->logger
        );

        $this->assertFalse($forecaster->isMlAvailable());
    }

    public function testIsMlAvailableReturnsFalseWhenUnhealthy(): void
    {
        $this->mlProvider
            ->method('isHealthy')
            ->willReturn(false);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $this->assertFalse($forecaster->isMlAvailable());
    }

    public function testGetConfidenceLevelFromMLProvider(): void
    {
        $this->mlProvider
            ->method('getModelConfidence')
            ->with('prod-001')
            ->willReturn(ForecastConfidence::HIGH);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $result = $forecaster->getConfidenceLevel('prod-001');

        $this->assertSame(ForecastConfidence::HIGH, $result);
    }

    public function testGetConfidenceLevelFromFallbackWhenMLFails(): void
    {
        $this->mlProvider
            ->method('getModelConfidence')
            ->willThrowException(new \RuntimeException('ML unavailable'));

        $this->fallbackProvider
            ->method('getHistoricalConfidence')
            ->with('prod-001')
            ->willReturn(ForecastConfidence::MEDIUM);

        $forecaster = new DemandForecaster(
            $this->mlProvider,
            $this->fallbackProvider,
            $this->logger
        );

        $result = $forecaster->getConfidenceLevel('prod-001');

        $this->assertSame(ForecastConfidence::MEDIUM, $result);
    }

    public function testCalculateSeasonalFactors(): void
    {
        $seasonalFactors = [
            1 => 0.8, 2 => 0.9, 3 => 1.0, 4 => 1.0,
            5 => 1.1, 6 => 1.2, 7 => 1.3, 8 => 1.2,
            9 => 1.1, 10 => 1.0, 11 => 0.9, 12 => 0.8,
        ];

        $this->fallbackProvider
            ->expects($this->once())
            ->method('calculateSeasonality')
            ->with('prod-001', 12)
            ->willReturn($seasonalFactors);

        $forecaster = new DemandForecaster(
            null,
            $this->fallbackProvider,
            $this->logger
        );

        $result = $forecaster->calculateSeasonalFactors('prod-001');

        $this->assertCount(12, $result);
        $this->assertSame(0.8, $result[1]); // January
        $this->assertSame(1.3, $result[7]); // July
    }

    /**
     * Create a mock demand forecast.
     */
    private function createForecast(
        string $productId,
        float $quantity,
        string $source,
        ForecastConfidence $confidence
    ): DemandForecast {
        return new DemandForecast(
            productId: $productId,
            startDate: $this->startDate,
            endDate: $this->endDate,
            quantity: $quantity,
            confidence: $confidence,
            source: $source,
            periodBreakdown: [
                '2024-01' => $quantity / 3,
                '2024-02' => $quantity / 3,
                '2024-03' => $quantity / 3,
            ],
            calculatedAt: new \DateTimeImmutable(),
        );
    }
}
