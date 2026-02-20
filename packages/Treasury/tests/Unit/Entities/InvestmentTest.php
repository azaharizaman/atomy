<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\Treasury\Entities\Investment;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;
use Nexus\Common\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class InvestmentTest extends TestCase
{
    public function test_creates_investment_with_required_fields(): void
    {
        $now = new DateTimeImmutable();
        $maturity = $now->modify('+1 year');
        
        $investment = new Investment(
            id: 'TRE-INV-001',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::FIXED_DEPOSIT,
            name: 'Term Deposit 12M',
            description: 'Bank deposit',
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: $maturity,
            investmentDate: $now,
            status: InvestmentStatus::ACTIVE,
            maturityAmount: Money::of(105000, 'USD'),
            accruedInterest: Money::of(0, 'USD'),
            bankAccountId: 'BANK-001',
            referenceNumber: 'REF-001',
            createdAt: $now,
            updatedAt: $now
        );
        
        $this->assertEquals('TRE-INV-001', $investment->getId());
        $this->assertEquals('tenant-001', $investment->getTenantId());
        $this->assertEquals(InvestmentType::FIXED_DEPOSIT, $investment->getInvestmentType());
        $this->assertEquals('Term Deposit 12M', $investment->getName());
        $this->assertTrue($investment->isActive());
    }

    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $investment = $this->createInvestment(InvestmentStatus::ACTIVE);
        
        $this->assertTrue($investment->isActive());
    }

    public function test_is_matured_returns_true_when_status_is_matured(): void
    {
        $investment = $this->createInvestment(InvestmentStatus::MATURED);
        
        $this->assertTrue($investment->isMatured());
    }

    public function test_is_pending_returns_true_when_status_is_pending(): void
    {
        $investment = $this->createInvestment(InvestmentStatus::PENDING);
        
        $this->assertTrue($investment->isPending());
    }

    public function test_get_days_to_maturity_returns_correct_days(): void
    {
        $futureDate = new DateTimeImmutable('+30 days');
        
        $investment = $this->createInvestment(InvestmentStatus::ACTIVE, $futureDate);
        
        $this->assertGreaterThan(20, $investment->getDaysToMaturity());
    }

    public function test_get_days_to_maturity_returns_zero_when_past_maturity(): void
    {
        $pastDate = new DateTimeImmutable('-1 day');
        
        $investment = $this->createInvestment(InvestmentStatus::ACTIVE, $pastDate);
        
        $this->assertEquals(0, $investment->getDaysToMaturity());
    }

    public function test_get_duration_days_returns_correct_value(): void
    {
        $now = new DateTimeImmutable();
        $maturity = $now->modify('+365 days');
        
        $investment = $this->createInvestment(InvestmentStatus::ACTIVE, $maturity, $now);
        
        $this->assertEquals(365, $investment->getDurationDays());
    }

    private function createInvestment(
        InvestmentStatus $status,
        ?DateTimeImmutable $maturityDate = null,
        ?DateTimeImmutable $investmentDate = null
    ): Investment {
        $now = new DateTimeImmutable();
        $maturity = $maturityDate ?? $now->modify('+1 year');
        $invested = $investmentDate ?? $now;
        
        return new Investment(
            id: 'TRE-INV-001',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::FIXED_DEPOSIT,
            name: 'Test Investment',
            description: null,
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: $maturity,
            investmentDate: $invested,
            status: $status,
            maturityAmount: Money::of(105000, 'USD'),
            accruedInterest: Money::of(0, 'USD'),
            bankAccountId: 'BANK-001',
            referenceNumber: null,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
