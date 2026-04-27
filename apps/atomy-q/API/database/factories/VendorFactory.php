<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'registration_number' => fake()->unique()->numerify('##########'),
            'tax_id' => fake()->unique()->numerify('##########'),
            'legal_name' => fake()->company(),
            'display_name' => fake()->company(),
            'country_of_registration' => fake()->countryCode(),
            'primary_contact_name' => fake()->name(),
            'primary_contact_email' => fake()->companyEmail(),
            'primary_contact_phone' => fake()->phoneNumber(),
            'status' => 'pending',
            'onboarded_at' => null,
            'metadata' => [],
            'approved_by_user_id' => null,
            'approved_at' => null,
            'approval_note' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'onboarded_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'restricted',
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}