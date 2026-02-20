<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\IntercompanyLoanPersistInterface;
use Nexus\Treasury\Contracts\IntercompanyLoanQueryInterface;
use Nexus\Treasury\Contracts\IntercompanyTreasuryInterface;
use Nexus\Treasury\Entities\IntercompanyLoan;
use Nexus\Treasury\Services\IntercompanyTreasuryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class IntercompanyTreasuryServiceTest extends TestCase
{
    private IntercompanyTreasuryService $service;
    private MockObject $query;
    private MockObject $persist;

    protected function setUp(): void
    {
        $this->query = $this->createMock(IntercompanyLoanQueryInterface::class);
        $this->persist = $this->createMock(IntercompanyLoanPersistInterface::class);

        $this->service = new IntercompanyTreasuryService(
            $this->query,
            $this->persist,
            new NullLogger()
        );
    }

    public function test_record_loan_creates_new_loan(): void
    {
        $startDate = new DateTimeImmutable();

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->recordLoan(
            tenantId: 'tenant-001',
            fromEntityId: 'entity-001',
            toEntityId: 'entity-002',
            principal: Money::of(50000, 'USD'),
            interestRate: 5.0,
            startDate: $startDate,
            maturityDate: new DateTimeImmutable('+90 days'),
            referenceNumber: 'REF-001',
            notes: 'Test loan'
        );

        $this->assertStringStartsWith('TRE-ICL-', $result->getId());
        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals('entity-001', $result->getFromEntityId());
        $this->assertEquals('entity-002', $result->getToEntityId());
    }

    public function test_record_loan_without_optional_fields(): void
    {
        $startDate = new DateTimeImmutable();

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->recordLoan(
            tenantId: 'tenant-001',
            fromEntityId: 'entity-001',
            toEntityId: 'entity-002',
            principal: Money::of(50000, 'USD'),
            interestRate: 5.0,
            startDate: $startDate
        );

        $this->assertNull($result->getMaturityDate());
        $this->assertNull($result->getReferenceNumber());
        $this->assertNull($result->getNotes());
    }

    public function test_record_repayment_reduces_outstanding_balance(): void
    {
        $loan = $this->createActiveLoan();
        $repaymentDate = new DateTimeImmutable('+30 days');

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->recordRepayment(
            'TRE-ICL-001',
            Money::of(10000, 'USD'),
            $repaymentDate
        );

        $this->assertLessThan(50000, $result->getOutstandingBalance()->getAmount());
    }

    public function test_record_repayment_without_date_uses_today(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->recordRepayment('TRE-ICL-001', Money::of(5000, 'USD'));

        $this->assertCount(1, $result->getPaymentSchedule());
    }

    public function test_record_repayment_handles_overpayment(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->recordRepayment('TRE-ICL-001', Money::of(100000, 'USD'));

        $this->assertEquals(0, $result->getOutstandingBalance()->getAmount());
    }

    public function test_calculate_interest_returns_calculated_interest(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $result = $this->service->calculateInterest('TRE-ICL-001');

        $this->assertInstanceOf(Money::class, $result);
    }

    public function test_calculate_interest_with_custom_date(): void
    {
        $loan = $this->createActiveLoan();
        $asOfDate = new DateTimeImmutable('+45 days');

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $result = $this->service->calculateInterest('TRE-ICL-001', $asOfDate);

        $this->assertInstanceOf(Money::class, $result);
    }

    public function test_get_returns_loan_by_id(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $result = $this->service->get('TRE-ICL-001');

        $this->assertSame($loan, $result);
    }

    public function test_get_active_returns_active_loans(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$loan]);

        $result = $this->service->getActive('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_get_overdue_returns_overdue_loans(): void
    {
        $loan = $this->createOverdueLoan();

        $this->query
            ->expects($this->once())
            ->method('findOverdueByTenantId')
            ->with('tenant-001')
            ->willReturn([$loan]);

        $result = $this->service->getOverdue('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_get_loans_between_entities(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findBetweenEntities')
            ->with('entity-001', 'entity-002')
            ->willReturn([$loan]);

        $result = $this->service->getLoansBetweenEntities('entity-001', 'entity-002');

        $this->assertCount(1, $result);
    }

    public function test_get_loans_by_from_entity(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findByFromEntity')
            ->with('entity-001')
            ->willReturn([$loan]);

        $result = $this->service->getLoansByFromEntity('entity-001');

        $this->assertCount(1, $result);
    }

    public function test_get_loans_by_to_entity(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findByToEntity')
            ->with('entity-002')
            ->willReturn([$loan]);

        $result = $this->service->getLoansByToEntity('entity-002');

        $this->assertCount(1, $result);
    }

    public function test_get_intercompany_position_calculates_position(): void
    {
        $loanFrom = $this->createActiveLoan();
        $loanTo = $this->createActiveLoanAsReceived();

        $this->query
            ->expects($this->once())
            ->method('findByFromEntity')
            ->with('entity-001')
            ->willReturn([$loanFrom]);

        $this->query
            ->expects($this->once())
            ->method('findByToEntity')
            ->with('entity-001')
            ->willReturn([$loanTo]);

        $this->query
            ->expects($this->exactly(2))
            ->method('findOrFail')
            ->willReturnOnConsecutiveCalls($loanFrom, $loanTo);

        $result = $this->service->getIntercompanyPosition('entity-001');

        $this->assertEquals('entity-001', $result['entity_id']);
        $this->assertArrayHasKey('total_lent', $result);
        $this->assertArrayHasKey('total_borrowed', $result);
        $this->assertArrayHasKey('net_position', $result);
        $this->assertArrayHasKey('interest_receivable', $result);
        $this->assertArrayHasKey('interest_payable', $result);
        $this->assertArrayHasKey('net_interest', $result);
    }

    public function test_get_intercompany_summary_returns_summary_data(): void
    {
        $loan = $this->createActiveLoan();

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$loan]);

        $this->query
            ->expects($this->once())
            ->method('findOverdueByTenantId')
            ->with('tenant-001')
            ->willReturn([]);

        $this->query
            ->expects($this->once())
            ->method('sumOutstandingByTenantId')
            ->with('tenant-001')
            ->willReturn(50000.0);

        $result = $this->service->getIntercompanySummary('tenant-001');

        $this->assertEquals(1, $result['active_count']);
        $this->assertEquals(0, $result['overdue_count']);
        $this->assertEquals(50000.0, $result['total_outstanding']);
        $this->assertArrayHasKey('by_entity', $result);
    }

    public function test_accrue_interest_updates_loan(): void
    {
        $loan = $this->createActiveLoanWithEarlierStart();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->accrueInterest('TRE-ICL-001');

        $this->assertGreaterThan(0, $result->getAccruedInterest()->getAmount());
    }

    public function test_accrue_interest_with_custom_date(): void
    {
        $loan = $this->createActiveLoan();
        $asOfDate = new DateTimeImmutable('+60 days');

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-ICL-001')
            ->willReturn($loan);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->accrueInterest('TRE-ICL-001', $asOfDate);

        $this->assertGreaterThan(0, $result->getAccruedInterest()->getAmount());
    }

    private function createActiveLoanWithEarlierStart(): IntercompanyLoan
    {
        $now = new DateTimeImmutable();
        $startDate = new DateTimeImmutable('-30 days');

        return new IntercompanyLoan(
            id: 'TRE-ICL-001',
            tenantId: 'tenant-001',
            fromEntityId: 'entity-001',
            toEntityId: 'entity-002',
            loanType: 'intercompany',
            principalAmount: Money::of(50000, 'USD'),
            interestRate: 5.0,
            outstandingBalance: Money::of(50000, 'USD'),
            startDate: $startDate,
            maturityDate: new DateTimeImmutable('+60 days'),
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: 'REF-001',
            notes: 'Test loan',
            createdAt: $startDate,
            updatedAt: $now
        );
    }

    private function createActiveLoan(): IntercompanyLoan
    {
        $now = new DateTimeImmutable();

        return new IntercompanyLoan(
            id: 'TRE-ICL-001',
            tenantId: 'tenant-001',
            fromEntityId: 'entity-001',
            toEntityId: 'entity-002',
            loanType: 'intercompany',
            principalAmount: Money::of(50000, 'USD'),
            interestRate: 5.0,
            outstandingBalance: Money::of(50000, 'USD'),
            startDate: $now,
            maturityDate: new DateTimeImmutable('+90 days'),
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: 'REF-001',
            notes: 'Test loan',
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function createActiveLoanAsReceived(): IntercompanyLoan
    {
        $now = new DateTimeImmutable();

        return new IntercompanyLoan(
            id: 'TRE-ICL-002',
            tenantId: 'tenant-001',
            fromEntityId: 'entity-003',
            toEntityId: 'entity-001',
            loanType: 'intercompany',
            principalAmount: Money::of(30000, 'USD'),
            interestRate: 4.5,
            outstandingBalance: Money::of(30000, 'USD'),
            startDate: $now,
            maturityDate: new DateTimeImmutable('+60 days'),
            accruedInterest: Money::of(0, 'USD'),
            paymentSchedule: [],
            referenceNumber: 'REF-002',
            notes: 'Test loan received',
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function createOverdueLoan(): IntercompanyLoan
    {
        $now = new DateTimeImmutable();
        $pastMaturity = new DateTimeImmutable('-10 days');

        return new IntercompanyLoan(
            id: 'TRE-ICL-003',
            tenantId: 'tenant-001',
            fromEntityId: 'entity-001',
            toEntityId: 'entity-002',
            loanType: 'intercompany',
            principalAmount: Money::of(50000, 'USD'),
            interestRate: 5.0,
            outstandingBalance: Money::of(25000, 'USD'),
            startDate: new DateTimeImmutable('-100 days'),
            maturityDate: $pastMaturity,
            accruedInterest: Money::of(1000, 'USD'),
            paymentSchedule: [],
            referenceNumber: 'REF-003',
            notes: 'Overdue loan',
            createdAt: new DateTimeImmutable('-100 days'),
            updatedAt: $now
        );
    }
}
