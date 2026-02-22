<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult;

/**
 * Test cases for DepreciationRunResult value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class DepreciationRunResultTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 8,
            failureCount: 2,
            totalDepreciation: 8000.00,
            successfulAssets: ['asset_1', 'asset_2'],
            failedAssets: [
                ['assetId' => 'asset_3', 'error' => 'Test error']
            ],
            runId: 'run_123',
            processedBy: 'system'
        );

        $this->assertEquals('period_001', $result->periodId);
        $this->assertEquals(10, $result->totalAssets);
        $this->assertEquals(8, $result->successCount);
        $this->assertEquals(2, $result->failureCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withProcessedAssetsAndErrors_createsResult(): void
    {
        $result = DepreciationRunResult::create(
            periodId: 'period_001',
            runId: 'run_123',
            processedAssets: ['asset_1', 'asset_2', 'asset_3'],
            errors: [
                ['assetId' => 'asset_4', 'message' => 'Error 1'],
                ['assetId' => 'asset_5', 'message' => 'Error 2']
            ],
            currency: 'USD'
        );

        $this->assertEquals('period_001', $result->periodId);
        $this->assertEquals(5, $result->totalAssets);
        $this->assertEquals(3, $result->successCount);
        $this->assertEquals(2, $result->failureCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_withResults_createsSuccessfulResult(): void
    {
        $results = [
            ['assetId' => 'asset_1', 'amount' => 1000.00],
            ['assetId' => 'asset_2', 'amount' => 2000.00],
            ['assetId' => 'asset_3', 'amount' => 1500.00],
        ];

        $result = DepreciationRunResult::success(
            periodId: 'period_001',
            results: $results,
            processedBy: 'system'
        );

        $this->assertEquals('period_001', $result->periodId);
        $this->assertEquals(3, $result->successCount);
        $this->assertEquals(0, $result->failureCount);
        $this->assertEquals(4500.00, $result->totalDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function success_withEmptyResults_createsEmptyResult(): void
    {
        $result = DepreciationRunResult::success(
            periodId: 'period_001',
            results: []
        );

        $this->assertEquals(0, $result->successCount);
        $this->assertEquals(0, $result->totalDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function failure_withFailures_createsFailedResult(): void
    {
        $failures = [
            ['assetId' => 'asset_1', 'message' => 'Error 1'],
            ['assetId' => 'asset_2', 'message' => 'Error 2'],
        ];

        $result = DepreciationRunResult::failure(
            periodId: 'period_001',
            failures: $failures,
            processedBy: 'system'
        );

        $this->assertEquals('period_001', $result->periodId);
        $this->assertEquals(0, $result->successCount);
        $this->assertEquals(2, $result->failureCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasFailures_withFailures_returnsTrue(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 8,
            failureCount: 2,
            totalDepreciation: 8000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertTrue($result->hasFailures());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasFailures_withoutFailures_returnsFalse(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 10,
            failureCount: 0,
            totalDepreciation: 10000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertFalse($result->hasFailures());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isSuccessful_withAllSuccess_returnsTrue(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 10,
            failureCount: 0,
            totalDepreciation: 10000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertTrue($result->isSuccessful());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isSuccessful_withFailures_returnsFalse(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 8,
            failureCount: 2,
            totalDepreciation: 8000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertFalse($result->isSuccessful());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSuccessRate_withAllSuccess_returns100(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 10,
            failureCount: 0,
            totalDepreciation: 10000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertEquals(100.0, $result->getSuccessRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSuccessRate_withPartialSuccess_returnsCorrectRate(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 7,
            failureCount: 3,
            totalDepreciation: 7000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertEquals(70.0, $result->getSuccessRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getSuccessRate_withNoAssets_returns100(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 0,
            successCount: 0,
            failureCount: 0,
            totalDepreciation: 0.0,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertEquals(100.0, $result->getSuccessRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAverageDepreciation_withAssets_returnsCorrectAverage(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 4,
            successCount: 4,
            failureCount: 0,
            totalDepreciation: 4000.00,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertEquals(1000.00, $result->getAverageDepreciation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAverageDepreciation_withNoSuccess_returnsZero(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 5,
            successCount: 0,
            failureCount: 5,
            totalDepreciation: 0.0,
            successfulAssets: [],
            failedAssets: []
        );

        $this->assertEquals(0.0, $result->getAverageDepreciation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function toArray_returnsCorrectArray(): void
    {
        $result = new DepreciationRunResult(
            periodId: 'period_001',
            runDate: new \DateTimeImmutable('2024-01-31'),
            totalAssets: 10,
            successCount: 8,
            failureCount: 2,
            totalDepreciation: 8000.00,
            successfulAssets: ['asset_1', 'asset_2'],
            failedAssets: [
                ['assetId' => 'asset_3', 'error' => 'Error 1']
            ],
            runId: 'run_123',
            processedBy: 'system'
        );

        $array = $result->toArray();
        
        $this->assertArrayHasKey('periodId', $array);
        $this->assertArrayHasKey('totalAssets', $array);
        $this->assertArrayHasKey('successCount', $array);
        $this->assertArrayHasKey('failureCount', $array);
        $this->assertArrayHasKey('totalDepreciation', $array);
    }
}
