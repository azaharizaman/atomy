<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Tests\Unit\Services;

use Nexus\Common\Contracts\ClockInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\JournalEntry\Contracts\CurrencyConverterInterface;
use Nexus\JournalEntry\Contracts\JournalEntryInterface;
use Nexus\JournalEntry\Contracts\JournalEntryPersistInterface;
use Nexus\JournalEntry\Contracts\JournalEntryQueryInterface;
use Nexus\JournalEntry\Contracts\LedgerQueryInterface;
use Nexus\JournalEntry\Enums\JournalEntryStatus;
use Nexus\JournalEntry\Exceptions\InvalidJournalEntryException;
use Nexus\JournalEntry\Exceptions\JournalEntryAlreadyPostedException;
use Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException;
use Nexus\JournalEntry\Exceptions\UnbalancedJournalEntryException;
use Nexus\JournalEntry\Services\JournalEntryManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JournalEntryManagerTest extends TestCase
{
    private JournalEntryManager $manager;
    private MockObject&JournalEntryQueryInterface $query;
    private MockObject&JournalEntryPersistInterface $persist;
    private MockObject&LedgerQueryInterface $ledgerQuery;
    private MockObject&ClockInterface $clock;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->query = $this->createMock(JournalEntryQueryInterface::class);
        $this->persist = $this->createMock(JournalEntryPersistInterface::class);
        $this->ledgerQuery = $this->createMock(LedgerQueryInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new JournalEntryManager(
            $this->query,
            $this->persist,
            $this->ledgerQuery,
            $this->clock,
            $this->logger
        );
    }

    public function test_createEntry_validates_empty_description(): void
    {
        $this->expectException(InvalidJournalEntryException::class);
        
        $this->manager->createEntry([
            'description' => '',
            'lines' => [
                ['account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
                ['account_id' => '2', 'debit' => '0.00', 'credit' => '100.00'],
            ],
        ]);
    }

    public function test_createEntry_validates_minimum_lines(): void
    {
        $this->expectException(InvalidJournalEntryException::class);

        $this->manager->createEntry([
            'description' => 'Test entry',
            'lines' => [
                ['account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
            ],
        ]);
    }

    public function test_createEntry_validates_balanced_entry(): void
    {
        $this->expectException(UnbalancedJournalEntryException::class);

        $this->manager->createEntry([
            'description' => 'Test entry',
            'lines' => [
                ['account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
                ['account_id' => '2', 'debit' => '0.00', 'credit' => '50.00'],
            ],
        ]);
    }

    public function test_createEntry_creates_balanced_entry(): void
    {
        $entryData = [
            'description' => 'Test entry',
            'lines' => [
                ['account_id' => '1', 'debit' => '100.00', 'credit' => '0.00'],
                ['account_id' => '2', 'debit' => '0.00', 'credit' => '100.00'],
            ],
        ];

        $mockEntry = $this->createMock(JournalEntryInterface::class);
        $mockEntry->method('getId')->willReturn('entry-1');
        $mockEntry->method('getNumber')->willReturn('JE-001');

        $this->persist->expects($this->once())
            ->method('create')
            ->with($entryData)
            ->willReturn($mockEntry);

        $result = $this->manager->createEntry($entryData);

        $this->assertSame($mockEntry, $result);
    }

    public function test_postEntry_throws_exception_for_nonexistent_entry(): void
    {
        $this->query->method('findById')->willReturn(null);

        $this->expectException(JournalEntryNotFoundException::class);
        
        $this->manager->postEntry('nonexistent-id');
    }

    public function test_postEntry_throws_exception_for_already_posted_entry(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        $mockEntry->method('getStatus')->willReturn(JournalEntryStatus::POSTED);

        $this->query->method('findById')->willReturn($mockEntry);

        $this->expectException(JournalEntryAlreadyPostedException::class);
        
        $this->manager->postEntry('entry-1');
    }

    public function test_postEntry_validates_balance_before_posting(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        $mockEntry->method('getStatus')->willReturn(JournalEntryStatus::DRAFT);
        $mockEntry->method('getTotalDebit')->willReturn(Money::of(100, 'MYR'));
        $mockEntry->method('getTotalCredit')->willReturn(Money::of(50, 'MYR')); // Unbalanced

        $this->query->method('findById')->willReturn($mockEntry);

        $this->expectException(UnbalancedJournalEntryException::class);
        
        $this->manager->postEntry('entry-1');
    }

    public function test_postEntry_posts_valid_draft_entry(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        $mockEntry->method('getStatus')->willReturn(JournalEntryStatus::DRAFT);
        $mockEntry->method('getId')->willReturn('entry-1');
        $mockEntry->method('getNumber')->willReturn('JE-001');
        $mockEntry->method('getTotalDebit')->willReturn(Money::of(100, 'MYR'));
        $mockEntry->method('getTotalCredit')->willReturn(Money::of(100, 'MYR'));

        $this->query->method('findById')->willReturn($mockEntry);

        $postedEntry = $this->createMock(JournalEntryInterface::class);
        $postedEntry->method('getNumber')->willReturn('JE-001');
        
        $this->persist->expects($this->once())
            ->method('updateStatus')
            ->with('entry-1', JournalEntryStatus::POSTED)
            ->willReturn($postedEntry);

        $result = $this->manager->postEntry('entry-1');

        $this->assertSame($postedEntry, $result);
    }

    public function test_deleteEntry_throws_exception_for_nonexistent_entry(): void
    {
        $this->query->method('findById')->willReturn(null);

        $this->expectException(JournalEntryNotFoundException::class);
        
        $this->manager->deleteEntry('nonexistent-id');
    }

    public function test_deleteEntry_throws_exception_for_posted_entry(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        $mockEntry->method('getStatus')->willReturn(JournalEntryStatus::POSTED);

        $this->query->method('findById')->willReturn($mockEntry);

        $this->expectException(JournalEntryAlreadyPostedException::class);
        
        $this->manager->deleteEntry('entry-1');
    }

    public function test_deleteEntry_deletes_draft_entry(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        $mockEntry->method('getStatus')->willReturn(JournalEntryStatus::DRAFT);
        $mockEntry->method('getId')->willReturn('entry-1');
        $mockEntry->method('getNumber')->willReturn('JE-001');

        $this->query->method('findById')->willReturn($mockEntry);

        $this->persist->expects($this->once())
            ->method('delete')
            ->with('entry-1');

        $this->manager->deleteEntry('entry-1');
    }

    public function test_findById_throws_exception_for_nonexistent_entry(): void
    {
        $this->query->method('findById')->willReturn(null);

        $this->expectException(JournalEntryNotFoundException::class);
        
        $this->manager->findById('nonexistent-id');
    }

    public function test_findById_returns_existing_entry(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        
        $this->query->expects($this->once())
            ->method('findById')
            ->with('entry-1')
            ->willReturn($mockEntry);

        $result = $this->manager->findById('entry-1');

        $this->assertSame($mockEntry, $result);
    }

    public function test_findByNumber_throws_exception_for_nonexistent_entry(): void
    {
        $this->query->method('findByNumber')->willReturn(null);

        $this->expectException(JournalEntryNotFoundException::class);
        
        $this->manager->findByNumber('JE-999');
    }

    public function test_findByNumber_returns_existing_entry(): void
    {
        $mockEntry = $this->createMock(JournalEntryInterface::class);
        
        $this->query->expects($this->once())
            ->method('findByNumber')
            ->with('JE-001')
            ->willReturn($mockEntry);

        $result = $this->manager->findByNumber('JE-001');

        $this->assertSame($mockEntry, $result);
    }
}
