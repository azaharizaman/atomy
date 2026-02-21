<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Nexus\FixedAssetDepreciation\Services\DepreciationMethodFactory;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Exceptions\InvalidDepreciationMethodException;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;

/**
 * Test cases for DepreciationMethodFactory service.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Services
 */
final class DepreciationMethodFactoryTest extends TestCase
{
    private DepreciationMethodFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new DepreciationMethodFactory();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withStraightLine_returnsCorrectMethod(): void
    {
        // Act
        $result = $this->factory->create(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
        $this->assertEquals(DepreciationMethodType::STRAIGHT_LINE, $result->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withStraightLineDaily_returnsCorrectMethod(): void
    {
        // Act
        $result = $this->factory->create(DepreciationMethodType::STRAIGHT_LINE_DAILY);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
        $this->assertEquals(DepreciationMethodType::STRAIGHT_LINE, $result->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withDoubleDeclining_returnsCorrectMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');
        
        // Act
        $result = $factory->create(DepreciationMethodType::DOUBLE_DECLINING);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
        $this->assertEquals(DepreciationMethodType::DOUBLE_DECLINING, $result->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withDeclining150_returnsCorrectMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');
        
        // Act
        $result = $factory->create(DepreciationMethodType::DECLINING_150);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withSumOfYears_returnsCorrectMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');
        
        // Act
        $result = $factory->create(DepreciationMethodType::SUM_OF_YEARS);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
        $this->assertEquals(DepreciationMethodType::SUM_OF_YEARS, $result->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withUnitsOfProduction_returnsCorrectMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('enterprise');
        
        // Act
        $result = $factory->create(DepreciationMethodType::UNITS_OF_PRODUCTION);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
        $this->assertEquals(DepreciationMethodType::UNITS_OF_PRODUCTION, $result->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withAnnuity_throwsException(): void
    {
        // Assert
        $this->expectException(InvalidDepreciationMethodException::class);

        // Act
        $this->factory->create(DepreciationMethodType::ANNUITY);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withMacrs_throwsException(): void
    {
        // Assert
        $this->expectException(InvalidDepreciationMethodException::class);

        // Act
        $this->factory->create(DepreciationMethodType::MACRS);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withBonus_throwsException(): void
    {
        // Assert
        $this->expectException(InvalidDepreciationMethodException::class);

        // Act
        $this->factory->create(DepreciationMethodType::BONUS);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isMethodAvailable_straightLineInBasicTier_returnsTrue(): void
    {
        // Act
        $result = $this->factory->isMethodAvailable(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isMethodAvailable_doubleDecliningInBasicTier_returnsFalse(): void
    {
        // Act
        $result = $this->factory->isMethodAvailable(DepreciationMethodType::DOUBLE_DECLINING);

        // Assert
        // In basic tier, only tier 1 methods are available
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAvailableMethods_inBasicTier_returnsOnlyTier1Methods(): void
    {
        // Act
        $result = $this->factory->getAvailableMethods();

        // Assert
        $this->assertContains(DepreciationMethodType::STRAIGHT_LINE, $result);
        $this->assertContains(DepreciationMethodType::STRAIGHT_LINE_DAILY, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_forStraightLine_returns1(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->assertEquals(1, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_forDoubleDeclining_returns2(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::DOUBLE_DECLINING);

        // Assert
        $this->assertEquals(2, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_forUnitsOfProduction_returns3(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::UNITS_OF_PRODUCTION);

        // Assert
        $this->assertEquals(3, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCurrentTier_returnsBasic(): void
    {
        // Act
        $result = $this->factory->getCurrentTier();

        // Assert
        $this->assertEquals('basic', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCurrentTierLevel_returns1(): void
    {
        // Act
        $result = $this->factory->getCurrentTierLevel();

        // Assert
        $this->assertEquals(1, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withAdvancedTier_includesAcceleratedMethods(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');

        // Act
        $result = $factory->isMethodAvailable(DepreciationMethodType::DOUBLE_DECLINING);

        // Assert
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withEnterpriseTier_includesAllMethods(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('enterprise');

        // Act
        $straightLineAvailable = $factory->isMethodAvailable(DepreciationMethodType::STRAIGHT_LINE);
        $doubleDecliningAvailable = $factory->isMethodAvailable(DepreciationMethodType::DOUBLE_DECLINING);
        $uopAvailable = $factory->isMethodAvailable(DepreciationMethodType::UNITS_OF_PRODUCTION);

        // Assert
        $this->assertTrue($straightLineAvailable);
        $this->assertTrue($doubleDecliningAvailable);
        $this->assertTrue($uopAvailable);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withInvalidTier_throwsException(): void
    {
        // Assert
        $this->expectException(InvalidDepreciationMethodException::class);

        // Act
        $this->factory->create(DepreciationMethodType::ANNUITY);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_multipleTimes_returnsDifferentInstances(): void
    {
        // Act
        $result1 = $this->factory->create(DepreciationMethodType::STRAIGHT_LINE);
        $result2 = $this->factory->create(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->assertNotSame($result1, $result2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withConfiguration_passesConfigToMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('basic', [
            'straight_line' => ['prorate_daily' => true]
        ]);

        // Act
        $method = $factory->create(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $method);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_doubleDecliningWithCustomFactor_createsWithFactor(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced', [
            'double_declining' => ['factor' => 1.5]
        ]);

        // Act
        $method = $factory->create(DepreciationMethodType::DOUBLE_DECLINING);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $method);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_declining150_returnsCorrectMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');
        
        // Act
        $result = $factory->create(DepreciationMethodType::DECLINING_150);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
        $this->assertEquals(DepreciationMethodType::DECLINING_150, $result->getType());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_sumOfYearsWithAdvancedTier_returnsCorrectMethod(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');
        
        // Act
        $result = $factory->create(DepreciationMethodType::SUM_OF_YEARS);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isMethodAvailable_withInvalidTierLevel_returnsFalse(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('invalid-tier');
        
        // Act
        $result = $factory->isMethodAvailable(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        // Should default to basic tier (level 1)
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_withStraightLineDaily_returns1(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::STRAIGHT_LINE_DAILY);

        // Assert
        $this->assertEquals(1, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_withAnnuity_returns3(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::ANNUITY);

        // Assert
        $this->assertEquals(3, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_withMacrs_returns3(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::MACRS);

        // Assert
        $this->assertEquals(3, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getMethodTier_withBonus_returns3(): void
    {
        // Act
        $result = $this->factory->getMethodTier(DepreciationMethodType::BONUS);

        // Assert
        $this->assertEquals(3, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withConfiguration_prorateDaily(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('basic', [
            'straight_line' => ['prorate_daily' => true]
        ]);

        // Act
        $method = $factory->create(DepreciationMethodType::STRAIGHT_LINE);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $method);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withDeclining150CustomConfig_createsWithConfig(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced', [
            'declining_150' => ['switch_to_sl' => false]
        ]);

        // Act
        $method = $factory->create(DepreciationMethodType::DECLINING_150);

        // Assert
        $this->assertInstanceOf(DepreciationMethodInterface::class, $method);
    }

    #[Test]
    public function create_withTierNotAvailable_throwsException(): void
    {
        // Arrange - basic tier but trying to create an advanced method
        $factory = new DepreciationMethodFactory('basic');

        // Assert - should throw because double declining requires advanced tier
        $this->expectException(InvalidDepreciationMethodException::class);

        // Act
        $factory->create(DepreciationMethodType::DOUBLE_DECLINING);
    }

    #[Test]
    public function create_withEnterpriseTierRequired_throwsExceptionInBasic(): void
    {
        // Arrange - basic tier but trying to create an enterprise method
        $factory = new DepreciationMethodFactory('basic');

        // Assert - should throw because units of production requires enterprise tier
        $this->expectException(InvalidDepreciationMethodException::class);

        // Act
        $factory->create(DepreciationMethodType::UNITS_OF_PRODUCTION);
    }

    #[Test]
    public function getCurrentTierLevel_withInvalidTier_returnsDefaultLevel(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('nonexistent-tier');

        // Act
        $result = $factory->getCurrentTierLevel();

        // Assert - should default to level 1 (basic)
        $this->assertEquals(1, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAvailableMethods_inAdvancedTier_returnsTier1And2Methods(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');

        // Act
        $result = $factory->getAvailableMethods();

        // Assert - should include tier 1 and 2 methods
        $this->assertContains(DepreciationMethodType::STRAIGHT_LINE, $result);
        $this->assertContains(DepreciationMethodType::DOUBLE_DECLINING, $result);
        $this->assertContains(DepreciationMethodType::SUM_OF_YEARS, $result);
        // Tier 3 methods should not be included
        $this->assertNotContains(DepreciationMethodType::UNITS_OF_PRODUCTION, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAvailableMethods_inEnterpriseTier_returnsAllMethods(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('enterprise');

        // Act
        $result = $factory->getAvailableMethods();

        // Assert - should include all methods
        $this->assertCount(9, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isMethodAvailable_withEnterpriseTier_returnsTrueForAll(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('enterprise');

        // Act & Assert
        $this->assertTrue($factory->isMethodAvailable(DepreciationMethodType::ANNUITY));
        $this->assertTrue($factory->isMethodAvailable(DepreciationMethodType::MACRS));
        $this->assertTrue($factory->isMethodAvailable(DepreciationMethodType::BONUS));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCurrentTier_withAdvancedTier_returnsAdvanced(): void
    {
        // Arrange
        $factory = new DepreciationMethodFactory('advanced');

        // Act
        $result = $factory->getCurrentTier();

        // Assert
        $this->assertEquals('advanced', $result);
    }
}
