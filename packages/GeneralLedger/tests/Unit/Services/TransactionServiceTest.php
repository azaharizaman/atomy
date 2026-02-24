<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\GeneralLedger\Services\TransactionService;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Contracts\TransactionPersistInterface;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\BalanceCalculationInterface;
use Nexus\GeneralLedger\Contracts\IdGeneratorInterface;
use Nexus\GeneralLedger\Contracts\DatabaseTransactionInterface;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\Enums\LedgerType;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\ValueObjects\TransactionDetail;
use Nexus\GeneralLedger\ValueObjects\PostingResult;
use Nexus\Common\ValueObjects\Money;

final class TransactionServiceTest extends TestCase
{
    private MockObject&TransactionQueryInterface $queryRepository;
    private MockObject&TransactionPersistInterface $persistRepository;
    private MockObject&LedgerQueryInterface $ledgerQuery;
    private MockObject&LedgerAccountQueryInterface $accountQuery;
    private MockObject&BalanceCalculationInterface $balanceService;
    private MockObject&IdGeneratorInterface $idGenerator;
    private MockObject&DatabaseTransactionInterface $db;
    private TransactionService $service;

    protected function setUp(): void
    {
        $this->queryRepository = $this->createMock(TransactionQueryInterface::class);
        $this->persistRepository = $this->createMock(TransactionPersistInterface::class);
        $this->ledgerQuery = $this->createMock(LedgerQueryInterface::class);
        $this->accountQuery = $this->createMock(LedgerAccountQueryInterface::class);
        $this->balanceService = $this->createMock(BalanceCalculationInterface::class);
        $this->idGenerator = $this->createMock(IdGeneratorInterface::class);
        $this->db = $this->createMock(DatabaseTransactionInterface::class);
        
        $this->service = new TransactionService(
            $this->queryRepository,
            $this->persistRepository,
            $this->ledgerQuery,
            $this->accountQuery,
            $this->balanceService,
            $this->idGenerator,
            $this->db
        );
    }

    public function test_it_can_post_a_transaction(): void
    {
        $ledgerId = 'ledger-id';
        $accountId = 'account-id';
        $periodId = 'period-id';
        $now = new \DateTimeImmutable();
        
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        $account = LedgerAccount::create($accountId, $ledgerId, 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        
        $detail = new TransactionDetail(
            ledgerAccountId: $accountId,
            journalEntryId: 'je-id',
            type: TransactionType::DEBIT,
            amount: AccountBalance::debit(Money::of('100.00', 'USD')),
            journalEntryLineId: 'line-id'
        );

        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->accountQuery->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $currentBalance = AccountBalance::zero('USD');
        $this->balanceService->expects($this->once())
            ->method('getAccountBalance')
            ->with($accountId)
            ->willReturn($currentBalance);

        $newBalance = AccountBalance::debit(Money::of('100.00', 'USD'));
        $this->balanceService->expects($this->once())
            ->method('calculateNewBalance')
            ->willReturn($newBalance);

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('tx-id');

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Transaction::class));

        $result = $this->service->postTransaction($ledgerId, $detail, $periodId, $now, $now);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('tx-id', $result->getTransactionId());
    }

    public function test_it_returns_failure_if_ledger_not_found(): void
    {
        $ledgerId = 'non-existent';
        $detail = new TransactionDetail(
            ledgerAccountId: 'acc-id',
            journalEntryId: 'je-id',
            type: TransactionType::DEBIT,
            amount: AccountBalance::debit(Money::of('100.00', 'USD')),
            journalEntryLineId: 'line-id'
        );

        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn(null);

        $result = $this->service->postTransaction($ledgerId, $detail, 'period-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('LEDGER_NOT_FOUND', $result->errorCode);
    }

    public function test_it_returns_failure_if_account_not_found(): void
    {
        $ledgerId = 'ledger-id';
        $accountId = 'non-existent';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        
        $detail = new TransactionDetail(
            ledgerAccountId: $accountId,
            journalEntryId: 'je-id',
            type: TransactionType::DEBIT,
            amount: AccountBalance::debit(Money::of('100.00', 'USD')),
            journalEntryLineId: 'line-id'
        );

        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->accountQuery->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn(null);

        $result = $this->service->postTransaction($ledgerId, $detail, 'period-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('ACCOUNT_NOT_FOUND', $result->errorCode);
    }

    public function test_it_can_post_a_batch_of_transactions(): void
    {
        $ledgerId = 'ledger-id';
        $periodId = 'period-id';
        $now = new \DateTimeImmutable();
        
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        $account = LedgerAccount::create('acc-id', $ledgerId, 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        
        $details = [
            new TransactionDetail('acc-id', 'je-id', TransactionType::DEBIT, AccountBalance::debit(Money::of('100.00', 'USD')), 'l1'),
            new TransactionDetail('acc-id', 'je-id', TransactionType::DEBIT, AccountBalance::debit(Money::of('50.00', 'USD')), 'l2'),
        ];

        $this->db->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(fn($callback) => $callback());

        $this->ledgerQuery->expects($this->exactly(2))
            ->method('findById')
            ->willReturn($ledger);

        $this->accountQuery->expects($this->exactly(2))
            ->method('findById')
            ->willReturn($account);

        $this->balanceService->expects($this->exactly(2))
            ->method('getAccountBalance')
            ->willReturn(AccountBalance::zero('USD'));

        $this->balanceService->expects($this->exactly(2))
            ->method('calculateNewBalance')
            ->willReturn(AccountBalance::debit(Money::of('100.00', 'USD')));

        $this->idGenerator->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('tx-1', 'tx-2');

        $result = $this->service->postBatch($ledgerId, $details, $periodId, $now, $now);

        $this->assertTrue($result->isSuccessful());
        $this->assertCount(2, $result->metadata['transactions']);
    }

    public function test_it_can_reverse_a_transaction(): void
    {
        $txId = 'tx-id';
        $periodId = 'rev-period';
        
        $originalTx = Transaction::create(
            $txId, 'acc-id', 'line-id', 'je-id', 
            TransactionType::DEBIT, 
            AccountBalance::debit(Money::of('100.00', 'USD')),
            AccountBalance::debit(Money::of('500.00', 'USD')),
            'old-period', new \DateTimeImmutable(), new \DateTimeImmutable()
        );

        $account = LedgerAccount::create('acc-id', 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT);

        $this->db->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(fn($callback) => $callback());

        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($txId)
            ->willReturn($originalTx);

        $this->accountQuery->expects($this->once())
            ->method('findById')
            ->with('acc-id')
            ->willReturn($account);

        $this->balanceService->expects($this->once())
            ->method('getAccountBalance')
            ->willReturn(AccountBalance::debit(Money::of('500.00', 'USD')));

        $this->balanceService->expects($this->once())
            ->method('calculateNewBalance')
            ->willReturn(AccountBalance::debit(Money::of('400.00', 'USD')));

        $this->idGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('rev-tx-id');

        $this->persistRepository->expects($this->exactly(2))
            ->method('save');

        $result = $this->service->reverseTransaction($txId, 'Error', $periodId);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('rev-tx-id', $result->getTransactionId());
    }

    public function test_it_can_get_account_balance(): void
    {
        $accountId = 'acc-id';
        $expectedBalance = AccountBalance::debit(Money::of('100.00', 'USD'));

        $this->queryRepository->expects($this->once())
            ->method('getAccountBalance')
            ->willReturn($expectedBalance);

        $result = $this->service->getAccountBalance($accountId);

        $this->assertSame($expectedBalance, $result);
    }

    public function test_it_can_get_account_transactions(): void
    {
        $accountId = 'acc-id';
        $tx = Transaction::create(
            'id', 'acc-id', 'line-id', 'je-id', 
            TransactionType::DEBIT, 
            AccountBalance::debit(Money::of('100.00', 'USD')),
            AccountBalance::debit(Money::of('100.00', 'USD')),
            'p', new \DateTimeImmutable(), new \DateTimeImmutable()
        );
        $transactions = [$tx];

        $this->queryRepository->expects($this->once())
            ->method('findByAccount')
            ->with($accountId)
            ->willReturn($transactions);

        $result = $this->service->getAccountTransactions($accountId);

        $this->assertSame($transactions, $result);
    }

    public function test_it_can_get_account_transactions_with_date_range(): void
    {
        $accountId = 'acc-id';
        $from = new \DateTimeImmutable('2023-01-01');
        $to = new \DateTimeImmutable('2023-12-31');
        $tx = Transaction::create(
            'id', 'acc-id', 'line-id', 'je-id', 
            TransactionType::DEBIT, 
            AccountBalance::debit(Money::of('100.00', 'USD')),
            AccountBalance::debit(Money::of('100.00', 'USD')),
            'p', new \DateTimeImmutable(), new \DateTimeImmutable()
        );
        $transactions = [$tx];

        $this->queryRepository->expects($this->once())
            ->method('findByDateRange')
            ->with($accountId, $from, $to)
            ->willReturn($transactions);

        $result = $this->service->getAccountTransactions($accountId, $from, $to);

        $this->assertSame($transactions, $result);
    }

    public function test_it_returns_failure_if_journal_line_id_missing(): void
    {
        $detail = new TransactionDetail(
            ledgerAccountId: 'acc-id',
            journalEntryId: 'je-id',
            type: TransactionType::DEBIT,
            amount: AccountBalance::debit(Money::of('100.00', 'USD')),
            journalEntryLineId: null // Missing
        );

        $result = $this->service->postTransaction('ledger-id', $detail, 'period-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('MISSING_LINE_ID', $result->errorCode);
    }

    public function test_it_returns_failure_if_account_belongs_to_different_ledger(): void
    {
        $ledgerId = 'ledger-A';
        $ledger = Ledger::create($ledgerId, 'tenant-id', 'Main', 'USD', LedgerType::STATUTORY);
        // Account belongs to ledger-B
        $account = LedgerAccount::create('acc-id', 'ledger-B', 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        
        $detail = new TransactionDetail(
            ledgerAccountId: 'acc-id',
            journalEntryId: 'je-id',
            type: TransactionType::DEBIT,
            amount: AccountBalance::debit(Money::of('100.00', 'USD')),
            journalEntryLineId: 'line-id'
        );

        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->with($ledgerId)
            ->willReturn($ledger);

        $this->accountQuery->expects($this->once())
            ->method('findById')
            ->willReturn($account);

        $result = $this->service->postTransaction($ledgerId, $detail, 'period-id', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('ACCOUNT_MISMATCH', $result->errorCode);
    }

    public function test_it_returns_failure_if_batch_item_fails(): void
    {
        $ledgerId = 'ledger-id';
        $details = [
            new TransactionDetail('acc-id', 'je-id', TransactionType::DEBIT, AccountBalance::debit(Money::of('100.00', 'USD')), 'l1'),
        ];

        $this->db->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(fn($callback) => $callback());

        // Force postTransaction to fail by making ledger not found
        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $result = $this->service->postBatch($ledgerId, $details, 'p', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('BATCH_FAILED', $result->errorCode);
    }

    public function test_it_handles_unexpected_exception_in_post_transaction(): void
    {
        $detail = new TransactionDetail('acc-id', 'je-id', TransactionType::DEBIT, AccountBalance::debit(Money::of('100.00', 'USD')), 'l1');
        
        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->willThrowException(new \Exception('Unexpected error'));

        $result = $this->service->postTransaction('ledger-id', $detail, 'p', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('POSTING_ERROR', $result->errorCode);
        $this->assertEquals('Unexpected error', $result->errorMessage);
    }

    public function test_it_rethrows_domain_exception_in_post_transaction(): void
    {
        $detail = new TransactionDetail('acc-id', 'je-id', TransactionType::DEBIT, AccountBalance::debit(Money::of('100.00', 'USD')), 'l1');
        
        $domainException = new \Nexus\GeneralLedger\Exceptions\GeneralLedgerException('Domain error');
        
        $this->ledgerQuery->expects($this->once())
            ->method('findById')
            ->willThrowException($domainException);

        $this->expectException(\Nexus\GeneralLedger\Exceptions\GeneralLedgerException::class);
        $this->service->postTransaction('ledger-id', $detail, 'p', new \DateTimeImmutable(), new \DateTimeImmutable());
    }
}
