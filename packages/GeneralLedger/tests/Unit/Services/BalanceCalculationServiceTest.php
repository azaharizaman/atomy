<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\GeneralLedger\Services\BalanceCalculationService;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\Common\ValueObjects\Money;

final class BalanceCalculationServiceTest extends TestCase
{
    private readonly MockObject&TransactionQueryInterface $transactionQuery;
    private readonly MockObject&LedgerAccountQueryInterface $accountQuery;
    private readonly BalanceCalculationService $service;

    protected function setUp(): void
    {
        $this->transactionQuery = $this->createMock(TransactionQueryInterface::class);
        $this->accountQuery = $this->createMock(LedgerAccountQueryInterface::class);
        $this->service = new BalanceCalculationService(
            $this->transactionQuery,
            $this->accountQuery
        );
    }

    public function test_it_calculates_new_balance_for_debit_account_debit_tx(): void
    {
        $currentBalance = AccountBalance::debit(Money::of('100.00', 'USD'));
        $txAmount = AccountBalance::debit(Money::of('50.00', 'USD'));
        
        $newBalance = $this->service->calculateNewBalance(
            $currentBalance,
            $txAmount,
            TransactionType::DEBIT,
            BalanceType::DEBIT
        );

        $this->assertEquals(150.00, $newBalance->amount->getAmount());
        $this->assertEquals(BalanceType::DEBIT, $newBalance->balanceType);
    }

    public function test_it_calculates_new_balance_for_debit_account_credit_tx(): void
    {
        $currentBalance = AccountBalance::debit(Money::of('100.00', 'USD'));
        $txAmount = AccountBalance::credit(Money::of('40.00', 'USD'));
        
        $newBalance = $this->service->calculateNewBalance(
            $currentBalance,
            $txAmount,
            TransactionType::CREDIT,
            BalanceType::DEBIT
        );

        $this->assertEquals(60.00, $newBalance->amount->getAmount());
        $this->assertEquals(BalanceType::DEBIT, $newBalance->balanceType);
    }

    public function test_it_calculates_new_balance_for_credit_account_credit_tx(): void
    {
        $currentBalance = AccountBalance::credit(Money::of('100.00', 'USD'));
        $txAmount = AccountBalance::credit(Money::of('50.00', 'USD'));
        
        $newBalance = $this->service->calculateNewBalance(
            $currentBalance,
            $txAmount,
            TransactionType::CREDIT,
            BalanceType::CREDIT
        );

        $this->assertEquals(150.00, $newBalance->amount->getAmount());
        $this->assertEquals(BalanceType::CREDIT, $newBalance->balanceType);
    }

    public function test_it_switches_balance_type_when_overdrawn(): void
    {
        $currentBalance = AccountBalance::debit(Money::of('100.00', 'USD'));
        $txAmount = AccountBalance::credit(Money::of('150.00', 'USD'));
        
        $newBalance = $this->service->calculateNewBalance(
            $currentBalance,
            $txAmount,
            TransactionType::CREDIT,
            BalanceType::DEBIT
        );

        $this->assertEquals(50.00, $newBalance->amount->getAmount());
        $this->assertEquals(BalanceType::CREDIT, $newBalance->balanceType);
    }

    public function test_it_can_get_account_balance(): void
    {
        $accountId = 'acc-id';
        $expectedBalance = AccountBalance::debit(Money::of('100.00', 'USD'));

        $this->transactionQuery->expects($this->once())
            ->method('getAccountBalance')
            ->with($accountId, $this->isInstanceOf(\DateTimeImmutable::class))
            ->willReturn($expectedBalance);

        $result = $this->service->getAccountBalance($accountId);

        $this->assertSame($expectedBalance, $result);
    }

    public function test_it_can_get_account_totals(): void
    {
        $accountId = 'acc-id';
        $periodId = 'period-id';
        $debits = AccountBalance::debit(Money::of('100.00', 'USD'));
        $credits = AccountBalance::credit(Money::of('40.00', 'USD'));

        $this->transactionQuery->expects($this->once())
            ->method('getTotalDebits')
            ->with($accountId, $periodId)
            ->willReturn($debits);
        $this->transactionQuery->expects($this->once())
            ->method('getTotalCredits')
            ->with($accountId, $periodId)
            ->willReturn($credits);

        $account = \Nexus\GeneralLedger\Entities\LedgerAccount::create(
            $accountId, 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT
        );
        $this->accountQuery->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $result = $this->service->getAccountTotals($accountId, $periodId);

        $this->assertEquals(100.00, $result['total_debits']->amount->getAmount());
        $this->assertEquals(40.00, $result['total_credits']->amount->getAmount());
        $this->assertEquals(60.00, $result['net_balance']->amount->getAmount());
        $this->assertEquals(BalanceType::DEBIT, $result['net_balance']->balanceType);
    }

    public function test_it_can_get_account_balance_for_period(): void
    {
        $accountId = 'acc-id';
        $periodId = 'period-id';
        $expectedBalance = AccountBalance::debit(Money::of('100.00', 'USD'));

        $this->transactionQuery->expects($this->once())
            ->method('getAccountBalanceForPeriod')
            ->with($accountId, $periodId)
            ->willReturn($expectedBalance);

        $result = $this->service->getAccountBalanceForPeriod($accountId, $periodId);
        $this->assertSame($expectedBalance, $result);
    }

    public function test_it_can_get_all_account_balances(): void
    {
        $ledgerId = 'ledger-id';
        $asOfDate = new \DateTimeImmutable();
        $accounts = [
            \Nexus\GeneralLedger\Entities\LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT),
        ];

        $this->accountQuery->expects($this->once())
            ->method('findByLedger')
            ->with($ledgerId)
            ->willReturn($accounts);

        $this->transactionQuery->expects($this->once())
            ->method('getAccountBalance')
            ->with('a1', $asOfDate)
            ->willReturn(AccountBalance::debit(Money::of('100.00', 'USD')));

        $result = $this->service->getAllAccountBalances($ledgerId, $asOfDate);
        $this->assertCount(1, $result);
        $this->assertEquals(100.00, $result['a1']->amount->getAmount());
    }

    public function test_it_can_get_period_activity(): void
    {
        $accountId = 'acc-id';
        $periodId = 'period-id';
        
        $this->transactionQuery->expects($this->once())->method('getTotalDebits')->with($accountId, $periodId)->willReturn(AccountBalance::debit(Money::of('100.00', 'USD')));
        $this->transactionQuery->expects($this->once())->method('getTotalCredits')->with($accountId, $periodId)->willReturn(AccountBalance::credit(Money::of('50.00', 'USD')));
        $this->accountQuery->expects($this->once())->method('findById')->with($accountId)->willReturn(
            \Nexus\GeneralLedger\Entities\LedgerAccount::create($accountId, 'l', 'c', '1', 'n', BalanceType::DEBIT)
        );

        $activity = $this->service->getPeriodActivity($accountId, $periodId);
        $this->assertEquals(150.00, $activity->getAmount());
    }
}
