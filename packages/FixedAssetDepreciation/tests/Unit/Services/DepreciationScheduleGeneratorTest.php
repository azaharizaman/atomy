<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FixedAssetDepreciation\Services\DepreciationScheduleGenerator;
use Nexus\FixedAssetDepreciation\Services\DepreciationMethodFactory;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Contracts\Integration\PeriodProviderInterface;
use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;

/**
 * Test cases for DepreciationScheduleGenerator service.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class DepreciationScheduleGeneratorTest extends TestCase
{
    private DepreciationScheduleGenerator $generator;
    private DepreciationMethodFactory $methodFactory;
    private AssetDataProviderInterface&MockObject $assetProvider;
    private PeriodProviderInterface&MockObject $periodProvider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->methodFactory = new DepreciationMethodFactory('basic');
        $this->assetProvider = $this->createMock(AssetDataProviderInterface::class);
        $this->periodProvider = $this->createMock(PeriodProviderInterface::class);
        
        $this->generator = new DepreciationScheduleGenerator(
            $this->methodFactory,
            $this->assetProvider,
            $this->periodProvider
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generate_withValidAsset_returnsSchedule(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $tenantId = 'tenant-001';
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->generator->generate($assetId, $tenantId);

        // Assert
        $this->assertInstanceOf(DepreciationSchedule::class, $result);
        $this->assertEquals($assetId, $result->assetId);
        $this->assertNotEmpty($result->periods);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generate_withZeroCost_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(0.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(0.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->generator->generate('asset-001', 'tenant-001');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generate_withNegativeUsefulLife_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->generator->generate('asset-001', 'tenant-001');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generate_withCustomScheduleId_usesProvidedId(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $tenantId = 'tenant-001';
        $scheduleId = 'custom-schedule-id';
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->generator->generate($assetId, $tenantId, DepreciationType::BOOK, $scheduleId);

        // Assert
        $this->assertEquals($scheduleId, $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regenerateFromPeriod_withValidData_returnsArray(): void
    {
        // Arrange
        $assetId = 'asset-001';
        
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->generator->regenerateFromPeriod(
            $assetId,
            'tenant-001',
            12,
            8000.0,
            4000.0
        );

        // Assert
        $this->assertIsArray($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function adjust_withValidAdjustments_returnsSchedule(): void
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
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        $adjustments = [
            'usefulLifeMonths' => 48,
            'salvageValue' => 1000.0,
            'fromPeriodNumber' => 1,
            'reason' => 'Extended useful life'
        ];

        // Act
        $result = $this->generator->adjust($assetId, 'tenant-001', $adjustments);

        // Assert
        $this->assertInstanceOf(DepreciationSchedule::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function adjust_withZeroCost_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(0.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(0.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        $adjustments = [
            'usefulLifeMonths' => 48,
            'salvageValue' => 1000.0
        ];

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->generator->adjust('asset-001', 'tenant-001', $adjustments);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function adjust_withZeroUsefulLife_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        $adjustments = [
            'usefulLifeMonths' => 0,
            'salvageValue' => 1000.0
        ];

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->generator->adjust('asset-001', 'tenant-001', $adjustments);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function adjust_withSalvageExceedingCost_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        $adjustments = [
            'usefulLifeMonths' => 48,
            'salvageValue' => 15000.0
        ];

        // Assert
        $this->expectException(DepreciationCalculationException::class);

        // Act
        $this->generator->adjust('asset-001', 'tenant-001', $adjustments);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function recalculateFromPeriod_withValidPeriod_returnsSchedule(): void
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
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));

        // Act
        $result = $this->generator->recalculateFromPeriod(
            $assetId,
            'tenant-001',
            12,
            ['salvageValue' => 1500.0]
        );

        // Assert
        $this->assertInstanceOf(DepreciationSchedule::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validateAdjustment_withValidParams_returnsEmptyArray(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);

        // Act
        $result = $this->generator->validateAdjustment('asset-001', 48, 1500.0);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validateAdjustment_withZeroCost_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(0.0);

        // Act
        $result = $this->generator->validateAdjustment('asset-001', 60, 0.0);

        // Assert
        $this->assertContains('Asset cost must be greater than zero', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validateAdjustment_withZeroUsefulLife_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);

        // Act
        $result = $this->generator->validateAdjustment('asset-001', 0, 2000.0);

        // Assert
        $this->assertContains('Useful life must be greater than zero', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validateAdjustment_withNegativeSalvage_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);

        // Act
        $result = $this->generator->validateAdjustment('asset-001', 60, -100.0);

        // Assert
        $this->assertContains('Salvage value cannot be negative', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validateAdjustment_withSalvageExceedingCost_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);

        // Act
        $result = $this->generator->validateAdjustment('asset-001', 60, 15000.0);

        // Assert
        $this->assertContains('Salvage value cannot exceed asset cost', $result);
    }
}
