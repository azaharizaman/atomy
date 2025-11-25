<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\ValueObjects;

use Nexus\Manufacturing\Enums\ForecastConfidence;
use Nexus\Manufacturing\ValueObjects\DemandForecast;
use Nexus\Manufacturing\Tests\TestCase;

final class DemandForecastTest extends TestCase
{
    public function testCreateDemandForecast(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-03-31');
        $calculatedAt = new \DateTimeImmutable();

        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: $startDate,
            endDate: $endDate,
            quantity: 300.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
            modelVersion: 'v1.2.0',
            calculatedAt: $calculatedAt,
        );

        $this->assertSame('prod-001', $forecast->productId);
        $this->assertSame($startDate, $forecast->startDate);
        $this->assertSame($endDate, $forecast->endDate);
        $this->assertSame(300.0, $forecast->quantity);
        $this->assertSame(ForecastConfidence::HIGH, $forecast->confidence);
        $this->assertSame('ml', $forecast->source);
        $this->assertSame('v1.2.0', $forecast->modelVersion);
    }

    public function testIsMlBased(): void
    {
        $mlForecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );

        $historicalForecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::FALLBACK,
            source: 'historical',
        );

        $this->assertTrue($mlForecast->isMlBased());
        $this->assertFalse($historicalForecast->isMlBased());
    }

    public function testIsFallback(): void
    {
        $mlForecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );

        $historicalForecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::FALLBACK,
            source: 'historical',
        );

        $this->assertFalse($mlForecast->isFallback());
        $this->assertTrue($historicalForecast->isFallback());
    }

    public function testIsHighConfidence(): void
    {
        $highConfidence = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );

        $lowConfidence = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::LOW,
            source: 'historical',
        );

        $this->assertTrue($highConfidence->isHighConfidence());
        $this->assertFalse($lowConfidence->isHighConfidence());
    }

    public function testNeedsReview(): void
    {
        $reliable = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );

        $unreliable = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::UNKNOWN,
            source: 'historical',
        );

        $this->assertFalse($reliable->needsReview());
        $this->assertTrue($unreliable->needsReview());
    }

    public function testGetHorizonDays(): void
    {
        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::MEDIUM,
            source: 'ml',
        );

        $this->assertSame(30, $forecast->getHorizonDays());
    }

    public function testGetDailyAverage(): void
    {
        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            quantity: 300.0,
            confidence: ForecastConfidence::MEDIUM,
            source: 'ml',
        );

        $this->assertSame(10.0, $forecast->getDailyAverage());
    }

    public function testGetWeeklyAverage(): void
    {
        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
            quantity: 300.0,
            confidence: ForecastConfidence::MEDIUM,
            source: 'ml',
        );

        // Daily = 10.0, Weekly = 70.0
        $this->assertSame(70.0, $forecast->getWeeklyAverage());
    }

    public function testGetSafetyStockMultiplier(): void
    {
        $highConfidence = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );

        $lowConfidence = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::LOW,
            source: 'historical',
        );

        $this->assertSame(1.0, $highConfidence->getSafetyStockMultiplier());
        $this->assertSame(1.5, $lowConfidence->getSafetyStockMultiplier());
    }

    public function testGetConfidenceRange(): void
    {
        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
            lowerBound: 80.0,
            upperBound: 120.0,
        );

        $this->assertSame(40.0, $forecast->getConfidenceRange());

        $noRange = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::FALLBACK,
            source: 'historical',
        );

        $this->assertNull($noRange->getConfidenceRange());
    }

    public function testWithQuantity(): void
    {
        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );

        $adjusted = $forecast->withQuantity(150.0);

        $this->assertSame(150.0, $adjusted->quantity);
        $this->assertSame('manual', $adjusted->source);
        $this->assertSame(ForecastConfidence::MEDIUM, $adjusted->confidence);
        $this->assertArrayHasKey('originalQuantity', $adjusted->metadata);
    }

    public function testFromMl(): void
    {
        $forecast = DemandForecast::fromMl(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidenceScore: 0.90,
            modelVersion: 'v2.0.0',
            lowerBound: 80.0,
            upperBound: 120.0,
        );

        $this->assertSame('ml', $forecast->source);
        $this->assertSame('v2.0.0', $forecast->modelVersion);
        $this->assertSame(ForecastConfidence::HIGH, $forecast->confidence);
        $this->assertSame(80.0, $forecast->lowerBound);
        $this->assertSame(120.0, $forecast->upperBound);
    }

    public function testFromHistorical(): void
    {
        $forecast = DemandForecast::fromHistorical(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            method: 'exponential_smoothing',
        );

        $this->assertSame('historical', $forecast->source);
        $this->assertSame(ForecastConfidence::FALLBACK, $forecast->confidence);
        $this->assertArrayHasKey('method', $forecast->metadata);
        $this->assertSame('exponential_smoothing', $forecast->metadata['method']);
    }

    public function testToArray(): void
    {
        $calculatedAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $forecast = new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 300.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
            modelVersion: 'v1.2.0',
            calculatedAt: $calculatedAt,
        );

        $array = $forecast->toArray();

        $this->assertSame('prod-001', $array['productId']);
        $this->assertSame('2024-01-01', $array['startDate']);
        $this->assertSame('2024-03-31', $array['endDate']);
        $this->assertSame(300.0, $array['quantity']);
        $this->assertSame('high', $array['confidence']);
        $this->assertSame(0.9, $array['confidenceScore']);
        $this->assertSame('ml', $array['source']);
        $this->assertSame('v1.2.0', $array['modelVersion']);
        $this->assertArrayHasKey('summary', $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'productId' => 'prod-001',
            'startDate' => '2024-01-01',
            'endDate' => '2024-03-31',
            'quantity' => 300.0,
            'confidence' => 'high',
            'source' => 'ml',
            'modelVersion' => 'v1.0.0',
        ];

        $forecast = DemandForecast::fromArray($data);

        $this->assertSame('prod-001', $forecast->productId);
        $this->assertSame(300.0, $forecast->quantity);
        $this->assertSame(ForecastConfidence::HIGH, $forecast->confidence);
        $this->assertSame('ml', $forecast->source);
    }

    public function testThrowsExceptionForNegativeQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Forecast quantity cannot be negative');

        new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: -100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );
    }

    public function testThrowsExceptionForInvalidDateRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End date must be after start date');

        new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-03-31'),
            endDate: new \DateTimeImmutable('2024-01-01'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'ml',
        );
    }

    public function testThrowsExceptionForInvalidSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be ml, historical, or manual');

        new DemandForecast(
            productId: 'prod-001',
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-03-31'),
            quantity: 100.0,
            confidence: ForecastConfidence::HIGH,
            source: 'invalid',
        );
    }
}
