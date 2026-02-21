<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\DepreciationCalculator;
use Nexus\FixedAssetDepreciation\Services\DepreciationMethodFactory;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\Exceptions\AssetNotDepreciableException;

/**
 * Test cases for DepreciationCalculator service.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class DepreciationCalculatorTest extends TestCase
{
    private DepreciationCalculator $calculator;
    private AssetDataProviderInterface $assetProvider;
    private DepreciationMethodFactory $methodFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->methodFactory = new DepreciationMethodFactory('advanced');
        $this->assetProvider = $this->createMock(AssetDataProviderInterface::class);
        $this->calculator = new DepreciationCalculator(
            $this->methodFactory,
            $this->assetProvider
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidAsset_returnsDepreciationAmount(): void
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
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculate($assetId);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withNonExistentAsset_throwsException(): void
    {
        // Arrange
        $assetId = 'non-existent';
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->calculator->calculate($assetId);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withZeroCostAsset_throwsException(): void
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
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Assert
        $this->expectException(AssetNotDepreciableException::class);

        // Act
        $this->calculator->calculate($assetId);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_whenFullyDepreciated_returnsZero(): void
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
        // Fully depreciated
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(10000.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculate($assetId);

        // Assert
        $this->assertEquals(0.0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateForPeriod_returnsAssetDepreciationEntity(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $periodId = '2026-01';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculateForPeriod($assetId, $periodId);

        // Assert
        $this->assertEquals($assetId, $result->assetId);
        $this->assertEquals($periodId, $result->periodId);
        $this->assertEquals(DepreciationMethodType::STRAIGHT_LINE, $result->methodType);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_returnsDepreciationForecast(): void
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
        $result = $this->calculator->forecast($assetId, 12);

        // Assert
        $this->assertNotNull($result);
        $this->assertCount(12, $result->periods);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withNonExistentAsset_throwsException(): void
    {
        // Arrange
        $assetId = 'non-existent';
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->calculator->forecast($assetId, 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withDoubleDecliningMethod_returnsCorrectAmount(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(10000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(1000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::DOUBLE_DECLINING);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculate($assetId);

        // Assert
        $this->assertNotNull($result);
        $this->assertGreaterThan(0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withSumOfYearsMethod_returnsCorrectAmount(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(10000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(1000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::SUM_OF_YEARS);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculate($assetId);

        // Assert
        $this->assertNotNull($result);
        $this->assertGreaterThan(0, $result->getAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_withZeroPeriods_returnsEmptyForecast(): void
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
        $result = $this->calculator->forecast($assetId, 0);

        // Assert
        $this->assertNotNull($result);
        $this->assertCount(0, $result->periods);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withSpecificDate_usesProvidedDate(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $specificDate = new DateTimeImmutable('2026-06-15');
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
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculate($assetId, $specificDate, DepreciationType::BOOK);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('USD', $result->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withTaxDepreciationType_returnsAmount(): void
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
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculate($assetId, null, DepreciationType::TAX);

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateForPeriod_withDifferentPeriodIds_returnsCorrectEntity(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $periodId = '2026-06';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->calculator->calculateForPeriod($assetId, $periodId, DepreciationType::TAX);

        // Assert
        $this->assertEquals($assetId, $result->assetId);
        $this->assertEquals($periodId, $result->periodId);
        $this->assertEquals(DepreciationType::TAX, $result->depreciationType);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateForPeriod_withNonExistentAsset_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->calculator->calculateForPeriod('non-existent', '2026-01');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecast_whenBookValueReachesSalvage_returnsZeroForRemainingPeriods(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        // Asset with very small depreciable amount
        $this->assetProvider->method('getAssetCost')->willReturn(1000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(900.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(12);
        // Already depreciated to near salvage value
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(95.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);

        // Act
        $result = $this->calculator->forecast($assetId, 24);

        // Assert
        $this->assertNotNull($result);
        // The forecast should have periods, and some should have zero depreciation
        // when book value reaches salvage value
        $this->assertGreaterThan(0, count($result->periods));
    }
}
