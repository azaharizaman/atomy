<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\VendorInvitation;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class VendorInvitationFactory extends Factory
{
    protected $model = VendorInvitation::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'rfq_id' => Rfq::factory()->create(['tenant_id' => $tenant->id])->id,
            'vendor_id' => Vendor::factory()->create(['tenant_id' => $tenant->id])->id,
            'vendor_email' => fake()->companyEmail(),
            'vendor_name' => fake()->company(),
            'status' => 'pending',
            'invited_at' => now(),
            'responded_at' => null,
            'reminded_at' => null,
            'channel' => fake()->randomElement(['email', 'portal']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'responded_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'responded_at' => now(),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
            'tenant_id' => $vendor->tenant_id,
        ]);
    }
}