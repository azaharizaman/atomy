<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Engine;

use Nexus\Accounting\Contracts\PeriodCloseServiceInterface;
use Nexus\Accounting\Core\Enums\PeriodCloseStatus;
use Nexus\Accounting\Exceptions\{PeriodNotClosedException, InvalidReportingPeriodException};
use Nexus\Finance\Contracts\{LedgerRepositoryInterface, JournalEntryServiceInterface};
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Period close operations engine.
 *
 * Handles month-end and year-end closing procedures.
 */
final readonly class PeriodCloseService implements PeriodCloseServiceInterface
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private LedgerRepositoryInterface $ledgerRepository,
        private JournalEntryServiceInterface $journalEntryService,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function closeMonth(string $periodId, array $options = []): void
    {
        $this->logger->info('Starting month-end close', ['period_id' => $periodId]);

        // Validate readiness
        $validation = $this->validatePeriodReadiness($periodId);
        if (!$validation['ready']) {
            throw PeriodNotClosedException::withValidationErrors($periodId, $validation['issues']);
        }

        // Generate closing entries
        $closingEntries = $this->generateClosingEntries($periodId);

        // Lock the period
        $this->periodManager->lockPeriod($periodId);

        // Audit log
        $this->auditLogger->log(
            $periodId,
            'period_closed',
            "Month-end period {$periodId} closed",
            ['type' => 'month', 'closing_entries' => count($closingEntries)]
        );

        $this->logger->info('Month-end close completed', ['period_id' => $periodId]);
    }

    /**
     * {@inheritdoc}
     */
    public function closeYear(string $fiscalYearId, array $options = []): void
    {
        $this->logger->info('Starting year-end close', ['fiscal_year_id' => $fiscalYearId]);

        // Get all periods in the fiscal year
        $periods = $this->periodManager->getPeriodsForFiscalYear($fiscalYearId);

        // Verify all months are closed
        foreach ($periods as $period) {
            $status = $this->getPeriodCloseStatus($period['id']);
            if ($status !== PeriodCloseStatus::CLOSED) {
                throw PeriodNotClosedException::forPeriod($period['id']);
            }
        }

        // Generate year-end closing entries (revenue/expense → retained earnings)
        $closingEntries = $this->generateYearEndClosingEntries($fiscalYearId);

        // Lock the fiscal year
        $this->periodManager->lockFiscalYear($fiscalYearId);

        // Audit log
        $this->auditLogger->log(
            $fiscalYearId,
            'fiscal_year_closed',
            "Fiscal year {$fiscalYearId} closed",
            ['closing_entries' => count($closingEntries)]
        );

        $this->logger->info('Year-end close completed', ['fiscal_year_id' => $fiscalYearId]);
    }

    /**
     * {@inheritdoc}
     */
    public function reopenPeriod(string $periodId, string $reason): void
    {
        $this->logger->warning('Reopening period', ['period_id' => $periodId, 'reason' => $reason]);

        $status = $this->getPeriodCloseStatus($periodId);
        if (!$status->canBeReopened()) {
            throw InvalidReportingPeriodException::periodNotAvailable($periodId, 'Cannot reopen this period');
        }

        // Unlock the period
        $this->periodManager->unlockPeriod($periodId);

        // Audit log
        $this->auditLogger->log(
            $periodId,
            'period_reopened',
            "Period {$periodId} reopened: {$reason}",
            ['reason' => $reason]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPeriodCloseStatus(string $periodId): PeriodCloseStatus
    {
        $period = $this->periodManager->findById($periodId);
        
        if (!$period) {
            throw InvalidReportingPeriodException::periodNotFound($periodId);
        }

        return match($period['status']) {
            'open' => PeriodCloseStatus::OPEN,
            'in_progress' => PeriodCloseStatus::IN_PROGRESS,
            'closed' => PeriodCloseStatus::CLOSED,
            'reopened' => PeriodCloseStatus::REOPENED,
            default => PeriodCloseStatus::OPEN,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function validatePeriodReadiness(string $periodId): array
    {
        $issues = [];

        // Check if all transactions are posted
        if (!$this->areAllTransactionsPosted($periodId)) {
            $issues[] = 'Unposted transactions exist';
        }

        // Check trial balance
        if (!$this->verifyTrialBalance($periodId)) {
            $issues[] = 'Trial balance does not balance';
        }

        // Check for pending reconciliations
        $pendingReconciliations = $this->ledgerRepository->getPendingReconciliations($periodId);
        if (count($pendingReconciliations) > 0) {
            $issues[] = sprintf('%d bank reconciliations pending', count($pendingReconciliations));
        }

        return [
            'ready' => empty($issues),
            'issues' => $issues,
            'checked_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generateClosingEntries(string $periodId): array
    {
        $this->logger->info('Generating closing entries', ['period_id' => $periodId]);

        $period = $this->periodManager->findById($periodId);
        if (!$period) {
            throw InvalidReportingPeriodException::periodNotFound($periodId);
        }

        $entries = [];

        // Get revenue and expense balances
        $revenueAccounts = $this->ledgerRepository->getAccountsByType($period['entity_id'], 'revenue');
        $expenseAccounts = $this->ledgerRepository->getAccountsByType($period['entity_id'], 'expense');

        // Close revenue accounts to income summary
        foreach ($revenueAccounts as $account) {
            if ($account['balance'] != 0) {
                $entries[] = [
                    'type' => 'closing_revenue',
                    'account_id' => $account['id'],
                    'amount' => abs($account['balance']),
                    'debit_credit' => 'debit', // Debit to close revenue
                ];
            }
        }

        // Close expense accounts to income summary
        foreach ($expenseAccounts as $account) {
            if ($account['balance'] != 0) {
                $entries[] = [
                    'type' => 'closing_expense',
                    'account_id' => $account['id'],
                    'amount' => abs($account['balance']),
                    'debit_credit' => 'credit', // Credit to close expense
                ];
            }
        }

        return $entries;
    }

    /**
     * {@inheritdoc}
     */
    public function areAllTransactionsPosted(string $periodId): bool
    {
        $unpostedCount = $this->ledgerRepository->getUnpostedTransactionCount($periodId);
        return $unpostedCount === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyTrialBalance(string $periodId): bool
    {
        $period = $this->periodManager->findById($periodId);
        if (!$period) {
            return false;
        }

        $trialBalance = $this->ledgerRepository->getTrialBalance(
            $period['entity_id'],
            new \DateTimeImmutable($period['end_date'])
        );

        $totalDebits = array_sum(array_column($trialBalance, 'debit'));
        $totalCredits = array_sum(array_column($trialBalance, 'credit'));

        // Allow for small rounding differences (0.01)
        return abs($totalDebits - $totalCredits) < 0.01;
    }

    /**
     * Generate year-end closing entries.
     *
     * @return array<string, mixed>
     */
    private function generateYearEndClosingEntries(string $fiscalYearId): array
    {
        $this->logger->info('Generating year-end closing entries', ['fiscal_year_id' => $fiscalYearId]);

        $fiscalYear = $this->periodManager->findFiscalYearById($fiscalYearId);
        if (!$fiscalYear) {
            throw InvalidReportingPeriodException::periodNotFound($fiscalYearId);
        }

        $entries = [];

        // Get net income for the year
        $netIncome = $this->ledgerRepository->getNetIncome(
            $fiscalYear['entity_id'],
            new \DateTimeImmutable($fiscalYear['start_date']),
            new \DateTimeImmutable($fiscalYear['end_date'])
        );

        // Close income summary to retained earnings
        if ($netIncome != 0) {
            $entries[] = [
                'type' => 'close_to_retained_earnings',
                'description' => "Year-end closing entry for {$fiscalYear['label']}",
                'amount' => abs($netIncome),
                'net_income' => $netIncome,
            ];
        }

        // Close dividend accounts if any
        $dividendAccounts = $this->ledgerRepository->getAccountsByType(
            $fiscalYear['entity_id'],
            'dividend'
        );

        foreach ($dividendAccounts as $account) {
            if ($account['balance'] != 0) {
                $entries[] = [
                    'type' => 'close_dividends',
                    'account_id' => $account['id'],
                    'amount' => abs($account['balance']),
                ];
            }
        }

        return $entries;
    }
}
