<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Services;

use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerAccountPersistInterface;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Exceptions\GeneralLedgerException;
use Symfony\Component\Uid\Ulid;

/**
 * Account Service
 * 
 * Service for managing ledger accounts including registration,
 * updates, and status management.
 */
final readonly class AccountService
{
    public function __construct(
        private LedgerAccountQueryInterface $queryRepository,
        private LedgerAccountPersistInterface $persistRepository,
    ) {}

    /**
     * Register a new account in the ledger
     * 
     * Links a ChartOfAccount account to the GL with ledger-specific attributes.
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $accountId ChartOfAccount account ULID
     * @param string $accountCode Account code
     * @param string $accountName Account name
     * @param BalanceType $balanceType Account balance type
     * @param bool $allowPosting Whether to allow posting
     * @param bool $isBankAccount Whether this is a bank account
     * @param string|null $costCenterId Optional cost center
     * @return LedgerAccount Created account
     */
    public function registerAccount(
        string $ledgerId,
        string $accountId,
        string $accountCode,
        string $accountName,
        BalanceType $balanceType,
        bool $allowPosting = true,
        bool $isBankAccount = false,
        ?string $costCenterId = null,
    ): LedgerAccount {
        // Check if account code already exists in ledger
        $existing = $this->queryRepository->findByAccountCode($ledgerId, $accountCode);
        if ($existing !== null) {
            throw new GeneralLedgerException(
                sprintf(
                    'Account with code %s already exists in ledger %s',
                    $accountCode,
                    $ledgerId
                )
            );
        }

        $account = LedgerAccount::create(
            id: (string) Ulid::generate(),
            ledgerId: $ledgerId,
            accountId: $accountId,
            accountCode: $accountCode,
            accountName: $accountName,
            balanceType: $balanceType,
            allowPosting: $allowPosting,
            isBankAccount: $isBankAccount,
            costCenterId: $costCenterId,
        );

        $this->persistRepository->save($account);

        return $account;
    }

    /**
     * Get an account by ID
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount The account
     * @throws GeneralLedgerException If account not found
     */
    public function getAccount(string $accountId): LedgerAccount
    {
        $account = $this->queryRepository->findById($accountId);

        if ($account === null) {
            throw new GeneralLedgerException(
                sprintf('Account not found: %s', $accountId)
            );
        }

        return $account;
    }

    /**
     * Get all accounts in a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Accounts
     */
    public function getAccountsForLedger(string $ledgerId): array
    {
        return $this->queryRepository->findByLedger($ledgerId);
    }

    /**
     * Get accounts that allow posting
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Postable accounts
     */
    public function getPostableAccounts(string $ledgerId): array
    {
        return $this->queryRepository->findPostableAccounts($ledgerId);
    }

    /**
     * Get bank accounts for reconciliation
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Bank accounts
     */
    public function getBankAccounts(string $ledgerId): array
    {
        return $this->queryRepository->findBankAccounts($ledgerId);
    }

    /**
     * Get an account by code
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $accountCode Account code
     * @return LedgerAccount|null The account
     */
    public function getAccountByCode(string $ledgerId, string $accountCode): ?LedgerAccount
    {
        return $this->queryRepository->findByAccountCode($ledgerId, $accountCode);
    }

    /**
     * Close an account
     * 
     * Prevents future postings to the account.
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount Closed account
     */
    public function closeAccount(string $accountId): LedgerAccount
    {
        $account = $this->getAccount($accountId);
        $closedAccount = $account->close();
        $this->persistRepository->save($closedAccount);

        return $closedAccount;
    }

    /**
     * Reopen a closed account
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount Reopened account
     */
    public function reopenAccount(string $accountId): LedgerAccount
    {
        $account = $this->getAccount($accountId);
        $reopenedAccount = $account->reopen();
        $this->persistRepository->save($reopenedAccount);

        return $reopenedAccount;
    }

    /**
     * Assign cost center to account
     * 
     * @param string $accountId LedgerAccount ULID
     * @param string $costCenterId Cost center ULID
     * @return LedgerAccount Updated account
     */
    public function assignCostCenter(string $accountId, string $costCenterId): LedgerAccount
    {
        $account = $this->getAccount($accountId);
        $updatedAccount = $account->assignCostCenter($costCenterId);
        $this->persistRepository->save($updatedAccount);

        return $updatedAccount;
    }

    /**
     * Remove cost center from account
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount Updated account
     */
    public function removeCostCenter(string $accountId): LedgerAccount
    {
        $account = $this->getAccount($accountId);
        $updatedAccount = $account->removeCostCenter();
        $this->persistRepository->save($updatedAccount);

        return $updatedAccount;
    }

    /**
     * Check if account allows posting
     * 
     * @param string $accountId LedgerAccount ULID
     * @return bool True if posting allowed
     */
    public function allowsPosting(string $accountId): bool
    {
        return $this->queryRepository->allowsPosting($accountId);
    }
}
