<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\FixedAssetDepreciation\Services\AssetRevaluationService;
use Nexus\FixedAssetDepreciation\Services\DepreciationScheduleGenerator;
use Nexus\FixedAssetDepreciation\Services\DepreciationForecastService;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\Exceptions\RevaluationException;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Test cases for AssetRevaluationService.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class AssetRevaluationServiceTest extends TestCase
{
    private AssetRevaluationService $service;
    private AssetDataProviderInterface&MockObject $assetProvider;
    private DepreciationScheduleGenerator&MockObject $scheduleGenerator;
    private DepreciationForecastService&MockObject $forecastService;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->assetProvider = $this->createMock(AssetDataProviderInterface::class);
        $this->scheduleGenerator = $this->createMock(DepreciationScheduleGenerator::class);
        $this->forecastService = $this->createMock(DepreciationForecastService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        
        $this->service = new AssetRevaluationService(
            $this->assetProvider,
            $this->scheduleGenerator,
            $this->forecastService,
            $this->eventDispatcher,
            2 // Tier 2 - Advanced
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_withValidIncrement_returnsRevaluation(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $newCost = 15000.0;
        $newSalvageValue = 2000.0;
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'tenant_id' => 'tenant-001',
            'currency' => 'USD',
            'name' => 'Test Asset'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);
        $this->assetProvider->expects($this->once())->method('updateAccumulatedDepreciation');
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        // Act
        $result = $this->service->revalue(
            $assetId,
            $newCost,
            $newSalvageValue,
            RevaluationType::INCREMENT,
            'Market value increase'
        );

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
        $this->assertEquals($assetId, $result->assetId);
        $this->assertTrue($result->isIncrement());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_withValidDecrement_returnsRevaluation(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $newCost = 8000.0;
        $newSalvageValue = 1000.0;
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'tenant_id' => 'tenant-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);
        $this->assetProvider->expects($this->once())->method('updateAccumulatedDepreciation');
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        // Act
        $result = $this->service->revalue(
            $assetId,
            $newCost,
            $newSalvageValue,
            RevaluationType::DECREMENT,
            'Market value decrease'
        );

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
        $this->assertEquals($assetId, $result->assetId);
        $this->assertTrue($result->isDecrement());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_whenTier1_throwsException(): void
    {
        // Arrange
        $service = new AssetRevaluationService(
            $this->assetProvider,
            $this->scheduleGenerator,
            $this->forecastService,
            $this->eventDispatcher,
            1 // Tier 1 - Basic
        );

        // Assert
        $this->expectException(RevaluationException::class);

        // Act
        $service->revalue(
            'asset-001',
            15000.0,
            2000.0,
            RevaluationType::INCREMENT,
            'Test'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_whenAssetNotFound_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(RevaluationException::class);

        // Act
        $this->service->revalue(
            'non-existent',
            15000.0,
            2000.0,
            RevaluationType::INCREMENT,
            'Test'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_whenSalvageExceedsCost_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'tenant_id' => 'tenant-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(10000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Assert
        $this->expectException(RevaluationException::class);

        // Act
        $this->service->revalue(
            'asset-001',
            5000.0, // Cost is less than salvage
            8000.0,
            RevaluationType::DECREMENT,
            'Test'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateImpact_withValidAsset_returnsImpactArray(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);

        // Act
        $result = $this->service->calculateImpact($assetId, 11000.0);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('previousValue', $result);
        $this->assertArrayHasKey('newValue', $result);
        $this->assertArrayHasKey('revaluationAmount', $result);
        $this->assertArrayHasKey('revaluationType', $result);
        $this->assertArrayHasKey('depreciationImpact', $result);
        $this->assertArrayHasKey('annualDepreciationChange', $result);
        $this->assertArrayHasKey('revaluationReserveImpact', $result);
        $this->assertArrayHasKey('remainingMonths', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateImpact_whenAssetNotFound_throwsException(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->calculateImpact('non-existent', 10000.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCurrentBookValue_returnsBookValue(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(4000.0);

        // Act
        $result = $this->service->getCurrentBookValue($assetId);

        // Assert
        $this->assertInstanceOf(BookValue::class, $result);
        $this->assertEquals(12000.0, $result->cost);
        $this->assertEquals(2000.0, $result->salvageValue);
        $this->assertEquals(4000.0, $result->accumulatedDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getHistory_returnsEmptyArray(): void
    {
        // Act
        $result = $this->service->getHistory('asset-001');

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function postToGl_whenRevaluationNotFound_throwsException(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->postToGl('non-existent');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processFullRevaluation_whenTier1_throwsException(): void
    {
        // Arrange
        $service = new AssetRevaluationService(
            $this->assetProvider,
            $this->scheduleGenerator,
            $this->forecastService,
            $this->eventDispatcher,
            1
        );

        // Assert
        $this->expectException(RevaluationException::class);

        // Act
        $service->processFullRevaluation(
            'asset-001',
            15000.0,
            2000.0,
            'Test',
            'GL-001'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function recalculateDepreciation_whenRevaluationNotFound_throwsException(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->recalculateDepreciation('asset-001', 'non-existent');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withValidParams_returnsEmptyArray(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);

        // Act
        $result = $this->service->validate('asset-001', 11000.0, 2000.0);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_whenAssetNotFound_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Act
        $result = $this->service->validate('non-existent', 10000.0, 2000.0);

        // Assert
        $this->assertIsArray($result);
        $this->assertContains('Asset not found', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_whenSalvageNegative_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Act
        $result = $this->service->validate('asset-001', 10000.0, -500.0);

        // Assert
        $this->assertContains('Salvage value cannot be negative', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_whenSalvageExceedsValue_returnsError(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Act
        $result = $this->service->validate('asset-001', 10000.0, 15000.0);

        // Assert
        $this->assertContains('Salvage value cannot exceed the new asset value', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getReserveBalance_returnsZero(): void
    {
        // Act
        $result = $this->service->getReserveBalance('asset-001');

        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canRevaluate_whenTier2AndAssetActive_returnsTrue(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'status' => 'active'
        ]);
        $this->assetProvider->method('isAssetActive')->willReturn(true);

        // Act
        $result = $this->service->canRevalue('asset-001');

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canRevaluate_whenTier1_returnsFalse(): void
    {
        // Arrange
        $service = new AssetRevaluationService(
            $this->assetProvider,
            $this->scheduleGenerator,
            $this->forecastService,
            $this->eventDispatcher,
            1
        );

        // Act
        $result = $service->canRevalue('asset-001');

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canRevaluate_whenAssetNotFound_returnsFalse(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn(null);

        // Act
        $result = $this->service->canRevalue('non-existent');

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canRevaluate_whenAssetInactive_returnsFalse(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'status' => 'disposed'
        ]);
        $this->assetProvider->method('isAssetActive')->willReturn(false);

        // Act
        $result = $this->service->canRevalue('asset-001');

        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getForPeriod_returnsNull(): void
    {
        // Act
        $result = $this->service->getForPeriod('asset-001', '2026-01');

        // Assert
        $this->assertNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function findById_returnsNull(): void
    {
        // Act
        $result = $this->service->findById('REV-001');

        // Assert
        $this->assertNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function approve_whenRevaluationNotFound_throwsException(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->approve('non-existent', 'user-001');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reverse_whenRevaluationNotFound_throwsException(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->reverse('non-existent', 'Test reversal');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validate_withSignificantChange_returnsWarning(): void
    {
        // Arrange
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(0.0);

        // Act - propose value that is 60% different from current book value
        $result = $this->service->validate('asset-001', 19200.0, 2000.0);

        // Assert - should contain warning about significant change
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Warning', $result[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateImpact_withDecrementType_returnsCorrectType(): void
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

        // Act - propose value lower than current book value
        $result = $this->service->calculateImpact($assetId, 8000.0);

        // Assert
        $this->assertEquals(RevaluationType::DECREMENT, $result['revaluationType']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_withCustomDate_usesProvidedDate(): void
    {
        // Arrange
        $assetId = 'asset-001';
        $customDate = new DateTimeImmutable('2025-06-15');
        
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => $assetId,
            'tenant_id' => 'tenant-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        // Act
        $result = $this->service->revalue(
            $assetId,
            15000.0,
            2000.0,
            RevaluationType::INCREMENT,
            'Test',
            $customDate
        );

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revalue_withGlAccountId_includesGlAccount(): void
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
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        // Act
        $result = $this->service->revalue(
            $assetId,
            15000.0,
            2000.0,
            RevaluationType::INCREMENT,
            'Test',
            null,
            'GL-ACCOUNT-001'
        );

        // Assert
        $this->assertEquals('GL-ACCOUNT-001', $result->glAccountId);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getHistory_withOptions_returnsEmptyArray(): void
    {
        // Act
        $result = $this->service->getHistory('asset-001', ['limit' => 10]);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function reverse_withExistingRevaluation_returnsReversal(): void
    {
        // Arrange - create a partial mock that returns a revaluation for findById
        $service = $this->getMockBuilder(AssetRevaluationService::class)
            ->setConstructorArgs([
                $this->assetProvider,
                $this->scheduleGenerator,
                $this->forecastService,
                $this->eventDispatcher,
                2
            ])
            ->onlyMethods(['findById'])
            ->getMock();

        $originalRevaluation = new AssetRevaluation(
            id: 'REV-001',
            assetId: 'asset-001',
            tenantId: 'tenant-001',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 2000.0),
            newBookValue: new BookValue(15000.0, 2000.0, 2000.0),
            revaluationAmount: \Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount::createIncrement(
                10000.0, 15000.0, 1000.0, 2000.0, 2000.0, 'USD'
            ),
            glAccountId: 'GL-001',
            reason: 'Test revaluation',
            createdAt: new DateTimeImmutable()
        );

        $service->method('findById')->willReturn($originalRevaluation);

        // Act
        $result = $service->reverse('REV-001', 'Error in original revaluation');

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
        $this->assertEquals('asset-001', $result->assetId);
        $this->assertTrue($result->isDecrement()); // Reversal of increment is decrement
        $this->assertStringContainsString('Reversal:', $result->reason);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function postToGl_withExistingRevaluation_returnsPostedRevaluation(): void
    {
        // Arrange - create a partial mock that returns a revaluation for findById
        $service = $this->getMockBuilder(AssetRevaluationService::class)
            ->setConstructorArgs([
                $this->assetProvider,
                $this->scheduleGenerator,
                $this->forecastService,
                $this->eventDispatcher,
                2
            ])
            ->onlyMethods(['findById'])
            ->getMock();

        $originalRevaluation = new AssetRevaluation(
            id: 'REV-001',
            assetId: 'asset-001',
            tenantId: 'tenant-001',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 2000.0),
            newBookValue: new BookValue(15000.0, 2000.0, 2000.0),
            revaluationAmount: \Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount::createIncrement(
                10000.0, 15000.0, 1000.0, 2000.0, 2000.0, 'USD'
            ),
            glAccountId: 'GL-001',
            reason: 'Test revaluation',
            createdAt: new DateTimeImmutable()
        );

        $service->method('findById')->willReturn($originalRevaluation);

        // Act
        $result = $service->postToGl('REV-001');

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
        $this->assertNotNull($result->journalEntryId);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function approve_withExistingRevaluation_returnsApprovedRevaluation(): void
    {
        // Arrange - create a partial mock that returns a revaluation for findById
        $service = $this->getMockBuilder(AssetRevaluationService::class)
            ->setConstructorArgs([
                $this->assetProvider,
                $this->scheduleGenerator,
                $this->forecastService,
                $this->eventDispatcher,
                2
            ])
            ->onlyMethods(['findById'])
            ->getMock();

        $originalRevaluation = new AssetRevaluation(
            id: 'REV-001',
            assetId: 'asset-001',
            tenantId: 'tenant-001',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 2000.0),
            newBookValue: new BookValue(15000.0, 2000.0, 2000.0),
            revaluationAmount: \Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount::createIncrement(
                10000.0, 15000.0, 1000.0, 2000.0, 2000.0, 'USD'
            ),
            glAccountId: 'GL-001',
            reason: 'Test revaluation',
            createdAt: new DateTimeImmutable()
        );

        $service->method('findById')->willReturn($originalRevaluation);

        // Act
        $result = $service->approve('REV-001', 'user-001');

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function recalculateDepreciation_withExistingRevaluation_executesSuccessfully(): void
    {
        // Arrange - create a partial mock that returns a revaluation for findById
        $service = $this->getMockBuilder(AssetRevaluationService::class)
            ->setConstructorArgs([
                $this->assetProvider,
                $this->scheduleGenerator,
                $this->forecastService,
                $this->eventDispatcher,
                2
            ])
            ->onlyMethods(['findById'])
            ->getMock();

        $originalRevaluation = new AssetRevaluation(
            id: 'REV-001',
            assetId: 'asset-001',
            tenantId: 'tenant-001',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(10000.0, 1000.0, 2000.0),
            newBookValue: new BookValue(15000.0, 2000.0, 2000.0),
            revaluationAmount: \Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount::createIncrement(
                10000.0, 15000.0, 1000.0, 2000.0, 2000.0, 'USD'
            ),
            glAccountId: 'GL-001',
            reason: 'Test revaluation',
            createdAt: new DateTimeImmutable()
        );

        $service->method('findById')->willReturn($originalRevaluation);
        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'tenant_id' => 'tenant-001'
        ]);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);

        // Act & Assert - should not throw
        $service->recalculateDepreciation('asset-001', 'REV-001');
        $this->addToAssertionCount(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function processFullRevaluation_withValidAsset_returnsRevaluation(): void
    {
        // Arrange - create a partial mock that returns a revaluation for findById
        $service = $this->getMockBuilder(AssetRevaluationService::class)
            ->setConstructorArgs([
                $this->assetProvider,
                $this->scheduleGenerator,
                $this->forecastService,
                $this->eventDispatcher,
                2
            ])
            ->onlyMethods(['findById'])
            ->getMock();

        $this->assetProvider->method('getAsset')->willReturn([
            'id' => 'asset-001',
            'tenant_id' => 'tenant-001',
            'currency' => 'USD'
        ]);
        $this->assetProvider->method('getAssetCost')->willReturn(12000.0);
        $this->assetProvider->method('getAssetSalvageValue')->willReturn(2000.0);
        $this->assetProvider->method('getAccumulatedDepreciation')->willReturn(2000.0);
        $this->assetProvider->method('getAssetUsefulLife')->willReturn(60);
        $this->eventDispatcher->method('dispatch');

        // Create a revaluation that will be returned by findById after revalue() is called
        $revaluation = new AssetRevaluation(
            id: 'REV-001',
            assetId: 'asset-001',
            tenantId: 'tenant-001',
            revaluationDate: new DateTimeImmutable(),
            revaluationType: RevaluationType::INCREMENT,
            previousBookValue: new BookValue(12000.0, 2000.0, 2000.0),
            newBookValue: new BookValue(15000.0, 2000.0, 2000.0),
            revaluationAmount: \Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount::createIncrement(
                12000.0, 15000.0, 2000.0, 2000.0, 2000.0, 'USD'
            ),
            glAccountId: 'GL-001',
            reason: 'Test revaluation',
            createdAt: new DateTimeImmutable()
        );
        $service->method('findById')->willReturn($revaluation);

        // Act
        $result = $service->processFullRevaluation(
            'asset-001',
            15000.0,
            2000.0,
            'Fair value adjustment',
            'GL-RESERVE-001'
        );

        // Assert
        $this->assertInstanceOf(AssetRevaluation::class, $result);
    }
}
