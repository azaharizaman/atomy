<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\Treasury\Entities\IntercompanyLoan;
use Nexus\Common\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class IntercompanyLoanTest extends TestCase
{
    public function test_creates_loan_with_required_fields(): void
    {
        $now = new DateTimeImmutable();
        
        $loan = new IntercompanyLoan(
            id: 'TRE-ICL-001',
            tenantId: 'tenant-001',
            fromEntityId: 'ENTITY-A',
            toEntityId: 'ENTITY-B',
            loanType: 'intercompany',
            principalAmount: Money::of(500000, 'USD'),
            interestRate: 3.5,
            outstandingBalance: Money::of(500000, 'USD'),
            startDate: $now,
            maturityDate: $now->modify('+1 year'),
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: 'REF-001',
            notes: 'Intercompany loan',
            createdAt: $now,
            updatedAt: $now
        );
        
        $this->assertEquals('TRE-ICL-001', $loan->getId());
        $this->assertEquals('tenant-001', $loan->getTenantId());
        $this->assertEquals('ENTITY-A', $loan->getFromEntityId());
        $this->assertEquals('ENTITY-B', $loan->getToEntityId());
        $this->assertTrue($loan->isActive());
    }

    public function test_is_active_returns_true_when_outstanding_balance_not_zero(): void
    {
        $loan = $this->createLoanWithBalance(Money::of(100000, 'USD'));
        
        $this->assertTrue($loan->isActive());
    }

    public function test_is_active_returns_false_when_outstanding_balance_is_zero(): void
    {
        $loan = $this->createLoanWithBalance(Money::of(0, 'USD'));
        
        $this->assertFalse($loan->isActive());
    }

    public function test_is_overdue_returns_true_when_past_maturity_and_has_balance(): void
    {
        $pastMaturity = new DateTimeImmutable('-1 day');
        
        $loan = $this->createLoanWithMaturity($pastMaturity, Money::of(100000, 'USD'));
        
        $this->assertTrue($loan->isOverdue());
    }

    public function test_is_overdue_returns_false_when_past_maturity_but_no_balance(): void
    {
        $pastMaturity = new DateTimeImmutable('-1 day');
        
        $loan = $this->createLoanWithMaturity($pastMaturity, Money::of(0, 'USD'));
        
        $this->assertFalse($loan->isOverdue());
    }

    public function test_is_overdue_returns_false_when_no_maturity_date(): void
    {
        $loan = $this->createLoanWithMaturity(null, Money::of(100000, 'USD'));
        
        $this->assertFalse($loan->isOverdue());
    }

    public function test_get_days_outstanding_returns_correct_value(): void
    {
        $startDate = new DateTimeImmutable('-30 days');
        
        $loan = $this->createLoanWithStartDate($startDate);
        
        $this->assertGreaterThanOrEqual(29, $loan->getDaysOutstanding());
    }

    private function createLoanWithBalance(Money $balance): IntercompanyLoan
    {
        $now = new DateTimeImmutable();
        
        return new IntercompanyLoan(
            id: 'TRE-ICL-001',
            tenantId: 'tenant-001',
            fromEntityId: 'ENTITY-A',
            toEntityId: 'ENTITY-B',
            loanType: 'intercompany',
            principalAmount: Money::of(500000, 'USD'),
            interestRate: 3.5,
            outstandingBalance: $balance,
            startDate: $now,
            maturityDate: $now->modify('+1 year'),
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: null,
            notes: null,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function createLoanWithMaturity(?DateTimeImmutable $maturity, Money $balance): IntercompanyLoan
    {
        $now = new DateTimeImmutable();
        
        return new IntercompanyLoan(
            id: 'TRE-ICL-001',
            tenantId: 'tenant-001',
            fromEntityId: 'ENTITY-A',
            toEntityId: 'ENTITY-B',
            loanType: 'intercompany',
            principalAmount: Money::of(500000, 'USD'),
            interestRate: 3.5,
            outstandingBalance: $balance,
            startDate: $now->modify('-1 year'),
            maturityDate: $maturity,
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: null,
            notes: null,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function createLoanWithStartDate(DateTimeImmutable $startDate): IntercompanyLoan
    {
        $now = new DateTimeImmutable();
        
        return new IntercompanyLoan(
            id: 'TRE-ICL-001',
            tenantId: 'tenant-001',
            fromEntityId: 'ENTITY-A',
            toEntityId: 'ENTITY-B',
            loanType: 'intercompany',
            principalAmount: Money::of(500000, 'USD'),
            interestRate: 3.5,
            outstandingBalance: Money::of(500000, 'USD'),
            startDate: $startDate,
            maturityDate: $now->modify('+1 year'),
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: null,
            notes: null,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
