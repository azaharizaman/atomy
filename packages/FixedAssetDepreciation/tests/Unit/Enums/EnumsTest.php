<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;

/**
 * Test cases for Enum methods in FixedAssetDepreciation package.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Enums
 */
final class EnumsTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_isAccelerated_withAcceleratedMethods_returnsTrue(): void
    {
        // Double Declining is accelerated
        $this->assertTrue(DepreciationMethodType::DOUBLE_DECLINING->isAccelerated());
        
        // Declining 150 is accelerated
        $this->assertTrue(DepreciationMethodType::DECLINING_150->isAccelerated());
        
        // Sum of Years is accelerated
        $this->assertTrue(DepreciationMethodType::SUM_OF_YEARS->isAccelerated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_isAccelerated_withNonAcceleratedMethods_returnsFalse(): void
    {
        // Straight Line is not accelerated
        $this->assertFalse(DepreciationMethodType::STRAIGHT_LINE->isAccelerated());
        $this->assertFalse(DepreciationMethodType::STRAIGHT_LINE_DAILY->isAccelerated());
        
        // Units of Production is not accelerated (depends on usage)
        $this->assertFalse(DepreciationMethodType::UNITS_OF_PRODUCTION->isAccelerated());
        
        // Other enterprise methods
        $this->assertFalse(DepreciationMethodType::ANNUITY->isAccelerated());
        $this->assertFalse(DepreciationMethodType::MACRS->isAccelerated());
        $this->assertFalse(DepreciationMethodType::BONUS->isAccelerated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_requiresUsefulLife_withUnitsOfProduction_returnsFalse(): void
    {
        // Units of Production calculates based on units, not time
        $this->assertFalse(DepreciationMethodType::UNITS_OF_PRODUCTION->requiresUsefulLife());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_requiresUsefulLife_withOtherMethods_returnsTrue(): void
    {
        $this->assertTrue(DepreciationMethodType::STRAIGHT_LINE->requiresUsefulLife());
        $this->assertTrue(DepreciationMethodType::DOUBLE_DECLINING->requiresUsefulLife());
        $this->assertTrue(DepreciationMethodType::SUM_OF_YEARS->requiresUsefulLife());
        $this->assertTrue(DepreciationMethodType::DECLINING_150->requiresUsefulLife());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_supportsSalvageValue_withDecliningMethods_returnsFalse(): void
    {
        // Double Declining and Declining 150 don't support salvage value in calculation
        $this->assertFalse(DepreciationMethodType::DOUBLE_DECLINING->supportsSalvageValue());
        $this->assertFalse(DepreciationMethodType::DECLINING_150->supportsSalvageValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_supportsSalvageValue_withOtherMethods_returnsTrue(): void
    {
        $this->assertTrue(DepreciationMethodType::STRAIGHT_LINE->supportsSalvageValue());
        $this->assertTrue(DepreciationMethodType::STRAIGHT_LINE_DAILY->supportsSalvageValue());
        $this->assertTrue(DepreciationMethodType::SUM_OF_YEARS->supportsSalvageValue());
        $this->assertTrue(DepreciationMethodType::UNITS_OF_PRODUCTION->supportsSalvageValue());
        $this->assertTrue(DepreciationMethodType::ANNUITY->supportsSalvageValue());
        $this->assertTrue(DepreciationMethodType::MACRS->supportsSalvageValue());
        $this->assertTrue(DepreciationMethodType::BONUS->supportsSalvageValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationMethodType_getTierLevel_returnsCorrectTiers(): void
    {
        // Tier 1 - Basic methods
        $this->assertEquals(1, DepreciationMethodType::STRAIGHT_LINE->getTierLevel());
        $this->assertEquals(1, DepreciationMethodType::STRAIGHT_LINE_DAILY->getTierLevel());
        
        // Tier 2 - Advanced methods
        $this->assertEquals(2, DepreciationMethodType::DOUBLE_DECLINING->getTierLevel());
        $this->assertEquals(2, DepreciationMethodType::DECLINING_150->getTierLevel());
        $this->assertEquals(2, DepreciationMethodType::SUM_OF_YEARS->getTierLevel());
        
        // Tier 3 - Enterprise methods
        $this->assertEquals(3, DepreciationMethodType::UNITS_OF_PRODUCTION->getTierLevel());
        $this->assertEquals(3, DepreciationMethodType::ANNUITY->getTierLevel());
        $this->assertEquals(3, DepreciationMethodType::MACRS->getTierLevel());
        $this->assertEquals(3, DepreciationMethodType::BONUS->getTierLevel());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationStatus_canBePosted_withCalculatedStatus_returnsTrue(): void
    {
        // Only CALCULATED status can be posted
        $this->assertTrue(DepreciationStatus::CALCULATED->canBePosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationStatus_canBePosted_withOtherStatuses_returnsFalse(): void
    {
        $this->assertFalse(DepreciationStatus::POSTED->canBePosted());
        $this->assertFalse(DepreciationStatus::REVERSED->canBePosted());
        $this->assertFalse(DepreciationStatus::ADJUSTED->canBePosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationStatus_canBeReversed_withPostedStatus_returnsTrue(): void
    {
        // Only POSTED status can be reversed
        $this->assertTrue(DepreciationStatus::POSTED->canBeReversed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationStatus_canBeReversed_withOtherStatuses_returnsFalse(): void
    {
        $this->assertFalse(DepreciationStatus::CALCULATED->canBeReversed());
        $this->assertFalse(DepreciationStatus::REVERSED->canBeReversed());
        $this->assertFalse(DepreciationStatus::ADJUSTED->canBeReversed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationStatus_isFinal_withReversedStatus_returnsTrue(): void
    {
        // REVERSED is a final status
        $this->assertTrue(DepreciationStatus::REVERSED->isFinal());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationStatus_isFinal_withOtherStatuses_returnsFalse(): void
    {
        $this->assertFalse(DepreciationStatus::CALCULATED->isFinal());
        $this->assertFalse(DepreciationStatus::POSTED->isFinal());
        $this->assertFalse(DepreciationStatus::ADJUSTED->isFinal());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationType_getDisplayName_withBook_returnsCorrectName(): void
    {
        $this->assertEquals('Book Depreciation', DepreciationType::BOOK->getDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationType_getDisplayName_withTax_returnsCorrectName(): void
    {
        $this->assertEquals('Tax Depreciation', DepreciationType::TAX->getDisplayName());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationType_requiresGlPosting_withBook_returnsTrue(): void
    {
        $this->assertTrue(DepreciationType::BOOK->requiresGlPosting());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationType_requiresGlPosting_withTax_returnsFalse(): void
    {
        $this->assertFalse(DepreciationType::TAX->requiresGlPosting());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function prorateConvention_usesDailyCalculation_withDaily_returnsTrue(): void
    {
        $this->assertTrue(ProrateConvention::DAILY->usesDailyCalculation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function prorateConvention_usesDailyCalculation_withOtherConventions_returnsFalse(): void
    {
        $this->assertFalse(ProrateConvention::FULL_MONTH->usesDailyCalculation());
        $this->assertFalse(ProrateConvention::NONE->usesDailyCalculation());
        $this->assertFalse(ProrateConvention::HALF_YEAR->usesDailyCalculation());
        $this->assertFalse(ProrateConvention::MID_QUARTER->usesDailyCalculation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function prorateConvention_getDefaultMonthlyFactor_returnsCorrectFactors(): void
    {
        $this->assertEquals(1.0, ProrateConvention::FULL_MONTH->getDefaultMonthlyFactor());
        $this->assertEquals(0.5, ProrateConvention::DAILY->getDefaultMonthlyFactor());
        $this->assertEquals(0.5, ProrateConvention::HALF_YEAR->getDefaultMonthlyFactor());
        $this->assertEquals(0.5, ProrateConvention::MID_QUARTER->getDefaultMonthlyFactor());
        $this->assertEquals(1.0, ProrateConvention::NONE->getDefaultMonthlyFactor());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revaluationType_isIncrease_withIncrement_returnsTrue(): void
    {
        $this->assertTrue(RevaluationType::INCREMENT->isIncrease());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revaluationType_isIncrease_withDecrement_returnsFalse(): void
    {
        $this->assertFalse(RevaluationType::DECREMENT->isIncrease());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revaluationType_getGlAccountType_withIncrement_returnsRevaluationReserve(): void
    {
        $this->assertEquals('revaluation_reserve', RevaluationType::INCREMENT->getGlAccountType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function revaluationType_getGlAccountType_withDecrement_returnsDepreciationExpense(): void
    {
        $this->assertEquals('depreciation_expense', RevaluationType::DECREMENT->getGlAccountType());
    }
}
