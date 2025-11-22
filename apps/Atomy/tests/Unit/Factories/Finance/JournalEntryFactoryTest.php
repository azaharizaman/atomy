<?php

declare(strict_types=1);

namespace Tests\Unit\Factories\Finance;

use App\Models\Finance\JournalEntry;
use Database\Factories\Finance\JournalEntryFactory;
use Nexus\Finance\Enums\JournalEntryStatus;
use Tests\TestCase;

final class JournalEntryFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_draft_journal_entry(): void
    {
        $entry = JournalEntry::factory()->draft()->make();

        $this->assertEquals(JournalEntryStatus::Draft, $entry->status);
        $this->assertNull($entry->posted_at);
        $this->assertNull($entry->posted_by);
    }

    /** @test */
    public function it_creates_posted_journal_entry(): void
    {
        $entry = JournalEntry::factory()->posted()->make();

        $this->assertEquals(JournalEntryStatus::Posted, $entry->status);
        $this->assertNotNull($entry->posted_at);
        $this->assertNotNull($entry->posted_by);
    }

    /** @test */
    public function it_creates_reversed_journal_entry(): void
    {
        $entry = JournalEntry::factory()->reversed()->make();

        $this->assertEquals(JournalEntryStatus::Reversed, $entry->status);
    }

    /** @test */
    public function it_sets_custom_entry_number(): void
    {
        $entry = JournalEntry::factory()
            ->withEntryNumber('JE-2024-11-9999')
            ->make();

        $this->assertEquals('JE-2024-11-9999', $entry->entry_number);
    }

    /** @test */
    public function it_sets_entry_date(): void
    {
        $entry = JournalEntry::factory()
            ->onDate('2024-11-22')
            ->make();

        $this->assertEquals('2024-11-22', $entry->entry_date->format('Y-m-d'));
    }

    /** @test */
    public function it_chains_state_methods_correctly(): void
    {
        $entry = JournalEntry::factory()
            ->posted()
            ->withEntryNumber('JE-2024-11-0001')
            ->withReference('REF-001')
            ->make();

        $this->assertEquals(JournalEntryStatus::Posted, $entry->status);
        $this->assertEquals('JE-2024-11-0001', $entry->entry_number);
        $this->assertEquals('REF-001', $entry->reference);
    }

    /** @test */
    public function it_returns_new_instance_for_chaining(): void
    {
        $factory1 = JournalEntry::factory();
        $factory2 = $factory1->posted();

        $this->assertNotSame($factory1, $factory2);
        $this->assertInstanceOf(JournalEntryFactory::class, $factory2);
    }
}
