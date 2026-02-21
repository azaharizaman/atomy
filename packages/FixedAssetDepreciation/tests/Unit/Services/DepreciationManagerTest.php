<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FixedAssetDepreciation\Services\DepreciationManager;
use Nexus\FixedAssetDepreciation\Services\DepreciationCalculator;
use Nexus\FixedAssetDepreciation\Services\DepreciationScheduleGenerator;
use Nexus\FixedAssetDepreciation\Services\AssetRevaluationService;
use Nexus\FixedAssetDepreciation\Services\DepreciationMethodFactory;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
use Nexus\FixedAssetDepreciation\Exceptions\AssetNotDepreciableException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Test cases for DepreciationManager service.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class DepreciationManagerTest extends TestCase
{
    private DepreciationManager $manager;
    private DepreciationCalculator&MockObject $calculator;
    private DepreciationScheduleGenerator&MockObject $scheduleGenerator;
    private AssetRevaluationService&MockObject $revaluationService;
    private DepreciationMethodFactory $methodFactory;
    private AssetDataProviderInterface&MockObject $assetProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calculator = $this->createMock(DepreciationCalculator::class);
        $this->scheduleGenerator = $this->createMock(DepreciationScheduleGenerator::class);
        $this->revaluationService = $this->createMock(AssetRevaluationService::class);
        $this->methodFactory = new DepreciationMethodFactory('basic');
        $this->assetProvider = $this->createMock(AssetDataProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->manager = new DepreciationManager(
            $this->calculator,
            $this->scheduleGenerator,
            $this->revaluationService,
            $this->methodFactory,
            $this->assetProvider,
            $this->eventDispatcher,
            $this->logger,
            2 // Tier 2
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDepreciation_withValidAsset_returnsDepreciation(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $periodId = '2026-01';
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'tenant_id' => 'tenant-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('isAssetActive')->willReturn(true);
        
        $this->calculator->method('calculate')->willReturn(new DepreciationAmount(
            amount: 166.67,
            currency: 'USD'
        ));
        
        $this->eventDispatcher->expects($this->once())->method('dispatch');
        $this->logger->expects($this->once())->method('info');

        // Act
        $result = $this->manager->calculateDepreciation($assetId, $periodId);

        // Assert
        $this->assertEquals($assetId, $result->assetId);
        $this->assertEquals($periodId, $result->periodId);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDepreciation_whenAssetNotFound_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(AssetNotDepreciableException::class);

        // Act
        $this->manager->calculateDepreciation('non-existent', '2026-01');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDepreciation_whenAssetInactive_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'status' => 'disposed'
        ]);
        $this->assetProvider->method('isAssetActive')->willReturn(false);

        // Assert
        $this->expectException(AssetNotDepreciableException::class);

        // Act
        $this->manager->calculateDepreciation('asset-001', '2026-01');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDepreciation_whenZeroCost_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'status' => 'active'
        ]);
        $this->assetProvider->method('isAssetActive')->willReturn(true);
        $this->assetProvider->method('getAssetCost')->willReturn(0.0);

        // Assert
        $this->expectException(AssetNotDepreciableException::class);

        // Act
        $this->manager->calculateDepreciation('asset-001', '2026-01');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDepreciation_whenFullyDepreciated_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'status' => 'active'
        ]);
        $this->assetProvider->method('isAssetActive')->willReturn(true);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(10000.0);

        // Assert
        $this->expectException(AssetNotDepreciableException::class);

        // Act
        $this->manager->calculateDepreciation('asset-001', '2026-01');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reverseDepreciation_logsWarning(): void
    {
        // Arrange
        $this->logger->expects($this->once())->method('warning');

        // Act
        $this->manager->reverseDepreciation('DEP-001', 'Test reversal');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalueAsset_withRevaluationService_returnsRevaluation(): void
    {
        // Arrange
        $assetId = 'asset-001';
        
        $this->revaluationService->method('revalue')->willReturn(\Nexus\FixedAssetDepreciation\Entities\AssetRevaluation::create(
            $assetId,
            'tenant-001',
            new \Nexus\FixedAssetDepreciation\ValueObjects\BookValue(12000.0, 2000.0, 0.0),
            new \Nexus\FixedAssetDepreciation\ValueObjects\BookValue(15000.0, 2000.0, 0.0),
            \Nexus\FixedAssetDepreciation\Enums\RevaluationType::INCREMENT,
            'Test revaluation'
        ));

        // Act
        $result = $this->manager->revalueAsset(
            $assetId,
            15000.0,
            2000.0,
            RevaluationType::INCREMENT,
            'Test'
        );

        // Assert
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalueAsset_whenServiceNotAvailable_throwsException(): void
    {
        // Arrange
        $manager = new DepreciationManager(
            $this->calculator,
            $this->scheduleGenerator,
            null, // No revaluation service
            $this->methodFactory,
            $this->assetProvider,
            $this->eventDispatcher,
            $this->logger,
            1 // Tier 1
        );

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $manager->revalueAsset('asset-001', 15000.0, 2000.0, RevaluationType::INCREMENT, 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateTaxDepreciation_whenTier3_returnsDepreciationAmount(): void
    {
        // Arrange
        $manager = new DepreciationManager(
            $this->calculator,
            $this->scheduleGenerator,
            $this->revaluationService,
            $this->methodFactory,
            $this->assetProvider,
            $this->eventDispatcher,
            $this->logger,
            3 // Tier 3
        );
        
        $this->calculator->method('calculate')->willReturn(new DepreciationAmount(
            amount: 3000.0,
            currency: 'USD'
        ));

        // Act
        $result = $manager->calculateTaxDepreciation('asset-001', 'MACRS', 2026);

        // Assert
        $this->assertInstanceOf(DepreciationAmount::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateTaxDepreciation_whenTierLessThan3_throwsException(): void
    {
        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $this->manager->calculateTaxDepreciation('asset-001', 'MACRS', 2026);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastDepreciation_whenTier2_returnsForecast(): void
    {
        // Arrange
        $assetId = 'asset-001';
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        
        $this->calculator->method('forecast')->willReturn(new DepreciationForecast(
            [],
            10000.0,
            833.33,
            12
        ));

        // Act
        $result = $this->manager->forecastDepreciation($assetId, 12);

        // Assert
        $this->assertInstanceOf(DepreciationForecast::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forecastDepreciation_whenTier1_throwsException(): void
    {
        // Arrange
        $manager = new DepreciationManager(
            $this->calculator,
            $this->scheduleGenerator,
            $this->revaluationService,
            $this->methodFactory,
            $this->assetProvider,
            $this->eventDispatcher,
            $this->logger,
            1 // Tier 1
        );

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $manager->forecastDepreciation('asset-001', 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generateSchedule_withValidAsset_returnsSchedule(): void
    {
        // Arrange
        $assetId = 'asset-001';
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'tenant_id' => 'tenant-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);
        $this->assetProvider->method('getAssetDepreciationMethod')->willReturn(DepreciationMethodType::STRAIGHT_LINE);
        $this->assetProvider->method('isAssetActive')->willReturn(true);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->assetProvider->method('getAssetAcquisitionDate')->willReturn(new DateTimeImmutable('2026-01-01'));
        
        $this->scheduleGenerator->method('generate')->willReturn(
            new \Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule(
                id: 'SCH-001',
                assetId: $assetId,
                tenantId: 'tenant-001',
                methodType: DepreciationMethodType::STRAIGHT_LINE,
                depreciationType: \Nexus\FixedAssetDepreciation\Enums\DepreciationType::BOOK,
                depreciationLife: \Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife::fromYears(5, 12000.0, 2000.0),
                acquisitionDate: new DateTimeImmutable('2026-01-01'),
                startDepreciationDate: new DateTimeImmutable('2026-01-01'),
                endDepreciationDate: new DateTimeImmutable('2030-12-31'),
                prorateConvention: \Nexus\FixedAssetDepreciation\Enums\ProrateConvention::DAILY,
                periods: [],
                status: \Nexus\FixedAssetDepreciation\Enums\DepreciationStatus::CALCULATED,
                currency: 'USD',
                createdAt: new DateTimeImmutable()
            )
        );

        // Act
        $result = $this->manager->generateSchedule($assetId);

        // Assert
        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generateSchedule_whenAssetNotFound_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);
        $this->assetProvider->method('isAssetActive')->willReturn(true);

        // Assert
        $this->expectException(AssetNotDepreciableException::class);

        // Act
        $this->manager->generateSchedule('non-existent');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function runPeriodicDepreciation_returnsDepreciationRunResult(): void
    {
        // Arrange
        $periodId = '2026-01';
        $this->logger->expects($this->once())->method('info');

        // Act
        $result = $this->manager->runPeriodicDepreciation($periodId);

        // Assert
        $this->assertInstanceOf(\Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult::class, $result);
        $this->assertEquals($periodId, $result->periodId);
    }
}
