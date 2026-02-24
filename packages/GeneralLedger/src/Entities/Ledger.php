<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Entities;

use Nexus\GeneralLedger\Enums\LedgerStatus;
use Nexus\GeneralLedger\Enums\LedgerType;

/**
 * Ledger Entity
 * 
 * Represents a general ledger for a tenant. Each tenant may have multiple
 * ledgers (e.g., statutory ledger for external reporting, management ledger
 * for internal reporting).
 * 
 * The ledger is the core container for all financial transactions and serves
 * as the source of truth for account balances within a specific accounting context.
 * Different ledger types allow for parallel accounting treatments while maintaining
 * integrity through proper status management.
 */
final readonly class Ledger
{
    /**
     * @param string $id Unique identifier (ULID)
     * @param string $tenantId Tenant ULID for multi-entity support
     * @param string $name Ledger name (e.g., "Statutory Ledger", "Management Ledger")
     * @param string $currency ISO 4217 currency code (e.g., "USD", "EUR", "MYR")
     * @param LedgerType $type Classification for reporting purposes
     * @param LedgerStatus $status Current operational state
     * @param \DateTimeImmutable $createdAt Timestamp of creation
     * @param \DateTimeImmutable|null $closedAt Timestamp when ledger was closed (null if still active)
     * @param string|null $description Optional description for the ledger
     */
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $name,
        public string $currency,
        public LedgerType $type,
        public LedgerStatus $status,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $closedAt = null,
        public ?string $description = null,
    ) {
        // Validate that closed ledgers have a closedAt timestamp
        if ($this->status === LedgerStatus::CLOSED && $this->closedAt === null) {
            throw new \InvalidArgumentException(
                'A closed ledger must have a closedAt timestamp'
            );
        }

        // Validate currency format (ISO 4217 - 3 uppercase letters)
        if (!preg_match('/^[A-Z]{3}$/', $this->currency)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid currency code: %s. Expected ISO 4217 format (e.g., USD, EUR)', $this->currency)
            );
        }
    }

    /**
     * Check if the ledger is currently active and can accept transactions
     */
    public function isActive(): bool
    {
        return $this->status === LedgerStatus::ACTIVE;
    }

    /**
     * Check if the ledger is closed
     */
    public function isClosed(): bool
    {
        return $this->status === LedgerStatus::CLOSED;
    }

    /**
     * Check if the ledger is archived
     */
    public function isArchived(): bool
    {
        return $this->status === LedgerStatus::ARCHIVED;
    }

    /**
     * Check if transactions can be posted to this ledger
     * 
     * Only active ledgers can accept new transactions. This is a fundamental
     * business rule to prevent posting to closed or archived ledgers.
     */
    public function canPostTransactions(): bool
    {
        return $this->isActive();
    }

    /**
     * Check if this is a statutory ledger
     */
    public function isStatutory(): bool
    {
        return $this->type === LedgerType::STATUTORY;
    }

    /**
     * Check if this is a management ledger
     */
    public function isManagement(): bool
    {
        return $this->type === LedgerType::MANAGEMENT;
    }

    /**
     * Create a new active ledger with the given parameters
     * 
     * @param string $id ULID for the ledger
     * @param string $tenantId Tenant ULID
     * @param string $name Ledger name
     * @param string $currency ISO currency code
     * @param LedgerType $type Ledger type
     * @param string|null $description Optional description
     */
    public static function create(
        string $id,
        string $tenantId,
        string $name,
        string $currency,
        LedgerType $type,
        ?string $description = null,
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            name: $name,
            currency: $currency,
            type: $type,
            status: LedgerStatus::ACTIVE,
            createdAt: new \DateTimeImmutable(),
            closedAt: null,
            description: $description,
        );
    }

    /**
     * Close this ledger
     * 
     * Once closed, no more transactions can be posted. This is typically
     * done at the end of a fiscal year for statutory reporting purposes.
     */
    public function close(): self
    {
        if ($this->isClosed()) {
            throw new \RuntimeException(
                sprintf('Ledger %s is already closed', $this->id)
            );
        }

        if ($this->isArchived()) {
            throw new \RuntimeException(
                sprintf('Ledger %s is archived and cannot be closed', $this->id)
            );
        }

        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            currency: $this->currency,
            type: $this->type,
            status: LedgerStatus::CLOSED,
            createdAt: $this->createdAt,
            closedAt: new \DateTimeImmutable(),
            description: $this->description,
        );
    }

    /**
     * Archive this ledger
     * 
     * Archiving is typically done after closing and is used for historical
     * records that are no longer actively used but need to be retained.
     */
    public function archive(): self
    {
        if ($this->isArchived()) {
            throw new \RuntimeException(
                sprintf('Ledger %s is already archived', $this->id)
            );
        }

        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            currency: $this->currency,
            type: $this->type,
            status: LedgerStatus::ARCHIVED,
            createdAt: $this->createdAt,
            closedAt: $this->closedAt,
            description: $this->description,
        );
    }

    /**
     * Reactivate a closed ledger
     * 
     * Note: This should only be used in exceptional circumstances and may
     * require additional approvals depending on business rules.
     */
    public function reactivate(): self
    {
        if ($this->isActive()) {
            throw new \RuntimeException(
                sprintf('Ledger %s is already active', $this->id)
            );
        }

        if ($this->isArchived()) {
            throw new \RuntimeException(
                sprintf('Ledger %s is archived and cannot be reactivated', $this->id)
            );
        }

        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            currency: $this->currency,
            type: $this->type,
            status: LedgerStatus::ACTIVE,
            createdAt: $this->createdAt,
            closedAt: null,
            description: $this->description,
        );
    }
}
