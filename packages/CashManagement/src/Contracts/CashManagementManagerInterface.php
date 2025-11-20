<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

use Nexus\CashManagement\Enums\BankAccountStatus;
use Nexus\CashManagement\Enums\BankAccountType;

/**
 * Cash Management Manager Interface
 *
 * Main orchestrator for cash management operations.
 */
interface CashManagementManagerInterface
{
    /**
     * Create a new bank account
     *
     * @param array<string, mixed> $data
     */
    public function createBankAccount(
        string $tenantId,
        string $accountCode,
        string $glAccountId,
        string $accountNumber,
        string $bankName,
        string $bankCode,
        BankAccountType $accountType,
        string $currency,
        ?array $csvImportConfig = null
    ): BankAccountInterface;

    /**
     * Import bank statement
     *
     * @param array<array<string, mixed>> $transactions
     */
    public function importBankStatement(
        string $bankAccountId,
        string $startDate,
        string $endDate,
        array $transactions,
        string $importedBy
    ): BankStatementInterface;

    /**
     * Reconcile a bank statement
     */
    public function reconcileStatement(string $statementId): ReconciliationResultInterface;

    /**
     * Post a pending adjustment to GL
     */
    public function postPendingAdjustment(
        string $pendingAdjustmentId,
        string $glAccount,
        string $postedBy
    ): string;

    /**
     * Reject a pending adjustment (triggers reversal if needed)
     */
    public function rejectPendingAdjustment(
        string $pendingAdjustmentId,
        string $reason,
        string $rejectedBy
    ): void;

    /**
     * Update bank account status
     */
    public function updateBankAccountStatus(
        string $bankAccountId,
        BankAccountStatus $status
    ): void;
}
