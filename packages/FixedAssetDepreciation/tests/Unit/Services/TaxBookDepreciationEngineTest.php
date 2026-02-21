<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Services\TaxBookDepreciationEngine;
use Nexus\FixedAssetDepreciation\Services\Methods\StraightLineDepreciationMethod;
use Nexus\FixedAssetDepreciation\Services\Methods\DoubleDecliningDepreciationMethod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;

/**
 * Test cases for TaxBookDepreciationEngine service.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class TaxBookDepreciationEngineTest extends TestCase
{
    private TaxBookDepreciationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->engine = new TaxBookDepreciationEngine(0.21);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withValidData_returnsTaxBookDepreciationResult(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $bookMethod = new StraightLineDepreciationMethod();
        $taxMethod = new DoubleDecliningDepreciationMethod();
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        
        // Act
        $result = $this->engine->calculate(
            $cost,
            $salvageValue,
            $bookMethod,
            $taxMethod,
            $startDate,
            $endDate
        );
        
        // Assert
        $this->assertNotNull($result);
        $this->assertNotNull($result->bookDepreciation);
        $this->assertNotNull($result->taxDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withTaxDepreciationGreaterThanBook_returnsPositiveTemporaryDifference(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $bookMethod = new StraightLineDepreciationMethod();
        $taxMethod = new DoubleDecliningDepreciationMethod();
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        
        // Act
        $result = $this->engine->calculate(
            $cost,
            $salvageValue,
            $bookMethod,
            $taxMethod,
            $startDate,
            $endDate
        );
        
        // Assert
        $this->assertIsFloat($result->temporaryDifference);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateSchedule_withValidData_returnsArrayOfResults(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $bookMethod = new StraightLineDepreciationMethod();
        $taxMethod = new DoubleDecliningDepreciationMethod();
        $startDate = new DateTimeImmutable('2026-01-01');
        
        // Act
        $results = $this->engine->calculateSchedule(
            $cost,
            $salvageValue,
            $bookMethod,
            $taxMethod,
            $startDate,
            12
        );
        
        // Assert
        $this->assertIsArray($results);
        $this->assertCount(12, $results);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateSchedule_withSinglePeriod_returnsSingleResult(): void
    {
        // Arrange
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $bookMethod = new StraightLineDepreciationMethod();
        $taxMethod = new DoubleDecliningDepreciationMethod();
        $startDate = new DateTimeImmutable('2026-01-01');
        
        // Act
        $results = $this->engine->calculateSchedule(
            $cost,
            $salvageValue,
            $bookMethod,
            $taxMethod,
            $startDate,
            1
        );
        
        // Assert
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDeferredTaxLiability_withPositiveDifference_returnsPositiveValue(): void
    {
        // Arrange
        $temporaryDifference = 10000.0;
        
        // Act
        $result = $this->engine->calculateDeferredTaxLiability($temporaryDifference);
        
        // Assert
        $this->assertEquals(2100.0, $result); // 10000 * 0.21
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDeferredTaxLiability_withCustomTaxRate_usesCustomRate(): void
    {
        // Arrange
        $temporaryDifference = 10000.0;
        $customRate = 0.30;
        
        // Act
        $result = $this->engine->calculateDeferredTaxLiability($temporaryDifference, $customRate);
        
        // Assert
        $this->assertEquals(3000.0, $result); // 10000 * 0.30
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateDeferredTaxAsset_withNegativeDifference_returnsPositiveValue(): void
    {
        // Arrange
        $temporaryDifference = -10000.0;
        
        // Act
        $result = $this->engine->calculateDeferredTaxAsset($temporaryDifference);
        
        // Assert
        $this->assertEquals(2100.0, $result); // abs(-10000) * 0.21
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTaxRate_returnsConfiguredTaxRate(): void
    {
        // Act
        $result = $this->engine->getTaxRate();
        
        // Assert
        $this->assertEquals(0.21, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateBookBasis_withAccumulatedDepreciation_returnsCorrectValue(): void
    {
        // Arrange
        $cost = 100000.0;
        $accumulatedDepreciation = 20000.0;
        
        // Act
        $result = $this->engine->calculateBookBasis($cost, $accumulatedDepreciation);
        
        // Assert
        $this->assertEquals(80000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateBookBasis_withExcessDepreciation_returnsZero(): void
    {
        // Arrange
        $cost = 100000.0;
        $accumulatedDepreciation = 120000.0;
        
        // Act
        $result = $this->engine->calculateBookBasis($cost, $accumulatedDepreciation);
        
        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateTaxBasis_withAccumulatedDepreciation_returnsCorrectValue(): void
    {
        // Arrange
        $cost = 100000.0;
        $accumulatedTaxDepreciation = 30000.0;
        
        // Act
        $result = $this->engine->calculateTaxBasis($cost, $accumulatedTaxDepreciation);
        
        // Assert
        $this->assertEquals(70000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateTaxBasis_withExcessDepreciation_returnsZero(): void
    {
        // Arrange
        $cost = 100000.0;
        $accumulatedTaxDepreciation = 120000.0;
        
        // Act
        $result = $this->engine->calculateTaxBasis($cost, $accumulatedTaxDepreciation);
        
        // Assert
        $this->assertEquals(0.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateBasisDifference_withBookGreaterThanTax_returnsPositiveValue(): void
    {
        // Arrange
        $bookBasis = 80000.0;
        $taxBasis = 70000.0;
        
        // Act
        $result = $this->engine->calculateBasisDifference($bookBasis, $taxBasis);
        
        // Assert
        $this->assertEquals(10000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateBasisDifference_withTaxGreaterThanBook_returnsNegativeValue(): void
    {
        // Arrange
        $bookBasis = 70000.0;
        $taxBasis = 80000.0;
        
        // Act
        $result = $this->engine->calculateBasisDifference($bookBasis, $taxBasis);
        
        // Assert
        $this->assertEquals(-10000.0, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDeferredTaxLiability_withPositiveDifference_returnsTrue(): void
    {
        // Arrange
        $cumulativeDifference = 10000.0;
        
        // Act
        $result = $this->engine->hasDeferredTaxLiability($cumulativeDifference);
        
        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDeferredTaxLiability_withNegativeDifference_returnsFalse(): void
    {
        // Arrange
        $cumulativeDifference = -10000.0;
        
        // Act
        $result = $this->engine->hasDeferredTaxLiability($cumulativeDifference);
        
        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDeferredTaxAsset_withNegativeDifference_returnsTrue(): void
    {
        // Arrange
        $cumulativeDifference = -10000.0;
        
        // Act
        $result = $this->engine->hasDeferredTaxAsset($cumulativeDifference);
        
        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hasDeferredTaxAsset_withPositiveDifference_returnsFalse(): void
    {
        // Arrange
        $cumulativeDifference = 10000.0;
        
        // Act
        $result = $this->engine->hasDeferredTaxAsset($cumulativeDifference);
        
        // Assert
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCommonCombinations_returnsArrayOfCombinations(): void
    {
        // Act
        $result = TaxBookDepreciationEngine::getCommonCombinations();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sl_to_macrs_5', $result);
        $this->assertArrayHasKey('sl_to_macrs_7', $result);
        $this->assertArrayHasKey('ddb_to_macrs_5', $result);
        $this->assertArrayHasKey('ddb_to_macrs_7', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_withDefaultTaxRate_usesDefaultRate(): void
    {
        // Arrange
        $engine = new TaxBookDepreciationEngine();
        $cost = 100000.0;
        $salvageValue = 10000.0;
        $bookMethod = new StraightLineDepreciationMethod();
        $taxMethod = new DoubleDecliningDepreciationMethod();
        $startDate = new DateTimeImmutable('2026-01-01');
        $endDate = new DateTimeImmutable('2026-01-31');
        
        // Act
        $result = $engine->calculate(
            $cost,
            $salvageValue,
            $bookMethod,
            $taxMethod,
            $startDate,
            $endDate
        );
        
        // Assert
        $this->assertEquals(0.21, $result->taxRate);
    }
}
