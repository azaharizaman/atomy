<?php

declare(strict_types=1);

namespace Nexus\Finance\Services;

use DateTimeImmutable;
use Nexus\Finance\Contracts\AccountInterface;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Contracts\JournalEntryInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Finance\Enums\JournalEntryStatus;
use Nexus\Finance\Exceptions\AccountNotFoundException;
use Nexus\Finance\Exceptions\DuplicateAccountCodeException;
use Nexus\Finance\Exceptions\JournalEntryAlreadyPostedException;
use Nexus\Finance\Exceptions\JournalEntryNotFoundException;
use Nexus\Finance\Exceptions\JournalEntryNotPostedException;
use Nexus\Finance\ValueObjects\AccountCode;
use Nexus\Finance\ValueObjects\JournalEntryNumber;
use Nexus\Finance\ValueObjects\Money;
use Nexus\Finance\Events\JournalEntryReversedEvent;
use Nexus\Finance\Events\AccountDebitedEvent;
use Nexus\Finance\Events\AccountCreditedEvent;
use Nexus\Finance\Contracts\CacheInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Finance Manager Service
 * 
 * Main service for general ledger and journal entry operations.
 * Integrates with EventStream for SOX compliance audit trail.
 */
final class FinanceManager implements FinanceManagerInterface
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $journalEntryRepository,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly LedgerRepositoryInterface $ledgerRepository,
        private readonly PeriodManagerInterface $periodManager,
        private readonly EventStoreInterface $eventStore,
        private readonly CacheInterface $cache
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createJournalEntry(array $data): JournalEntryInterface
    {
        // Generate entry number if not provided
        $entryDate = isset($data['date']) ? new DateTimeImmutable($data['date']) : new DateTimeImmutable();
        
        if (!isset($data['entry_number'])) {
            $data['entry_number'] = $this->journalEntryRepository->getNextEntryNumber($entryDate);
        }

        // Set default status to Draft
        $data['status'] = $data['status'] ?? JournalEntryStatus::Draft->value;

        // This would create a new journal entry entity
        // Implementation depends on how we construct journal entries
        // For now, this is a placeholder that assumes the repository handles creation

        throw new \RuntimeException('createJournalEntry implementation depends on entity factory');
    }

    /**
     * {@inheritDoc}
     */
    public function postJournalEntry(string $journalEntryId): void
    {
        $entry = $this->findJournalEntry($journalEntryId);

        // Check if already posted
        if ($entry->isPosted()) {
            throw JournalEntryAlreadyPostedException::forEntry(
                $entry->getId(),
                $entry->getEntryNumber()
            );
        }

        // Check if period is locked
        // $this->periodManager->validatePeriodIsOpen($entry->getDate()); // TODO: Implement period validation

        // Publish JournalEntryPostedEvent to EventStream for SOX compliance
        $journalEntryPostedEvent = new \Nexus\Finance\Events\JournalEntryPostedEvent(
            journalEntryId: $entry->getId(),
            entryNumber: new \Nexus\Finance\ValueObjects\JournalEntryNumber($entry->getEntryNumber()),
            entryDate: $entry->getDate(),
            description: $entry->getDescription(),
            totalDebit: \Nexus\Finance\ValueObjects\Money::of($entry->getTotalDebit(), 'MYR'), // TODO: Get currency from entry
            totalCredit: \Nexus\Finance\ValueObjects\Money::of($entry->getTotalCredit(), 'MYR'), // TODO: Get currency from entry
            postedAt: new DateTimeImmutable(),
            postedBy: 'system', // TODO: Get from auth context
            tenantId: 'default' // TODO: Get from tenant context
        );

        $this->eventStore->append(
            $entry->getId(),
            $journalEntryPostedEvent
        );

        // Publish account-level events for each line (enables temporal queries)
        foreach ($entry->getLines() as $line) {
            if ($line->isDebit()) {
                $accountDebitedEvent = new \Nexus\Finance\Events\AccountDebitedEvent(
                    accountId: $line->getAccountId(),
                    accountCode: new \Nexus\Finance\ValueObjects\AccountCode($line->getAccountId()), // TODO: Get actual account code
                    amount: $line->getDebitAmount(),
                    journalEntryId: $entry->getId(),
                    entryNumber: new \Nexus\Finance\ValueObjects\JournalEntryNumber($entry->getEntryNumber()),
                    occurredAt: new DateTimeImmutable(),
                    tenantId: 'default' // TODO: Get from tenant context
                );

                $this->eventStore->append(
                    $line->getAccountId(),
                    $accountDebitedEvent
                );
            } else {
                $accountCreditedEvent = new \Nexus\Finance\Events\AccountCreditedEvent(
                    accountId: $line->getAccountId(),
                    accountCode: new \Nexus\Finance\ValueObjects\AccountCode($line->getAccountId()), // TODO: Get actual account code
                    amount: $line->getCreditAmount(),
                    journalEntryId: $entry->getId(),
                    entryNumber: new \Nexus\Finance\ValueObjects\JournalEntryNumber($entry->getEntryNumber()),
                    occurredAt: new DateTimeImmutable(),
                    tenantId: 'default' // TODO: Get from tenant context
                );

                $this->eventStore->append(
                    $line->getAccountId(),
                    $accountCreditedEvent
                );
            }
        }

        // The repository should handle updating the status to Posted
        // and setting the posted_at timestamp
        // $this->journalEntryRepository->save($entry); // TODO: Update entry status
    }

    /**
     * {@inheritDoc}
     */
    public function reverseJournalEntry(
        string $journalEntryId,
        DateTimeImmutable $reversalDate,
        string $reason
    ): JournalEntryInterface {
        $originalEntry = $this->findJournalEntry($journalEntryId);

        // Check if posted
        if (!$originalEntry->isPosted()) {
            throw JournalEntryNotPostedException::forEntry($journalEntryId);
        }

        // Check if period is open for reversal date
        // $this->periodManager->validatePeriodIsOpen($reversalDate);

        // Create reversal entry (swap debits and credits)
        $reversalData = [
            'date' => $reversalDate,
            'description' => "Reversal of {$originalEntry->getEntryNumber()}: {$reason}",
            'reference' => $originalEntry->getEntryNumber(),
            'lines' => [],
        ];

        foreach ($originalEntry->getLines() as $line) {
            $reversalData['lines'][] = [
                'account_id' => $line->getAccountId(),
                'debit' => $line->getCreditAmount()->getAmount(), // Swap!
                'credit' => $line->getDebitAmount()->getAmount(), // Swap!
                'description' => $line->getDescription(),
            ];
        }

        $reversalEntry = $this->createJournalEntry($reversalData);

        // Auto-post the reversal entry
        $this->postJournalEntry($reversalEntry->getId());

        // Publish JournalEntryReversedEvent to EventStream
        $correlationId = (string) new Ulid();
        $occurredAt = new DateTimeImmutable();
        $userId = 'system'; // TODO: Get from auth context

        $reversedEvent = new JournalEntryReversedEvent(
            originalJournalEntryId: $originalEntry->getId(),
            originalEntryNumber: new JournalEntryNumber($originalEntry->getEntryNumber()),
            reversalJournalEntryId: $reversalEntry->getId(),
            reversalEntryNumber: new JournalEntryNumber($reversalEntry->getEntryNumber()),
            reversalDate: $reversalDate,
            reason: $reason,
            reversedBy: $userId,
            tenantId: 'default', // TODO: Get from tenant context
            occurredAt: $occurredAt,
            correlationId: $correlationId
        );

        $this->eventStore->append($originalEntry->getId(), $reversedEvent);

        // Publish AccountDebitedEvent and AccountCreditedEvent for each line in reversal
        foreach ($reversalEntry->getLines() as $line) {
            $accountId = $line->getAccountId();
            $account = $this->findAccount($accountId);
            $accountCode = new AccountCode($account->getCode());
            
            if ($line->getDebitAmount()->getAmount() !== '0') {
                $debitEvent = new AccountDebitedEvent(
                    accountId: $accountId,
                    accountCode: $accountCode,
                    amount: Money::of($line->getDebitAmount()->getAmount(), 'MYR'),
                    journalEntryId: $reversalEntry->getId(),
                    entryNumber: new JournalEntryNumber($reversalEntry->getEntryNumber()),
                    occurredAt: $occurredAt,
                    tenantId: 'default',
                    causationId: $reversedEvent->getEventId(),
                    correlationId: $correlationId
                );
                $this->eventStore->append($accountId, $debitEvent);
            }

            if ($line->getCreditAmount()->getAmount() !== '0') {
                $creditEvent = new AccountCreditedEvent(
                    accountId: $accountId,
                    accountCode: $accountCode,
                    amount: Money::of($line->getCreditAmount()->getAmount(), 'MYR'),
                    journalEntryId: $reversalEntry->getId(),
                    entryNumber: new JournalEntryNumber($reversalEntry->getEntryNumber()),
                    occurredAt: $occurredAt,
                    tenantId: 'default',
                    causationId: $reversedEvent->getEventId(),
                    correlationId: $correlationId
                );
                $this->eventStore->append($accountId, $creditEvent);
            }
        }

        return $reversalEntry;
    }

    /**
     * {@inheritDoc}
     */
    public function findJournalEntry(string $journalEntryId): JournalEntryInterface
    {
        $entry = $this->journalEntryRepository->find($journalEntryId);

        if ($entry === null) {
            throw JournalEntryNotFoundException::forId($journalEntryId);
        }

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function findAccount(string $accountId): AccountInterface
    {
        $account = $this->accountRepository->find($accountId);

        if ($account === null) {
            throw AccountNotFoundException::forId($accountId);
        }

        return $account;
    }

    /**
     * {@inheritDoc}
     */
    public function findAccountByCode(string $accountCode): AccountInterface
    {
        $account = $this->accountRepository->findByCode($accountCode);

        if ($account === null) {
            throw AccountNotFoundException::forCode($accountCode);
        }

        return $account;
    }

    /**
     * {@inheritDoc}
     */
    public function createAccount(array $data): AccountInterface
    {
        // Validate account code
        $code = AccountCode::fromString($data['code']);

        // Check for duplicates
        if ($this->accountRepository->codeExists($code->getValue())) {
            throw DuplicateAccountCodeException::forCode($code->getValue());
        }

        // This would create a new account entity
        // Implementation depends on how we construct accounts
        throw new \RuntimeException('createAccount implementation depends on entity factory');
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountBalance(string $accountId, DateTimeImmutable $asOfDate): string
    {
        // Verify account exists
        $account = $this->findAccount($accountId);

        return $this->ledgerRepository->getAccountBalance($accountId, $asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function listAccounts(array $filters = []): array
    {
        return $this->accountRepository->findAll($filters);
    }

    /**
     * {@inheritDoc}
     */
    public function listJournalEntries(array $filters = []): array
    {
        return $this->journalEntryRepository->findAll($filters);
    }

    /**
     * {@inheritDoc}
     */
    public function generateBalanceTimeseries(
        string $accountId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $interval
    ): array {
        // Validate account exists
        $account = $this->findAccount($accountId);

        // Validate interval
        $validIntervals = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($interval, $validIntervals, true)) {
            throw new \InvalidArgumentException(
                "Invalid interval '{$interval}'. Must be one of: " . implode(', ', $validIntervals)
            );
        }

        // Validate date range
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException(
                "Start date must be before or equal to end date"
            );
        }

        // Generate date points based on interval
        $datePoints = $this->generateDatePoints($startDate, $endDate, $interval);

        // Build timeseries by getting balance at each date point
        $timeseries = [];
        foreach ($datePoints as $date) {
            $balance = $this->getAccountBalance($accountId, $date);
            $fiscalYear = $this->periodManager->getFiscalYearForDate($date);

            $timeseries[] = [
                'date' => $date->format('Y-m-d'),
                'balance' => $balance,
                'fiscal_year' => $fiscalYear,
            ];
        }

        return $timeseries;
    }

    /**
     * Generate array of DateTimeImmutable points based on interval
     * 
     * @return array<DateTimeImmutable>
     */
    private function generateDatePoints(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $interval
    ): array {
        $points = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            // For each interval, use the last day of the period
            $periodEnd = match ($interval) {
                'day' => $currentDate,
                'week' => $this->getEndOfWeek($currentDate),
                'month' => $this->getEndOfMonth($currentDate),
                'quarter' => $this->getEndOfQuarter($currentDate),
                'year' => $this->getEndOfFiscalYear($currentDate),
            };

            // Only include if period end is within range
            if ($periodEnd <= $endDate) {
                $points[] = $periodEnd;
            }

            // Advance to next period
            $currentDate = match ($interval) {
                'day' => $currentDate->modify('+1 day'),
                'week' => $currentDate->modify('+1 week'),
                'month' => $currentDate->modify('first day of next month'),
                'quarter' => $currentDate->modify('first day of +3 months'),
                'year' => $this->getStartOfNextFiscalYear($currentDate),
            };
        }

        // Always include the end date if not already included
        if (empty($points) || end($points)->format('Y-m-d') !== $endDate->format('Y-m-d')) {
            $points[] = $endDate;
        }

        return $points;
    }

    /**
     * Get the last day of the week containing the given date
     */
    private function getEndOfWeek(DateTimeImmutable $date): DateTimeImmutable
    {
        // Week ends on Sunday
        return $date->modify('sunday this week');
    }

    /**
     * Get the last day of the month containing the given date
     */
    private function getEndOfMonth(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->modify('last day of this month');
    }

    /**
     * Get the last day of the quarter containing the given date
     */
    private function getEndOfQuarter(DateTimeImmutable $date): DateTimeImmutable
    {
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');

        // Determine which quarter we're in (1-4)
        $quarter = (int) ceil($month / 3);

        // Last month of the quarter
        $lastMonthOfQuarter = $quarter * 3;

        return (new DateTimeImmutable("{$year}-{$lastMonthOfQuarter}-01"))
            ->modify('last day of this month');
    }

    /**
     * Get the last day of the fiscal year containing the given date
     */
    private function getEndOfFiscalYear(DateTimeImmutable $date): DateTimeImmutable
    {
        $fiscalYear = $this->periodManager->getFiscalYearForDate($date);
        $fiscalYearStartDate = $this->periodManager->getFiscalYearStartDate($fiscalYear);

        // Fiscal year ends one day before next fiscal year starts
        return $fiscalYearStartDate->modify('+1 year')->modify('-1 day');
    }

    /**
     * Get the first day of the next fiscal year after the given date
     */
    private function getStartOfNextFiscalYear(DateTimeImmutable $date): DateTimeImmutable
    {
        $fiscalYear = $this->periodManager->getFiscalYearForDate($date);
        $nextFiscalYear = (string) ((int) $fiscalYear + 1);

        return $this->periodManager->getFiscalYearStartDate($nextFiscalYear);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountTree(array $filters = []): array
    {
        $cacheKey = 'finance:accounts:tree:' . md5(json_encode($filters));

        return $this->cache->remember($cacheKey, 300, function () use ($filters) {
            // Get all accounts matching filters
            $accounts = $this->accountRepository->findAll($filters);

            // Build hierarchical tree structure
            return $this->buildAccountTree($accounts);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentEntries(int $limit = 10, array $filters = []): array
    {
        $cacheKey = 'finance:entries:recent:' . md5(json_encode(['limit' => $limit, 'filters' => $filters]));

        return $this->cache->remember($cacheKey, 300, function () use ($limit, $filters) {
            // Add limit and descending order to filters
            $filters['limit'] = $limit;
            $filters['order_by'] = 'entry_date';
            $filters['order_direction'] = 'desc';

            return $this->journalEntryRepository->findAll($filters);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function generateTrialBalance(DateTimeImmutable $asOfDate): array
    {
        $cacheKey = 'finance:trial_balance:' . $asOfDate->format('Y-m-d');

        return $this->cache->remember($cacheKey, 300, function () use ($asOfDate) {
            // Get all accounts
            $accounts = $this->accountRepository->findAll(['is_active' => true]);

            $accountBalances = [];
            $totalDebit = '0';
            $totalCredit = '0';

            foreach ($accounts as $account) {
                $balance = $this->getAccountBalance($account->getId(), $asOfDate);
                $debitBalance = '0';
                $creditBalance = '0';

                // Determine if balance goes in debit or credit column based on account type
                // Assets (1xxx), Expenses (5xxx): positive balance = debit
                // Liabilities (2xxx), Equity (3xxx), Revenue (4xxx): positive balance = credit
                $accountCode = $account->getCode();
                $isDebitNormalBalance = str_starts_with($accountCode, '1') || str_starts_with($accountCode, '5');

                if ($isDebitNormalBalance) {
                    // Assets, Expenses: positive balance = debit
                    if (bccomp($balance, '0', 2) >= 0) {
                        $debitBalance = $balance;
                    } else {
                        // Negative balance for debit normal balance account = credit
                        $creditBalance = bcmul($balance, '-1', 2);
                    }
                } else {
                    // Liabilities, Equity, Revenue: positive balance = credit
                    if (bccomp($balance, '0', 2) >= 0) {
                        $creditBalance = $balance;
                    } else {
                        // Negative balance for credit normal balance account = debit
                        $debitBalance = bcmul($balance, '-1', 2);
                    }
                }

                // Only include accounts with non-zero balances
                if (bccomp($debitBalance, '0', 2) !== 0 || bccomp($creditBalance, '0', 2) !== 0) {
                    $accountBalances[] = [
                        'id' => $account->getId(),
                        'code' => $account->getCode(),
                        'name' => $account->getName(),
                        'debit' => $debitBalance,
                        'credit' => $creditBalance,
                    ];

                    $totalDebit = bcadd($totalDebit, $debitBalance, 2);
                    $totalCredit = bcadd($totalCredit, $creditBalance, 2);
                }
            }

            return [
                'accounts' => $accountBalances,
                'totals' => [
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'balanced' => bccomp($totalDebit, $totalCredit, 2) === 0,
                ],
            ];
        });
    }

    /**
     * Build hierarchical account tree from flat array
     * 
     * @param array<AccountInterface> $accounts
     * @return array<array{id: string, code: string, name: string, children: array}>
     */
    private function buildAccountTree(array $accounts, ?string $parentId = null): array
    {
        $tree = [];

        foreach ($accounts as $account) {
            if ($account->getParentId() === $parentId) {
                $node = [
                    'id' => $account->getId(),
                    'code' => $account->getCode(),
                    'name' => $account->getName(),
                    'type' => $account->getType(),
                    'is_header' => $account->isHeader(),
                    'children' => $this->buildAccountTree($accounts, $account->getId()),
                ];

                $tree[] = $node;
            }
        }

        return $tree;
    }
}
