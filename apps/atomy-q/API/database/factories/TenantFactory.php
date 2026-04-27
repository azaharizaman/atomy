<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('????')),
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'status' => 'pending',
            'timezone' => fake()->randomElement(['UTC', 'Europe/Oslo', 'America/New_York']),
            'locale' => 'en',
            'currency' => fake()->randomElement(['USD', 'NOK', 'EUR']),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'max_users' => fake()->randomElement([10, 25, 50, 100]),
            'storage_quota' => fake()->randomElement([1073741824, 5368709120, 10737418240]),
            'storage_used' => 0,
            'rate_limit' => 60,
            'is_readonly' => false,
            'onboarding_progress' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'onboarding_progress' => 100,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}