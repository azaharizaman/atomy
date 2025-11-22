<?php

declare(strict_types=1);

namespace Database\Factories\Currency;

use App\Models\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Currency Factory
 * 
 * @extends Factory<Currency>
 */
final class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->tostring(),
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'numeric_code' => '840',
            'is_active' => true,
        ];
    }

    /**
     * Malaysian Ringgit
     * 
     * @return static
     */
    public function myr(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'MYR',
            'name' => 'Malaysian Ringgit',
            'symbol' => 'RM',
            'decimal_places' => 2,
            'numeric_code' => '458',
        ]);
    }

    /**
     * Euro
     * 
     * @return static
     */
    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'decimal_places' => 2,
            'numeric_code' => '978',
        ]);
    }

    /**
     * Japanese Yen (zero decimal)
     * 
     * @return static
     */
    public function jpy(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'decimal_places' => 0,
            'numeric_code' => '392',
        ]);
    }

    /**
     * British Pound
     * 
     * @return static
     */
    public function gbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'decimal_places' => 2,
            'numeric_code' => '826',
        ]);
    }

    /**
     * Bahraini Dinar (three decimal)
     * 
     * @return static
     */
    public function bhd(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'BHD',
            'name' => 'Bahraini Dinar',
            'symbol' => 'BD',
            'decimal_places' => 3,
            'numeric_code' => '048',
        ]);
    }

    /**
     * Set custom currency code
     * 
     * @return static
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }

    /**
     * Set custom name
     * 
     * @return static
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Mark currency as inactive
     * 
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Mark currency as active
     * 
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
