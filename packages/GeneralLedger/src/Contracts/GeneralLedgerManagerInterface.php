<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Enums\LedgerType;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\Entities\TrialBalance;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\ValueObjects\TransactionDetail;
use Nexus\GeneralLedger\ValueObjects\PostingResult;

/**
 * General Ledger Manager Interface
 * 
 * Main service interface for general ledger operations.
 * Provides a unified API for all GL operations including:
 * - Ledger management
 * - Account management
 * - Transaction posting
 * - Balance calculation
 * - Trial balance generation
 * - Subledger integration
 */
interface GeneralLedgerManagerInterface
{
    // ==================== Ledger Operations ====================

    /**
     * Create a new ledger
     * 
     * @param string $tenantId Tenant ULID
     * @param string $name Ledger name
     * @param LedgerType $type Ledger type
     * @param string $currency ISO currency code
     * @param string|null $description Optional description
     * @return Ledger Created ledger
     */
    public function createLedger(
        string $tenantId,
        string $name,
        LedgerType $type,
        string $currency,
        ?string $description = null,
    ): Ledger;

    /**
     * Get a ledger by ID
     * 
     * @param string $ledgerId Ledger ULID
     * @return Ledger The ledger
     * @throws \Nexus\GeneralLedger\Exceptions\LedgerNotFoundException
     */
    public function getLedger(string $ledgerId): Ledger;

    /**
     * Get all ledgers for a tenant
     * 
     * @param string $tenantId Tenant ULID
     * @return array<Ledger> Ledgers
     */
    public function getLedgersByTenant(string $tenantId): array;

    /**
     * Get active ledgers for a tenant
     * 
     * @param string $tenantId Tenant ULID
     * @return array<Ledger> Active ledgers
     */
    public function getActiveLedgers(string $tenantId): array;

    /**
     * Update ledger status
     * 
     * @param string $ledgerId Ledger ULID
     * @param LedgerStatus $status New status
     * @return Ledger Updated ledger
     */
    public function updateLedgerStatus(string $ledgerId, LedgerStatus $status): Ledger;

    /**
     * Close a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return Ledger Closed ledger
     */
    public function closeLedger(string $ledgerId): Ledger;

    // ==================== Account Operations ====================

    /**
     * Register a new account in the ledger
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
    ): LedgerAccount;

    /**
     * Get an account by ID
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount The account
     */
    public function getAccount(string $accountId): LedgerAccount;

    /**
     * Get all accounts in a ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Accounts
     */
    public function getAccountsForLedger(string $ledgerId): array;

    /**
     * Get accounts that allow posting
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Postable accounts
     */
    public function getPostableAccounts(string $ledgerId): array;

    /**
     * Get bank accounts
     * 
     * @param string $ledgerId Ledger ULID
     * @return array<LedgerAccount> Bank accounts
     */
    public function getBankAccounts(string $ledgerId): array;

    /**
     * Close an account
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount Closed account
     */
    public function closeAccount(string $accountId): LedgerAccount;

    /**
     * Reopen a closed account
     * 
     * @param string $accountId LedgerAccount ULID
     * @return LedgerAccount Reopened account
     */
    public function reopenAccount(string $accountId): LedgerAccount;

    // ==================== Transaction Operations ====================

    /**
     * Post a transaction to the ledger
     * 
     * @param string $ledgerId Ledger ULID
     * @param TransactionDetail $detail Transaction details
     * @param string $periodId Period ULID
     * @param \DateTimeImmutable $postingDate Posting date
     * @param \DateTimeImmutable $transactionDate Transaction date
     * @return PostingResult Result of posting
     */
    public function postTransaction(
        string $ledgerId,
        TransactionDetail $detail,
        string $periodId,
        \DateTimeImmutable $postingDate,
        \DateTimeImmutable $transactionDate,
    ): PostingResult;

    /**
     * Post multiple transactions in a batch
     * 
     * @param string $ledgerId Ledger ULID
     * @param array<TransactionDetail> $details Transaction details
     * @param string $periodId Period ULID
     * @param \DateTimeImmutable $postingDate Posting date
     * @param \DateTimeImmutable $transactionDate Transaction date
     * @return PostingResult Result of batch posting
     */
    public function postBatch(
        string $ledgerId,
        array $details,
        string $periodId,
        \DateTimeImmutable $postingDate,
        \DateTimeImmutable $transactionDate,
    ): PostingResult;

    /**
     * Reverse a transaction
     * 
     * @param string $transactionId Transaction ULID
     * @param string $reason Reason for reversal
     * @param string $periodId Reversal period ULID
     * @return PostingResult Result of reversal
     */
    public function reverseTransaction(
        string $transactionId,
        string $reason,
        string $periodId,
    ): PostingResult;

    /**
     * Get account balance
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable|null $asOfDate Balance as of date
     * @return AccountBalance Current balance
     */
    public function getAccountBalance(
        string $ledgerAccountId,
        ?\DateTimeImmutable $asOfDate = null,
    ): AccountBalance;

    /**
     * Get account transactions
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable|null $fromDate From date
     * @param \DateTimeImmutable|null $toDate To date
     * @return array<Transaction> Transactions
     */
    public function getAccountTransactions(
        string $ledgerAccountId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array;

    // ==================== Trial Balance Operations ====================

    /**
     * Generate trial balance for a period
     * 
     * @param string $ledgerId Ledger ULID
     * @param string $periodId Period ULID
     * @return TrialBalance Generated trial balance
     */
    public function generateTrialBalance(string $ledgerId, string $periodId): TrialBalance;

    /**
     * Generate trial balance as of a specific date
     * 
     * @param string $ledgerId Ledger ULID
     * @param \DateTimeImmutable $asOfDate Balance as of date
     * @return TrialBalance Generated trial balance
     */
    public function generateTrialBalanceAsOfDate(
        string $ledgerId,
        \DateTimeImmutable $asOfDate,
    ): TrialBalance;

    // ==================== Validation Operations ====================

    /**
     * Check if a ledger can accept transactions
     * 
     * @param string $ledgerId Ledger ULID
     * @return bool True if can post
     */
    public function canPostTransactions(string $ledgerId): bool;

    /**
     * Check if an account allows posting
     * 
     * @param string $accountId LedgerAccount ULID
     * @return bool True if posting allowed
     */
    public function allowsPosting(string $accountId): bool;

    /**
     * Validate a posting request before attempting to post
     * 
     * @param SubledgerPostingRequest $request The posting request
     * @return ValidationResult Validation result
     */
    public function validatePosting(SubledgerPostingRequest $request): ValidationResult;

    // ==================== Subledger Integration ====================

    /**
     * Post from subledger to GL
     * 
     * @param SubledgerPostingRequest $request The posting request
     * @return PostingResult Result of posting
     */
    public function postFromSubledger(SubledgerPostingRequest $request): PostingResult;

    /**
     * Get posted amounts for a subledger
     * 
     * @param string $subledgerId Subledger identifier
     * @param string $periodId Period ULID
     * @return array{total_debits: AccountBalance, total_credits: AccountBalance}
     */
    public function getPostedAmounts(string $subledgerId, string $periodId): array;
}
