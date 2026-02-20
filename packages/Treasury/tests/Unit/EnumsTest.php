<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit;

use Nexus\Treasury\Enums\ForecastScenario;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function testInvestmentTypeLabel(): void
    {
        $this->assertEquals('Money Market', InvestmentType::MONEY_MARKET->label());
        $this->assertEquals('Term Deposit', InvestmentType::TERM_DEPOSIT->label());
        $this->assertEquals('Treasury Bill', InvestmentType::TREASURY_BILL->label());
        $this->assertEquals('Commercial Paper', InvestmentType::COMMERCIAL_PAPER->label());
        $this->assertEquals('Fixed Deposit', InvestmentType::FIXED_DEPOSIT->label());
        $this->assertEquals('Overnight', InvestmentType::OVERNIGHT->label());
    }

    public function testInvestmentTypeIsShortTerm(): void
    {
        $this->assertTrue(InvestmentType::OVERNIGHT->isShortTerm());
        $this->assertTrue(InvestmentType::MONEY_MARKET->isShortTerm());
        $this->assertTrue(InvestmentType::TERM_DEPOSIT->isShortTerm());
        
        $this->assertFalse(InvestmentType::TREASURY_BILL->isShortTerm());
        $this->assertFalse(InvestmentType::COMMERCIAL_PAPER->isShortTerm());
        $this->assertFalse(InvestmentType::FIXED_DEPOSIT->isShortTerm());
    }

    public function testInvestmentStatusLabel(): void
    {
        $this->assertEquals('Active', InvestmentStatus::ACTIVE->label());
        $this->assertEquals('Matured', InvestmentStatus::MATURED->label());
        $this->assertEquals('Cancelled', InvestmentStatus::CANCELLED->label());
        $this->assertEquals('Rolled Over', InvestmentStatus::ROLLED_OVER->label());
    }

    public function testInvestmentStatusIsActive(): void
    {
        $this->assertTrue(InvestmentStatus::ACTIVE->isActive());
        $this->assertFalse(InvestmentStatus::MATURED->isActive());
        $this->assertFalse(InvestmentStatus::CANCELLED->isActive());
        $this->assertFalse(InvestmentStatus::ROLLED_OVER->isActive());
    }

    public function testForecastScenarioLabel(): void
    {
        $this->assertEquals('Best Case', ForecastScenario::BEST->label());
        $this->assertEquals('Expected Case', ForecastScenario::EXPECTED->label());
        $this->assertEquals('Worst Case', ForecastScenario::WORST->label());
        $this->assertEquals('Base Case', ForecastScenario::BASE->label());
    }

    public function testForecastScenarioMultiplier(): void
    {
        $this->assertEquals(1.1, ForecastScenario::BEST->multiplier());
        $this->assertEquals(1.0, ForecastScenario::EXPECTED->multiplier());
        $this->assertEquals(0.9, ForecastScenario::WORST->multiplier());
        $this->assertEquals(1.0, ForecastScenario::BASE->multiplier());
    }
}
