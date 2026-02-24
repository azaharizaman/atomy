<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\GeneralLedger\Services\AccountService;
use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerAccountPersistInterface;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Exceptions\GeneralLedgerException;

final class AccountServiceTest extends TestCase
{
    private MockObject&LedgerAccountQueryInterface $queryRepository;
    private MockObject&LedgerAccountPersistInterface $persistRepository;
    private AccountService $service;

    protected function setUp(): void
    {
        $this->queryRepository = $this->createMock(LedgerAccountQueryInterface::class);
        $this->persistRepository = $this->createMock(LedgerAccountPersistInterface::class);
        $this->service = new AccountService(
            $this->queryRepository,
            $this->persistRepository
        );
    }

    public function test_it_can_register_an_account(): void
    {
        $ledgerId = 'ledger-id';
        $accountId = 'coa-account-id';
        $accountCode = '1000';
        $accountName = 'Cash';
        $balanceType = BalanceType::DEBIT;

        $this->queryRepository->expects($this->once())
            ->method('findByAccountCode')
            ->with($ledgerId, $accountCode)
            ->willReturn(null);

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(LedgerAccount::class));

        $result = $this->service->registerAccount(
            $ledgerId,
            $accountId,
            $accountCode,
            $accountName,
            $balanceType
        );

        $this->assertInstanceOf(LedgerAccount::class, $result);
        $this->assertEquals($ledgerId, $result->ledgerId);
        $this->assertEquals($accountCode, $result->accountCode);
    }

    public function test_it_throws_exception_if_account_code_already_exists(): void
    {
        $ledgerId = 'ledger-id';
        $accountCode = '1000';
        
        $existingAccount = LedgerAccount::create(
            'acc-id',
            $ledgerId,
            'coa-id',
            $accountCode,
            'Cash',
            BalanceType::DEBIT
        );

        $this->queryRepository->expects($this->once())
            ->method('findByAccountCode')
            ->with($ledgerId, $accountCode)
            ->willReturn($existingAccount);

        $this->expectException(GeneralLedgerException::class);
        $this->expectExceptionMessage("Account with code 1000 already exists in ledger ledger-id");

        $this->service->registerAccount(
            $ledgerId,
            'coa-id',
            $accountCode,
            'Cash',
            BalanceType::DEBIT
        );
    }

    public function test_it_can_get_an_account(): void
    {
        $accountId = 'account-id';
        $account = LedgerAccount::create(
            $accountId,
            'ledger-id',
            'coa-id',
            '1000',
            'Cash',
            BalanceType::DEBIT
        );

        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $result = $this->service->getAccount($accountId);

        $this->assertSame($account, $result);
    }

    public function test_it_throws_exception_if_account_not_found(): void
    {
        $accountId = 'non-existent';
        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn(null);

        $this->expectException(GeneralLedgerException::class);
        $this->expectExceptionMessage("Account not found: non-existent");

        $this->service->getAccount($accountId);
    }

    public function test_it_can_close_an_account(): void
    {
        $accountId = 'account-id';
        $account = LedgerAccount::create(
            $accountId,
            'ledger-id',
            'coa-id',
            '1000',
            'Cash',
            BalanceType::DEBIT
        );

        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $this->persistRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn(LedgerAccount $a) => $a->isActive === false));

        $result = $this->service->closeAccount($accountId);

        $this->assertFalse($result->isActive);
    }

    public function test_it_can_reopen_an_account(): void
    {
        $accountId = 'account-id';
        $account = LedgerAccount::create($accountId, 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT)->close();

        $this->queryRepository->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $this->persistRepository->expects($this->once())->method('save');

        $result = $this->service->reopenAccount($accountId);
        $this->assertTrue($result->isActive);
    }

    public function test_it_can_get_accounts_for_ledger(): void
    {
        $ledgerId = 'ledger-id';
        $accounts = [LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT)];
        
        $this->queryRepository->expects($this->once())
            ->method('findByLedger')
            ->with($ledgerId)
            ->willReturn($accounts);

        $result = $this->service->getAccountsForLedger($ledgerId);
        $this->assertSame($accounts, $result);
    }

    public function test_it_can_get_postable_accounts(): void
    {
        $ledgerId = 'ledger-id';
        $accounts = [LedgerAccount::create('a1', $ledgerId, 'coa-1', '1000', 'Cash', BalanceType::DEBIT)];
        
        $this->queryRepository->expects($this->once())
            ->method('findPostableAccounts')
            ->willReturn($accounts);

        $result = $this->service->getPostableAccounts($ledgerId);
        $this->assertSame($accounts, $result);
    }

    public function test_it_can_assign_cost_center(): void
    {
        $accountId = 'acc-id';
        $account = LedgerAccount::create($accountId, 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT);
        
        $this->queryRepository->expects($this->once())->method('findById')->willReturn($account);
        $this->persistRepository->expects($this->once())->method('save');

        $result = $this->service->assignCostCenter($accountId, 'cc-id');
        $this->assertEquals('cc-id', $result->costCenterId);
    }

    public function test_it_can_remove_cost_center(): void
    {
        $accountId = 'acc-id';
        $account = LedgerAccount::create($accountId, 'ledger-id', 'coa-id', '1000', 'Cash', BalanceType::DEBIT, costCenterId: 'cc-id');
        
        $this->queryRepository->expects($this->once())->method('findById')->willReturn($account);
        $this->persistRepository->expects($this->once())->method('save');

        $result = $this->service->removeCostCenter($accountId);
        $this->assertNull($result->costCenterId);
    }

    public function test_it_can_check_if_allows_posting(): void
    {
        $accountId = 'acc-id';
        $this->queryRepository->expects($this->once())
            ->method('allowsPosting')
            ->with($accountId)
            ->willReturn(true);

        $this->assertTrue($this->service->allowsPosting($accountId));
    }
}
