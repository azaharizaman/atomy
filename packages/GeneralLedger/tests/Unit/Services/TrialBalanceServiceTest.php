<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\GeneralLedger\Services\TrialBalanceService;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Contracts\BalanceCalculationInterface;
use Nexus\GeneralLedger\Contracts\IdGeneratorInterface;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Entities\TrialBalance;
use Nexus\GeneralLedger\Enums\LedgerType;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\Common\ValueObjects\Money;

final class TrialBalanceServiceTest extends TestCase
{
    private readonly MockObject&LedgerQueryInterface $ledgerQuery;
    private readonly MockObject&LedgerAccountQueryInterface $accountQuery;
    private readonly MockObject&TransactionQueryInterface $transactionQuery;
    private readonly MockObject&BalanceCalculationInterface $balanceService;
    private readonly MockObject&IdGeneratorInterface $idGenerator;
    private readonly TrialBalanceService $service;

    protected function setUp(): void
    {
        $this->ledgerQuery = $this->createMock(LedgerQueryInterface::class);
        $this->accountQuery = $this->createMock(LedgerAccountQueryInterface::class);
        $this->transactionQuery = $this->createMock(TransactionQueryInterface::class);
        $this->balanceService = $this->createMock(BalanceCalculationInterface::class);
        $this->idGenerator = $this->createMock(IdGeneratorInterface::class);
        
        $this->service = new TrialBalanceService(
            $this->ledgerQuery,
            $this->accountQuery,
            $this->transactionQuery,
            $this->balanceService,
            $this->idGenerator
        );
    }

    public function test_it_can_generate_trial_balance(): void
    {
        $ledgerId = 'ledger-id';
        $periodId = 'period-id';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        
        $accounts = [
            LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT),
            LedgerAccount::create('a2', $ledgerId, 'coa-2', '2000', 'Payable', BalanceType::CREDIT),
        ];

        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->accountQuery->expects($this->once())
            ->method('findByLedger')
            ->with($ledgerId)
            ->willReturn($accounts);

        $this->balanceService->expects($this->exactly(2))
            ->method('getAccountBalanceForPeriod')
            ->willReturnMap([
                ['a1', $periodId, AccountBalance::debit(Money::of('100.00', 'USD'))],
                ['a2', $periodId, AccountBalance::credit(Money::of('100.00', 'USD'))],
            ]);

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('tb-id');

        $result = $this->service->generateTrialBalance($ledgerId, $periodId);

        $this->assertInstanceOf(TrialBalance::class, $result);
        $this->assertCount(2, $result->lines);
        $this->assertTrue($result->isBalanced);
        $this->assertEquals('tb-id', $result->id);
    }

    public function test_it_can_generate_trial_balance_as_of_date(): void
    {
        $ledgerId = 'ledger-id';
        $asOfDate = new \DateTimeImmutable('2023-12-31');
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        
        $accounts = [
            LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT),
        ];

        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->accountQuery->expects($this->once())
            ->method('findByLedger')
            ->with($ledgerId)
            ->willReturn($accounts);

        $this->balanceService->expects($this->once())
            ->method('getAccountBalance')
            ->with('a1', $asOfDate)
            ->willReturn(AccountBalance::debit(Money::of('100.00', 'USD')));

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('tb-id');

        $result = $this->service->generateTrialBalanceAsOfDate($ledgerId, $asOfDate);

        $this->assertInstanceOf(TrialBalance::class, $result);
        $this->assertEquals('ASOF', $result->periodId);
        $this->assertEquals($asOfDate, $result->asOfDate);
    }

    public function test_it_can_get_trial_balance_summary(): void
    {
        $ledgerId = 'ledger-id';
        $periodId = 'period-id';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        $accounts = [LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT)];

        $this->ledgerQuery->expects($this->once())->method('findById')->with($ledgerId)->willReturn($ledger);
        $this->accountQuery->expects($this->once())->method('findByLedger')->with($ledgerId)->willReturn($accounts);
        $this->balanceService->expects($this->once())
            ->method('getAccountBalanceForPeriod')
            ->with('a1', $periodId)
            ->willReturn(AccountBalance::debit(Money::of('100.00', 'USD')));

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('tb-id');

        $summary = $this->service->getTrialBalanceSummary($ledgerId, $periodId);

        $this->assertIsArray($summary);
        $this->assertEquals(100.00, $summary['total_debits']);
    }

    public function test_it_can_get_accounts_with_unusual_activity(): void
    {
        $ledgerId = 'ledger-id';
        $periodId = 'period-id';
        $accounts = [
            LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT),
        ];

        $this->accountQuery->expects($this->once())
            ->method('findByLedger')
            ->with($ledgerId)
            ->willReturn($accounts);

        // Difference is 10.00, total activity is 190.00. 10/190 = ~5.2% > 1% threshold
        $totals = [
            'total_debits' => AccountBalance::debit(Money::of('100.00', 'USD')),
            'total_credits' => AccountBalance::credit(Money::of('90.00', 'USD')),
        ];

        $this->balanceService->expects($this->once())
            ->method('getAccountTotals')
            ->with('a1', $periodId)
            ->willReturn($totals);

        $result = $this->service->getAccountsWithUnusualActivity($ledgerId, $periodId);

        $this->assertCount(1, $result);
        $this->assertEquals('a1', $result[0]['account_id']);
    }

    public function test_it_throws_exception_if_ledger_not_found(): void
    {
        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ledger not found');

        $this->service->generateTrialBalance('non-existent', 'period');
    }
}
