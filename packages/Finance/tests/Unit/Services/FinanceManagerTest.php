<?php

declare(strict_types=1);

namespace Nexus\Finance\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Finance\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Contracts\JournalEntryInterface;
use Nexus\Finance\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Finance\Events\JournalEntryPostedEvent;
use Nexus\Finance\Services\FinanceManager;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * FinanceManager Unit Tests
 * 
 * Tests Finance package's integration with EventStream for SOX compliance.
 * Following TDD: These tests WILL FAIL initially until EventStream integration is implemented.
 */
#[CoversClass(FinanceManager::class)]
final class FinanceManagerTest extends TestCase
{
    #[Test]
    public function it_requires_event_store_dependency_for_sox_compliance(): void
    {
        // This test verifies that FinanceManager requires EventStoreInterface
        // The test will fail until we add EventStore as a constructor dependency
        
        $accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $journalEntryRepository = $this->createMock(JournalEntryRepositoryInterface::class);
        $ledgerRepository = $this->createMock(LedgerRepositoryInterface::class);
        $periodManager = $this->createMock(PeriodManagerInterface::class);
        $eventStore = $this->createMock(EventStoreInterface::class);

        // This will fail if FinanceManager constructor doesn't accept EventStoreInterface
        $financeManager = new FinanceManager(
            $journalEntryRepository,
            $accountRepository,
            $ledgerRepository,
            $periodManager,
            $eventStore
        );

        $this->assertInstanceOf(FinanceManager::class, $financeManager);
    }

    #[Test]
    public function it_publishes_journal_entry_posted_event_when_posting(): void
    {
        // Test verifies JournalEntryPostedEvent is published to EventStream
        // This ensures SOX compliance with immutable audit trail
        
        $accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $journalEntryRepository = $this->createMock(JournalEntryRepositoryInterface::class);
        $ledgerRepository = $this->createMock(LedgerRepositoryInterface::class);
        $periodManager = $this->createMock(PeriodManagerInterface::class);
        $eventStore = $this->createMock(EventStoreInterface::class);

        // Mock journal entry retrieval with all required methods
        $journalEntry = $this->createMock(JournalEntryInterface::class);
        $journalEntry->method('getId')->willReturn('je-123');
        $journalEntry->method('isPosted')->willReturn(false);
        $journalEntry->method('getEntryNumber')->willReturn('JE-2024-0001');
        $journalEntry->method('getDate')->willReturn(new DateTimeImmutable('2024-01-01'));
        $journalEntry->method('getDescription')->willReturn('Test journal entry');
        $journalEntry->method('getTotalDebit')->willReturn('1000.00');
        $journalEntry->method('getTotalCredit')->willReturn('1000.00');
        $journalEntry->method('getLines')->willReturn([]);
        
        $journalEntryRepository->method('find')->with('je-123')->willReturn($journalEntry);
        
        // Expect EventStore to receive JournalEntryPostedEvent
        $eventStore->expects($this->once())
            ->method('append')
            ->with(
                $this->equalTo('je-123'),
                $this->isInstanceOf(\Nexus\Finance\Events\JournalEntryPostedEvent::class)
            );

        $financeManager = new FinanceManager(
            $journalEntryRepository,
            $accountRepository,
            $ledgerRepository,
            $periodManager,
            $eventStore
        );

        // This will fail until postJournalEntry publishes the event
        $financeManager->postJournalEntry('je-123');
    }

    #[Test]
    public function it_publishes_account_debited_and_credited_events_for_each_line(): void
    {
        // Test verifies AccountDebitedEvent and AccountCreditedEvent are published
        // for each journal entry line to enable temporal queries
        
        $accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $journalEntryRepository = $this->createMock(JournalEntryRepositoryInterface::class);
        $ledgerRepository = $this->createMock(LedgerRepositoryInterface::class);
        $periodManager = $this->createMock(PeriodManagerInterface::class);
        $eventStore = $this->createMock(EventStoreInterface::class);

        // Mock journal entry with 2 lines (1 debit, 1 credit)
        $journalEntry = $this->createMock(JournalEntryInterface::class);
        $journalEntry->method('getId')->willReturn('je-456');
        $journalEntry->method('isPosted')->willReturn(false);
        $journalEntry->method('getEntryNumber')->willReturn('JE-2024-0002');
        $journalEntry->method('getDate')->willReturn(new DateTimeImmutable('2024-01-02'));
        $journalEntry->method('getDescription')->willReturn('Test with lines');
        $journalEntry->method('getTotalDebit')->willReturn('1000.00');
        $journalEntry->method('getTotalCredit')->willReturn('1000.00');
        $journalEntry->method('getLines')->willReturn([
            $this->createDebitLine('acc-100'),
            $this->createCreditLine('acc-200')
        ]);
        $journalEntryRepository->method('find')->with('je-456')->willReturn($journalEntry);

        // Expect 3 events: 1 JournalEntryPostedEvent + 2 line events (AccountDebitedEvent + AccountCreditedEvent)
        $eventStore->expects($this->exactly(3))
            ->method('append')
            ->willReturnCallback(function ($aggregateId, $event) {
                $this->assertTrue(
                    $event instanceof \Nexus\Finance\Events\JournalEntryPostedEvent ||
                    $event instanceof \Nexus\Finance\Events\AccountDebitedEvent ||
                    $event instanceof \Nexus\Finance\Events\AccountCreditedEvent,
                    'Event must be a Finance domain event'
                );
                return true;
            });

        $financeManager = new FinanceManager(
            $journalEntryRepository,
            $accountRepository,
            $ledgerRepository,
            $periodManager,
            $eventStore
        );

        // This will fail until postJournalEntry publishes line-level events
        $financeManager->postJournalEntry('je-456');
    }

    private function createDebitLine(string $accountId): object
    {
        $line = new class($accountId) {
            public function __construct(private string $accountId) {}
            public function getAccountId(): string { return $this->accountId; }
            public function isDebit(): bool { return true; }
            public function getDebitAmount(): \Nexus\Finance\ValueObjects\Money {
                return \Nexus\Finance\ValueObjects\Money::of('1000.00', 'MYR');
            }
            public function getCreditAmount(): \Nexus\Finance\ValueObjects\Money {
                return \Nexus\Finance\ValueObjects\Money::of('0.00', 'MYR');
            }
        };
        return $line;
    }

    private function createCreditLine(string $accountId): object
    {
        $line = new class($accountId) {
            public function __construct(private string $accountId) {}
            public function getAccountId(): string { return $this->accountId; }
            public function isDebit(): bool { return false; }
            public function getDebitAmount(): \Nexus\Finance\ValueObjects\Money {
                return \Nexus\Finance\ValueObjects\Money::of('0.00', 'MYR');
            }
            public function getCreditAmount(): \Nexus\Finance\ValueObjects\Money {
                return \Nexus\Finance\ValueObjects\Money::of('1000.00', 'MYR');
            }
        };
        return $line;
    }
}

