<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use DateTimeImmutable;
use Nexus\CashManagement\ValueObjects\StatementHash;
use Nexus\CashManagement\ValueObjects\StatementPeriod;

/**
 * Bank Statement Interface
 *
 * Represents an imported bank statement.
 */
interface BankStatementInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getBankAccountId(): string;

    public function getStatementNumber(): string;

    public function getPeriod(): StatementPeriod;

    public function getStatementHash(): StatementHash;

    public function getImportedAt(): DateTimeImmutable;

    public function getImportedBy(): string;

    public function getTotalDebit(): string;

    public function getTotalCredit(): string;

    public function getOpeningBalance(): string;

    public function getClosingBalance(): string;

    /**
     * Get count of transactions in statement
     */
    public function getTransactionCount(): int;

    /**
     * Check if statement has been reconciled
     */
    public function isReconciled(): bool;

    public function getReconciledAt(): ?DateTimeImmutable;

    public function getNotes(): ?string;
}
