<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Finance;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\Account;
use App\Repositories\Finance\EloquentJournalEntryRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Finance\Enums\JournalEntryStatus;
use Nexus\Finance\Exceptions\JournalEntryAlreadyPostedException;
use Tests\TestCase;

final class JournalEntryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentJournalEntryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentJournalEntryRepository();
    }

    /** @test */
    public function it_can_find_journal_entry_by_id(): void
    {
        $entry = JournalEntry::factory()->create();

        $found = $this->repository->find($entry->id);

        $this->assertNotNull($found);
        $this->assertEquals($entry->id, $found->getId());
    }

    /** @test */
    public function it_returns_null_when_journal_entry_not_found(): void
    {
        $found = $this->repository->find('non-existent-id');

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_find_journal_entry_by_entry_number(): void
    {
        $entry = JournalEntry::factory()
            ->withEntryNumber('JE-2024-11-0001')
            ->create();

        $found = $this->repository->findByEntryNumber('JE-2024-11-0001');

        $this->assertNotNull($found);
        $this->assertEquals('JE-2024-11-0001', $found->getEntryNumber());
    }

    /** @test */
    public function it_can_find_all_journal_entries(): void
    {
        JournalEntry::factory()->count(3)->create();

        $entries = $this->repository->findAll();

        $this->assertCount(3, $entries);
    }

    /** @test */
    public function it_can_filter_journal_entries_by_status(): void
    {
        JournalEntry::factory()->draft()->count(2)->create();
        JournalEntry::factory()->posted()->create();

        $drafts = $this->repository->findAll(['status' => JournalEntryStatus::Draft->value]);

        $this->assertCount(2, $drafts);
        foreach ($drafts as $entry) {
            $this->assertEquals(JournalEntryStatus::Draft->value, $entry->getStatus());
        }
    }

    /** @test */
    public function it_can_filter_journal_entries_by_date_range(): void
    {
        JournalEntry::factory()->onDate('2024-01-01')->create();
        JournalEntry::factory()->onDate('2024-02-15')->create();
        JournalEntry::factory()->onDate('2024-03-31')->create();

        $entries = $this->repository->findAll([
            'start_date' => '2024-02-01',
            'end_date' => '2024-02-28',
        ]);

        $this->assertCount(1, $entries);
    }

    /** @test */
    public function it_can_find_journal_entries_by_account(): void
    {
        $account = Account::factory()->create();
        $otherAccount = Account::factory()->create();
        
        $entry1 = JournalEntry::factory()->create();
        $entry2 = JournalEntry::factory()->create();
        $entry3 = JournalEntry::factory()->create();

        // Entry1 and Entry2 use target account
        \App\Models\Finance\JournalEntryLine::factory()
            ->forJournalEntry($entry1->id)
            ->forAccount($account->id)
            ->create();
            
        \App\Models\Finance\JournalEntryLine::factory()
            ->forJournalEntry($entry2->id)
            ->forAccount($account->id)
            ->create();

        // Entry3 uses different account
        \App\Models\Finance\JournalEntryLine::factory()
            ->forJournalEntry($entry3->id)
            ->forAccount($otherAccount->id)
            ->create();

        $entries = $this->repository->findByAccount($account->id);

        $this->assertCount(2, $entries);
    }

    /** @test */
    public function it_can_find_journal_entries_by_date_range_method(): void
    {
        JournalEntry::factory()->onDate('2024-01-15')->create();
        JournalEntry::factory()->onDate('2024-02-15')->create();
        JournalEntry::factory()->onDate('2024-03-15')->create();

        $startDate = new DateTimeImmutable('2024-02-01');
        $endDate = new DateTimeImmutable('2024-03-31');

        $entries = $this->repository->findByDateRange($startDate, $endDate);

        $this->assertCount(2, $entries);
    }

    /** @test */
    public function it_can_save_journal_entry(): void
    {
        $entry = JournalEntry::factory()->make();

        $this->repository->save($entry);

        $this->assertDatabaseHas('journal_entries', [
            'entry_number' => $entry->entry_number,
        ]);
    }

    /** @test */
    public function it_can_delete_draft_journal_entry(): void
    {
        $entry = JournalEntry::factory()->draft()->create();

        $this->repository->delete($entry->id);

        $this->assertSoftDeleted('journal_entries', ['id' => $entry->id]);
    }

    /** @test */
    public function it_throws_exception_when_deleting_posted_journal_entry(): void
    {
        $entry = JournalEntry::factory()->posted()->create();

        $this->expectException(JournalEntryAlreadyPostedException::class);

        $this->repository->delete($entry->id);
    }

    /** @test */
    public function it_generates_next_entry_number_for_new_month(): void
    {
        $date = new DateTimeImmutable('2024-11-01');

        $entryNumber = $this->repository->getNextEntryNumber($date);

        $this->assertEquals('JE-2024-11-0001', $entryNumber);
    }

    /** @test */
    public function it_increments_entry_number_for_existing_month(): void
    {
        JournalEntry::factory()
            ->withEntryNumber('JE-2024-11-0001')
            ->create();
            
        JournalEntry::factory()
            ->withEntryNumber('JE-2024-11-0002')
            ->create();

        $date = new DateTimeImmutable('2024-11-15');
        $entryNumber = $this->repository->getNextEntryNumber($date);

        $this->assertEquals('JE-2024-11-0003', $entryNumber);
    }
}
