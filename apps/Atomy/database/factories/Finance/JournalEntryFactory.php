<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Finance\Enums\JournalEntryStatus;

/**
 * Journal Entry Factory
 * 
 * @extends Factory<JournalEntry>
 */
final class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_number' => 'JE-' . $this->faker->unique()->numerify('2024-####'),
            'entry_date' => $this->faker->date(),
            'reference' => $this->faker->optional()->regexify('[A-Z0-9]{10}'),
            'description' => $this->faker->sentence(),
            'status' => JournalEntryStatus::Draft,
            'created_by' => $this->faker->uuid(),
            'posted_at' => null,
            'posted_by' => null,
        ];
    }

    /**
     * Mark journal entry as draft.
     * 
     * @return static
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalEntryStatus::Draft,
            'posted_at' => null,
            'posted_by' => null,
        ]);
    }

    /**
     * Mark journal entry as posted.
     * 
     * @return static
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalEntryStatus::Posted,
            'posted_at' => now(),
            'posted_by' => $this->faker->uuid(),
        ]);
    }

    /**
     * Mark journal entry as reversed.
     * 
     * @return static
     */
    public function reversed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JournalEntryStatus::Reversed,
            'posted_at' => now()->subDays(1),
            'posted_by' => $this->faker->uuid(),
        ]);
    }

    /**
     * Set specific entry number.
     * 
     * @param string $entryNumber Entry number
     * @return static
     */
    public function withEntryNumber(string $entryNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_number' => $entryNumber,
        ]);
    }

    /**
     * Set entry date.
     * 
     * @param string $date Date in Y-m-d format
     * @return static
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_date' => $date,
        ]);
    }

    /**
     * Set reference number.
     * 
     * @param string $reference Reference number
     * @return static
     */
    public function withReference(string $reference): static
    {
        return $this->state(fn (array $attributes) => [
            'reference' => $reference,
        ]);
    }
}
