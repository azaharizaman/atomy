<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Entities;

use Nexus\GeneralLedger\Enums\BalanceType;

/**
 * Ledger Account Entity
 * 
 * Represents an account within a specific ledger, linking a ChartOfAccount
 * account to the GL context with ledger-specific attributes.
 * 
 * This entity maintains the relationship between the master chart of accounts
 * (managed by ChartOfAccount package) and the general ledger. It adds GL-specific
 * configuration such as whether posting is allowed, whether this is a bank account,
 * and optional cost center assignment.
 */
final readonly class LedgerAccount
{
    /**
     * @param string $id Unique identifier (ULID)
     * @param string $ledgerId Parent ledger ULID
     * @param string $accountId ChartOfAccount account ULID
     * @param string $accountCode Account code from chart of accounts (e.g., "1000-0000")
     * @param string $accountName Account name from chart of accounts
     * @param BalanceType $balanceType Natural balance type (DEBIT for assets/expenses, CREDIT for liabilities/equity/revenue)
     * @param bool $allowPosting Whether transactions can be posted to this account
     * @param bool $isBankAccount Whether this is a bank or cash account (for reconciliation)
     * @param bool $isActive Whether the account is active for posting
     * @param string|null $costCenterId Optional cost center ULID for cost tracking
     * @param string|null $parentAccountId Parent ledger account ULID for hierarchy
     * @param \DateTimeImmutable $createdAt Timestamp of creation
     * @param \DateTimeImmutable|null $closedAt Timestamp when account was closed
     */
    public function __construct(
        public string $id,
        public string $ledgerId,
        public string $accountId,
        public string $accountCode,
        public string $accountName,
        public BalanceType $balanceType,
        public bool $allowPosting,
        public bool $isBankAccount,
        public bool $isActive,
        public ?string $costCenterId = null,
        public ?string $parentAccountId = null,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $closedAt = null,
    ) {
        // Bank accounts should allow posting by default
        if ($this->isBankAccount && !$this->allowPosting) {
            throw new \InvalidArgumentException(
                'Bank accounts must allow posting for reconciliation purposes'
            );
        }
    }

    /**
     * Create a new active ledger account
     */
    public static function create(
        string $id,
        string $ledgerId,
        string $accountId,
        string $accountCode,
        string $accountName,
        BalanceType $balanceType,
        bool $allowPosting = true,
        bool $isBankAccount = false,
        ?string $costCenterId = null,
        ?string $parentAccountId = null,
    ): self {
        return new self(
            id: $id,
            ledgerId: $ledgerId,
            accountId: $accountId,
            accountCode: $accountCode,
            accountName: $accountName,
            balanceType: $balanceType,
            allowPosting: $allowPosting,
            isBankAccount: $isBankAccount,
            isActive: true,
            costCenterId: $costCenterId,
            parentAccountId: $parentAccountId,
            createdAt: new \DateTimeImmutable(),
            closedAt: null,
        );
    }

    /**
     * Check if this is an asset or expense account (debit-balanced)
     */
    public function isDebitBalanced(): bool
    {
        return $this->balanceType === BalanceType::DEBIT;
    }

    /**
     * Check if this is a liability, equity, or revenue account (credit-balanced)
     */
    public function isCreditBalanced(): bool
    {
        return $this->balanceType === BalanceType::CREDIT;
    }

    /**
     * Check if transactions can be posted to this account
     */
    public function canPostTransactions(): bool
    {
        return $this->allowPosting && $this->isActive;
    }

    /**
     * Check if this is a bank or cash account
     */
    public function isBankAccount(): bool
    {
        return $this->isBankAccount;
    }

    /**
     * Check if this account has a cost center assigned
     */
    public function hasCostCenter(): bool
    {
        return $this->costCenterId !== null;
    }

    /**
     * Check if this account has a parent (is part of hierarchy)
     */
    public function hasParent(): bool
    {
        return $this->parentAccountId !== null;
    }

    /**
     * Close this account (prevent future postings)
     */
    public function close(): self
    {
        if (!$this->isActive) {
            throw new \RuntimeException(
                sprintf('Ledger account %s is already closed', $this->id)
            );
        }

        return new self(
            id: $this->id,
            ledgerId: $this->ledgerId,
            accountId: $this->accountId,
            accountCode: $this->accountCode,
            accountName: $this->accountName,
            balanceType: $this->balanceType,
            allowPosting: $this->allowPosting,
            isBankAccount: $this->isBankAccount,
            isActive: false,
            costCenterId: $this->costCenterId,
            parentAccountId: $this->parentAccountId,
            createdAt: $this->createdAt,
            closedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Reopen a closed account
     */
    public function reopen(): self
    {
        if ($this->isActive) {
            throw new \RuntimeException(
                sprintf('Ledger account %s is already active', $this->id)
            );
        }

        return new self(
            id: $this->id,
            ledgerId: $this->ledgerId,
            accountId: $this->accountId,
            accountCode: $this->accountCode,
            accountName: $this->accountName,
            balanceType: $this->balanceType,
            allowPosting: $this->allowPosting,
            isBankAccount: $this->isBankAccount,
            isActive: true,
            costCenterId: $this->costCenterId,
            parentAccountId: $this->parentAccountId,
            createdAt: $this->createdAt,
            closedAt: null,
        );
    }

    /**
     * Update the cost center assignment
     */
    public function assignCostCenter(string $costCenterId): self
    {
        return new self(
            id: $this->id,
            ledgerId: $this->ledgerId,
            accountId: $this->accountId,
            accountCode: $this->accountCode,
            accountName: $this->accountName,
            balanceType: $this->balanceType,
            allowPosting: $this->allowPosting,
            isBankAccount: $this->isBankAccount,
            isActive: $this->isActive,
            costCenterId: $costCenterId,
            parentAccountId: $this->parentAccountId,
            createdAt: $this->createdAt,
            closedAt: $this->closedAt,
        );
    }

    /**
     * Remove the cost center assignment
     */
    public function removeCostCenter(): self
    {
        return new self(
            id: $this->id,
            ledgerId: $this->ledgerId,
            accountId: $this->accountId,
            accountCode: $this->accountCode,
            accountName: $this->accountName,
            balanceType: $this->balanceType,
            allowPosting: $this->allowPosting,
            isBankAccount: $this->isBankAccount,
            isActive: $this->isActive,
            costCenterId: null,
            parentAccountId: $this->parentAccountId,
            createdAt: $this->createdAt,
            closedAt: $this->closedAt,
        );
    }
}
