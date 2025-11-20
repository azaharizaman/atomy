<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use DateTimeImmutable;
use Nexus\CashManagement\Enums\BankAccountStatus;
use Nexus\CashManagement\Enums\BankAccountType;
use Nexus\CashManagement\ValueObjects\BankAccountNumber;

/**
 * Bank Account Interface
 *
 * Represents a bank account in the cash management system.
 */
interface BankAccountInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getAccountCode(): string;

    public function getGlAccountId(): string;

    public function getBankAccountNumber(): BankAccountNumber;

    public function getBankName(): string;

    public function getAccountType(): BankAccountType;

    public function getStatus(): BankAccountStatus;

    public function getCurrency(): string;

    public function getCurrentBalance(): string;

    public function getLastReconciledAt(): ?DateTimeImmutable;

    /**
     * Get CSV import configuration as array
     *
     * @return array<string, mixed>|null
     */
    public function getCsvImportConfig(): ?array;

    /**
     * Check if multi-currency is enabled for this account
     */
    public function isMultiCurrency(): bool;

    /**
     * Check if account is active and can be used
     */
    public function isActive(): bool;

    public function getNotes(): ?string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
