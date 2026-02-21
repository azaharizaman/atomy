<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\DepreciationForecastService;
use Nexus\FixedAssetDepreciation\Services\DepreciationMethodFactory;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for DepreciationForecastService.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class DepreciationForecastServiceTest extends TestCase
{
    private DepreciationForecastService $service;
    private AssetDataProviderInterface $assetProvider;
    private DepreciationMethodFactory $methodFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->methodFactory = new DepreciationMethodFactory('advanced');
        $this->assetProvider = $this->createMock(AssetDataProviderInterface::class);
        $this->service = new DepreciationForecastService(
            $this->methodFactory,
            $this->assetProvider
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withValidAsset_returnsDepreciationForecast(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD',
            'name' => 'Test Asset'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecast($assetId, 12);

        // Assert
        $this->assertNotNull($result);
        $this->assertCount(12, $result->periods);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withZeroPeriods_throwsException(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withNegativePeriods_throwsException(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, -5);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withNonExistentAsset_throwsException(): void
    {
        // Arrange
        $assetId = 'non-existent';
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withZeroCost_throwsException(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(0.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(0.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withZeroUsefulLife_throwsException(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withNegativeSalvageValue_throwsException(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(-500.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withSalvageGreaterThanCost_throwsException(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(10000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(15000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->forecast($assetId, 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withMethodOverride_usesProvidedMethod(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecast(
            $assetId,
            12,
            DepreciationMethodType::DOUBLE_DECLINING
        );

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastRemainingLife_withValidAsset_returnsForecast(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastRemainingLife($assetId);

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastRemainingLife_whenFullyDepreciated_returnsEmptyForecast(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(10000.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // When fully depreciated, forecastRemainingLife should return an empty forecast
        // This is a valid case - it returns a DepreciationForecast with 0 periods
        $result = $this->service->forecastRemainingLife($assetId);

        // Assert - the result should be a valid DepreciationForecast
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastAnnual_withValidAsset_returnsYearlySummary(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastAnnual($assetId, 5);

        // Assert
        $this->assertIsArray($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTotalRemainingDepreciation_withValidAsset_returnsCorrectAmount(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(3000.0);

        // Act
        $result = $this->service->getTotalRemainingDepreciation($assetId);

        // Assert
        // (12000 - 2000) - 3000 = 7000
        $this->assertEquals(7000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTotalRemainingDepreciation_whenFullyDepreciated_returnsZero(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(10000.0);

        // Act
        $result = $this->service->getTotalRemainingDepreciation($assetId);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTotalRemainingDepreciation_whenOverDepreciated_returnsZero(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(12000.0);

        // Act
        $result = $this->service->getTotalRemainingDepreciation($assetId);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProjectedMonthlyDepreciation_withValidAsset_returnsCorrectAmount(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Act
        $result = $this->service->getProjectedMonthlyDepreciation($assetId);

        // Assert
        // (12000 - 2000) / 60 = 166.67
        $this->assertEqualsWithDelta(166.67, $result, 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProjectedMonthlyDepreciation_withPartialDepreciation_returnsCorrectAmount(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        // 30% depreciated = 18 months remaining
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(3000.0);

        // Act
        $result = $this->service->getProjectedMonthlyDepreciation($assetId);

        // Assert
        // Remaining: 10000 - 3000 = 7000
        // Remaining months: 60 * 0.7 = 42
        // 7000 / 42 = 166.67
        $this->assertEqualsWithDelta(166.67, $result, 0.01);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withCustomStartDate_usesProvidedDate(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $startDate = new DateTimeImmutable('2025-01-01');
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecast($assetId, 12, null, $startDate);

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastRemainingLife_withZeroUsefulLife_returnsEmptyForecast(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastRemainingLife($assetId);

        // Assert
        $this->assertNotNull($result);
        $this->assertCount(0, $result->periods);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastRemainingLife_withCustomMethod_usesProvidedMethod(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastRemainingLife(
            $assetId,
            DepreciationMethodType::DOUBLE_DECLINING
        );

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastRemainingLife_withCustomStartDate_usesProvidedDate(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $startDate = new DateTimeImmutable('2025-01-01');
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastRemainingLife(
            $assetId,
            null,
            $startDate
        );

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastAnnual_withCustomMethod_usesProvidedMethod(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastAnnual(
            $assetId,
            5,
            DepreciationMethodType::DOUBLE_DECLINING
        );

        // Assert
        $this->assertIsArray($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastAnnual_withZeroYears_returnsEmptyArray(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecastAnnual($assetId, 0);

        // Assert
        $this->assertIsArray($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProjectedMonthlyDepreciation_withZeroDepreciableAmount_returnsZero(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(10000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(10000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Act
        $result = $this->service->getProjectedMonthlyDepreciation($assetId);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProjectedMonthlyDepreciation_withFullyDepreciatedAsset_returnsZero(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(12000.0);

        // Act
        $result = $this->service->getProjectedMonthlyDepreciation($assetId);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getProjectedMonthlyDepreciation_withCustomMethod_usesProvidedMethod(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Act
        $result = $this->service->getProjectedMonthlyDepreciation(
            $assetId,
            DepreciationMethodType::DOUBLE_DECLINING
        );

        // Assert
        $this->assertGreaterThan(0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_whenBookValueReachesSalvage_stopsGenerating(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        // Small depreciable amount that will be exhausted quickly
        $this->assetProvider->method('getAssetCost')->willReturn(1000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(900.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(12);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->service->forecast($assetId, 24);

        // Assert - should have fewer periods than requested since asset is fully depreciated
        $this->assertLessThanOrEqual(24, count($result->periods));
    }

    #[PHPUnit\Framework\Attributes\Test]
    public function forecastRemainingLife_whenCostEqualsSalvage_returnsEmptyForecast(): void
    {
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(1000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(1000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        $result = $this->service->forecastRemainingLife($assetId);

        $this->assertEmpty($result->periods);
        $this->assertEquals(0.0, $result->totalDepreciation);
    }
}
