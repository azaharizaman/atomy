<?php

declare(strict_types=1);

namespace Database\Factories\Currency;

use App\Models\Currency\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Exchange Rate Factory
 * 
 * @extends Factory<ExchangeRate>
 */
final class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toString(),
            'from_currency' => 'USD',
            'to_currency' => 'MYR',
            'rate' => '4.732500',
            'effective_date' => now()->toDateString(),
            'source' => null,
        ];
    }

    /**
     * USD to EUR rate
     * 
     * @return static
     */
    public function usdToEur(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => '0.850000',
        ]);
    }

    /**
     * EUR to USD rate
     * 
     * @return static
     */
    public function eurToUsd(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency' => 'EUR',
            'to_currency' => 'USD',
            'rate' => '1.176471',
        ]);
    }

    /**
     * USD to JPY rate
     * 
     * @return static
     */
    public function usdToJpy(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency' => 'USD',
            'to_currency' => 'JPY',
            'rate' => '149.500000',
        ]);
    }

    /**
     * USD to GBP rate
     * 
     * @return static
     */
    public function usdToGbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => '0.790000',
        ]);
    }

    /**
     * Set custom currency pair
     * 
     * @return static
     */
    public function pair(string $from, string $to): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency' => $from,
            'to_currency' => $to,
        ]);
    }

    /**
     * Set custom rate
     * 
     * @return static
     */
    public function withRate(string $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $rate,
        ]);
    }

    /**
     * Set effective date
     * 
     * @return static
     */
    public function onDate(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date instanceof \DateTimeImmutable 
                ? $date->format('Y-m-d') 
                : $date->format('Y-m-d'),
        ]);
    }

    /**
     * Set rate source
     * 
     * @return static
     */
    public function fromSource(string $source): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => $source,
        ]);
    }

    /**
     * ECB source
     * 
     * @return static
     */
    public function fromEcb(): static
    {
        return $this->fromSource('ECB');
    }

    /**
     * Fixer.io source
     * 
     * @return static
     */
    public function fromFixer(): static
    {
        return $this->fromSource('Fixer.io');
    }
}
