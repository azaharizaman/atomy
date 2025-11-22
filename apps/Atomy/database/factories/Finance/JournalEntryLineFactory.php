<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Journal Entry Line Factory
 * 
 * @extends Factory<JournalEntryLine>
 */
final class JournalEntryLineFactory extends Factory
{
    protected $model = JournalEntryLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDebit = $this->faker->boolean();
        $amount = $this->faker->randomFloat(4, 1, 10000);

        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'debit_amount' => $isDebit ? $amount : 0,
            'credit_amount' => $isDebit ? 0 : $amount,
            'debit_currency' => 'MYR',
            'credit_currency' => 'MYR',
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Create a debit line.
     * 
     * @param string $amount Amount to debit
     * @return static
     */
    public function debit(string $amount = '1000.0000'): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => $amount,
            'credit_amount' => '0.0000',
        ]);
    }

    /**
     * Create a credit line.
     * 
     * @param string $amount Amount to credit
     * @return static
     */
    public function credit(string $amount = '1000.0000'): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => '0.0000',
            'credit_amount' => $amount,
        ]);
    }

    /**
     * Set specific amount (debit by default).
     * 
     * @param string $amount Amount value
     * @return static
     */
    public function withAmount(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'debit_amount' => $amount,
            'credit_amount' => '0.0000',
        ]);
    }

    /**
     * Set journal entry ID.
     * 
     * @param string $journalEntryId Journal entry ID
     * @return static
     */
    public function forJournalEntry(string $journalEntryId): static
    {
        return $this->state(fn (array $attributes) => [
            'journal_entry_id' => $journalEntryId,
        ]);
    }

    /**
     * Set account ID.
     * 
     * @param string $accountId Account ID
     * @return static
     */
    public function forAccount(string $accountId): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $accountId,
        ]);
    }
}
