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
        private readonly EventStoreInterface $eventStore
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
}
