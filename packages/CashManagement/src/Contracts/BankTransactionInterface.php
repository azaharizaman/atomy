<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use DateTimeImmutable;
use Nexus\CashManagement\Enums\BankTransactionType;

/**
 * Bank Transaction Interface
 *
 * Represents a transaction line from a bank statement.
 */
interface BankTransactionInterface
{
    public function getId(): string;

    public function getBankStatementId(): string;

    public function getTransactionDate(): DateTimeImmutable;

    public function getDescription(): string;

    public function getTransactionType(): BankTransactionType;

    public function getAmount(): string;

    public function getBalance(): ?string;

    public function getReference(): ?string;

    /**
     * Get transaction currency (for V2 multi-currency)
     */
    public function getTransactionCurrency(): ?string;

    /**
     * Get exchange rate (for V2 multi-currency)
     */
    public function getExchangeRate(): ?string;

    /**
     * Get functional amount (for V2 multi-currency)
     */
    public function getFunctionalAmount(): ?string;

    /**
     * Check if transaction has been reconciled
     */
    public function isReconciled(): bool;

    public function getReconciliationId(): ?string;
}
