<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Enums;

use Nexus\Treasury\Enums\InvestmentType;
use PHPUnit\Framework\TestCase;

final class InvestmentTypeTest extends TestCase
{
    public function test_label_returns_correct_string(): void
    {
        $this->assertEquals('Fixed Deposit', InvestmentType::FIXED_DEPOSIT->label());
        $this->assertEquals('Money Market', InvestmentType::MONEY_MARKET->label());
        $this->assertEquals('Treasury Bill', InvestmentType::TREASURY_BILL->label());
        $this->assertEquals('Commercial Paper', InvestmentType::COMMERCIAL_PAPER->label());
        $this->assertEquals('Term Deposit', InvestmentType::TERM_DEPOSIT->label());
        $this->assertEquals('Overnight', InvestmentType::OVERNIGHT->label());
    }

    public function test_is_short_term_returns_true_for_short_term(): void
    {
        $this->assertTrue(InvestmentType::OVERNIGHT->isShortTerm());
        $this->assertTrue(InvestmentType::MONEY_MARKET->isShortTerm());
        $this->assertTrue(InvestmentType::COMMERCIAL_PAPER->isShortTerm());
        $this->assertFalse(InvestmentType::FIXED_DEPOSIT->isShortTerm());
        $this->assertFalse(InvestmentType::TERM_DEPOSIT->isShortTerm());
        $this->assertFalse(InvestmentType::TREASURY_BILL->isShortTerm());
    }

    public function test_is_government_backed_returns_true_for_treasury_bills(): void
    {
        $this->assertTrue(InvestmentType::TREASURY_BILL->isGovernmentBacked());
        $this->assertFalse(InvestmentType::FIXED_DEPOSIT->isGovernmentBacked());
        $this->assertFalse(InvestmentType::MONEY_MARKET->isGovernmentBacked());
    }

    public function test_typical_maturity_days(): void
    {
        $this->assertEquals(1, InvestmentType::OVERNIGHT->typicalMaturityDays());
        $this->assertEquals(30, InvestmentType::MONEY_MARKET->typicalMaturityDays());
        $this->assertEquals(90, InvestmentType::COMMERCIAL_PAPER->typicalMaturityDays());
        $this->assertEquals(180, InvestmentType::TREASURY_BILL->typicalMaturityDays());
        $this->assertEquals(365, InvestmentType::TERM_DEPOSIT->typicalMaturityDays());
        $this->assertEquals(365, InvestmentType::FIXED_DEPOSIT->typicalMaturityDays());
    }
}
