<?php

declare(strict_types=1);

namespace Tests\Unit\Factories\Finance;

use App\Models\Finance\JournalEntryLine;
use Database\Factories\Finance\JournalEntryLineFactory;
use Tests\TestCase;

final class JournalEntryLineFactoryTest extends TestCase
{
    /** @test */
    public function it_creates_debit_line(): void
    {
        $line = JournalEntryLine::factory()->debit('1000.0000')->make();

        $this->assertEquals('1000.0000', $line->debit_amount);
        $this->assertEquals('0.0000', $line->credit_amount);
    }

    /** @test */
    public function it_creates_credit_line(): void
    {
        $line = JournalEntryLine::factory()->credit('1000.0000')->make();

        $this->assertEquals('0.0000', $line->debit_amount);
        $this->assertEquals('1000.0000', $line->credit_amount);
    }

    /** @test */
    public function it_sets_specific_amount(): void
    {
        $line = JournalEntryLine::factory()->withAmount('5000.5000')->make();

        $this->assertEquals('5000.5000', $line->debit_amount);
        $this->assertEquals('0.0000', $line->credit_amount);
    }

    /** @test */
    public function it_chains_state_methods_correctly(): void
    {
        $journalEntryId = '01JCQR5XYZ1234567890ABCDEF';
        $accountId = '01JCQR5XYZ9876543210ZYXWVU';

        $line = JournalEntryLine::factory()
            ->debit('2500.7500')
            ->forJournalEntry($journalEntryId)
            ->forAccount($accountId)
            ->make();

        $this->assertEquals('2500.7500', $line->debit_amount);
        $this->assertEquals($journalEntryId, $line->journal_entry_id);
        $this->assertEquals($accountId, $line->account_id);
    }

    /** @test */
    public function it_returns_new_instance_for_chaining(): void
    {
        $factory1 = JournalEntryLine::factory();
        $factory2 = $factory1->debit('1000.0000');

        $this->assertNotSame($factory1, $factory2);
        $this->assertInstanceOf(JournalEntryLineFactory::class, $factory2);
    }
}
