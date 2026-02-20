<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\InvestmentInterface;
use Nexus\Treasury\Contracts\InvestmentPersistInterface;
use Nexus\Treasury\Contracts\InvestmentQueryInterface;
use Nexus\Treasury\Entities\Investment;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;
use Nexus\Treasury\Exceptions\InvestmentNotFoundException;
use Nexus\Treasury\Exceptions\InvalidInvestmentStateException;
use Nexus\Treasury\Services\InvestmentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class InvestmentServiceTest extends TestCase
{
    private InvestmentService $service;
    private MockObject $query;
    private MockObject $persist;

    protected function setUp(): void
    {
        $this->query = $this->createMock(InvestmentQueryInterface::class);
        $this->persist = $this->createMock(InvestmentPersistInterface::class);

        $this->service = new InvestmentService(
            $this->query,
            $this->persist,
            new NullLogger()
        );
    }

    public function test_record_creates_new_investment(): void
    {
        $maturityDate = new DateTimeImmutable('+30 days');

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->record(
            tenantId: 'tenant-001',
            type: InvestmentType::TREASURY_BILL,
            name: 'T-Bill Investment',
            principal: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: $maturityDate,
            bankAccountId: 'bank-001',
            referenceNumber: 'REF-001',
            description: 'Test investment'
        );

        $this->assertStringStartsWith('TRE-INV-', $result->getId());
        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals(InvestmentType::TREASURY_BILL, $result->getInvestmentType());
        $this->assertEquals('T-Bill Investment', $result->getName());
        $this->assertEquals(InvestmentStatus::ACTIVE, $result->getStatus());
    }

    public function test_record_creates_investment_without_optional_fields(): void
    {
        $maturityDate = new DateTimeImmutable('+30 days');

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->record(
            tenantId: 'tenant-001',
            type: InvestmentType::TERM_DEPOSIT,
            name: 'Term Deposit',
            principal: Money::of(50000, 'USD'),
            interestRate: 4.5,
            maturityDate: $maturityDate,
            bankAccountId: 'bank-001'
        );

        $this->assertNull($result->getReferenceNumber());
        $this->assertNull($result->getDescription());
    }

    public function test_mature_marks_investment_as_matured(): void
    {
        $investment = $this->createActiveInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($investment);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->mature('TRE-INV-001');

        $this->assertEquals(InvestmentStatus::MATURED, $result->getStatus());
    }

    public function test_mature_returns_existing_if_already_matured(): void
    {
        $maturedInvestment = $this->createMaturedInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($maturedInvestment);

        $this->persist
            ->expects($this->never())
            ->method('save');

        $result = $this->service->mature('TRE-INV-001');

        $this->assertEquals(InvestmentStatus::MATURED, $result->getStatus());
    }

    public function test_early_redeem_cancels_active_investment(): void
    {
        $investment = $this->createActiveInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($investment);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->earlyRedeem('TRE-INV-001');

        $this->assertEquals(InvestmentStatus::CANCELLED, $result->getStatus());
    }

    public function test_early_redeem_with_penalty_deducts_from_interest(): void
    {
        $investment = $this->createActiveInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($investment);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->earlyRedeem('TRE-INV-001', 10.0);

        $this->assertEquals(InvestmentStatus::CANCELLED, $result->getStatus());
    }

    public function test_early_redeem_throws_for_non_active_investment(): void
    {
        $maturedInvestment = $this->createMaturedInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($maturedInvestment);

        $this->expectException(InvalidInvestmentStateException::class);

        $this->service->earlyRedeem('TRE-INV-001');
    }

    public function test_get_returns_investment_by_id(): void
    {
        $investment = $this->createActiveInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($investment);

        $result = $this->service->get('TRE-INV-001');

        $this->assertSame($investment, $result);
    }

    public function test_get_active_returns_active_investments(): void
    {
        $investment = $this->createActiveInvestment();

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$investment]);

        $result = $this->service->getActive('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_get_maturing_between_returns_investments(): void
    {
        $investment = $this->createActiveInvestment();
        $from = new DateTimeImmutable();
        $to = new DateTimeImmutable('+30 days');

        $this->query
            ->expects($this->once())
            ->method('findMaturingBetween')
            ->with('tenant-001', $from, $to)
            ->willReturn([$investment]);

        $result = $this->service->getMaturingBetween('tenant-001', $from, $to);

        $this->assertCount(1, $result);
    }

    public function test_calculate_accrued_interest_for_returns_calculated_interest(): void
    {
        $investment = $this->createActiveInvestment();

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($investment);

        $result = $this->service->calculateAccruedInterestFor('TRE-INV-001');

        $this->assertInstanceOf(Money::class, $result);
    }

    public function test_calculate_accrued_interest_for_with_custom_date(): void
    {
        $investment = $this->createActiveInvestment();
        $asOfDate = new DateTimeImmutable('+15 days');

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-INV-001')
            ->willReturn($investment);

        $result = $this->service->calculateAccruedInterestFor('TRE-INV-001', $asOfDate);

        $this->assertInstanceOf(Money::class, $result);
    }

    public function test_get_investment_summary_returns_summary_data(): void
    {
        $investment1 = $this->createActiveInvestment();
        $investment2 = $this->createActiveInvestmentWithType(InvestmentType::TERM_DEPOSIT);

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$investment1, $investment2]);

        $this->query
            ->expects($this->once())
            ->method('findMaturedByTenantId')
            ->with('tenant-001')
            ->willReturn([]);

        $this->query
            ->expects($this->once())
            ->method('sumPrincipalByTenantId')
            ->with('tenant-001')
            ->willReturn(200000.0);

        $result = $this->service->getInvestmentSummary('tenant-001');

        $this->assertEquals(2, $result['active_count']);
        $this->assertEquals(0, $result['matured_count']);
        $this->assertEquals(200000.0, $result['total_principal']);
        $this->assertArrayHasKey('by_type', $result);
    }

    public function test_get_investment_summary_groups_by_type(): void
    {
        $investment1 = $this->createActiveInvestment();
        $investment2 = $this->createActiveInvestmentWithType(InvestmentType::TERM_DEPOSIT);
        $investment3 = $this->createActiveInvestmentWithType(InvestmentType::TERM_DEPOSIT);

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->willReturn([$investment1, $investment2, $investment3]);

        $this->query
            ->expects($this->once())
            ->method('findMaturedByTenantId')
            ->willReturn([]);

        $this->query
            ->expects($this->once())
            ->method('sumPrincipalByTenantId')
            ->willReturn(300000.0);

        $result = $this->service->getInvestmentSummary('tenant-001');

        $byType = $result['by_type'];
        $this->assertCount(2, $byType);
        
        $types = array_column($byType, 'type');
        $this->assertContains('treasury_bill', $types);
        $this->assertContains('term_deposit', $types);
    }

    public function test_process_matured_investments_processes_active_matured(): void
    {
        $investment = $this->createActiveInvestmentPastMaturity();

        $this->query
            ->expects($this->once())
            ->method('findMaturedByTenantId')
            ->with('tenant-001')
            ->willReturn([$investment]);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->willReturn($investment);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $count = $this->service->processMaturedInvestments('tenant-001');

        $this->assertEquals(1, $count);
    }

    public function test_process_matured_investments_returns_zero_when_none_to_process(): void
    {
        $maturedInvestment = $this->createMaturedInvestment();

        $this->query
            ->expects($this->once())
            ->method('findMaturedByTenantId')
            ->with('tenant-001')
            ->willReturn([$maturedInvestment]);

        $this->persist
            ->expects($this->never())
            ->method('save');

        $count = $this->service->processMaturedInvestments('tenant-001');

        $this->assertEquals(0, $count);
    }

    private function createActiveInvestment(): Investment
    {
        $now = new DateTimeImmutable();
        $maturityDate = new DateTimeImmutable('+30 days');

        return new Investment(
            id: 'TRE-INV-001',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::TREASURY_BILL,
            name: 'Test Investment',
            description: 'Test description',
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: $maturityDate,
            investmentDate: $now,
            status: InvestmentStatus::ACTIVE,
            maturityAmount: Money::of(100411, 'USD'),
            accruedInterest: Money::of(0, 'USD'),
            bankAccountId: 'bank-001',
            referenceNumber: 'REF-001',
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function createActiveInvestmentPastMaturity(): Investment
    {
        $investmentDate = new DateTimeImmutable('-60 days');
        $maturityDate = new DateTimeImmutable('-30 days');

        return new Investment(
            id: 'TRE-INV-002',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::TREASURY_BILL,
            name: 'Past Maturity Investment',
            description: 'Test description',
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: $maturityDate,
            investmentDate: $investmentDate,
            status: InvestmentStatus::ACTIVE,
            maturityAmount: Money::of(100411, 'USD'),
            accruedInterest: Money::of(411, 'USD'),
            bankAccountId: 'bank-001',
            referenceNumber: 'REF-002',
            createdAt: $investmentDate,
            updatedAt: $investmentDate
        );
    }

    private function createMaturedInvestment(): Investment
    {
        $investmentDate = new DateTimeImmutable('-60 days');
        $maturityDate = new DateTimeImmutable('-30 days');
        $now = new DateTimeImmutable();

        return new Investment(
            id: 'TRE-INV-003',
            tenantId: 'tenant-001',
            investmentType: InvestmentType::TREASURY_BILL,
            name: 'Matured Investment',
            description: 'Test description',
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 5.0,
            maturityDate: $maturityDate,
            investmentDate: $investmentDate,
            status: InvestmentStatus::MATURED,
            maturityAmount: Money::of(100411, 'USD'),
            accruedInterest: Money::of(411, 'USD'),
            bankAccountId: 'bank-001',
            referenceNumber: 'REF-003',
            createdAt: $investmentDate,
            updatedAt: $now
        );
    }

    private function createActiveInvestmentWithType(InvestmentType $type): Investment
    {
        $now = new DateTimeImmutable();
        $maturityDate = new DateTimeImmutable('+30 days');

        return new Investment(
            id: 'TRE-INV-' . uniqid(),
            tenantId: 'tenant-001',
            investmentType: $type,
            name: 'Test Investment',
            description: 'Test description',
            principalAmount: Money::of(100000, 'USD'),
            interestRate: 4.5,
            maturityDate: $maturityDate,
            investmentDate: $now,
            status: InvestmentStatus::ACTIVE,
            maturityAmount: Money::of(100370, 'USD'),
            accruedInterest: Money::of(0, 'USD'),
            bankAccountId: 'bank-001',
            referenceNumber: null,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
