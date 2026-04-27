<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\Rfq;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class AwardFactory extends Factory
{
    protected $model = Award::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $vendor = Vendor::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'tenant_id' => $tenant->id,
            'rfq_id' => Rfq::factory()->create(['tenant_id' => $tenant->id])->id,
            'comparison_run_id' => ComparisonRun::factory()->create(['tenant_id' => $tenant->id])->id,
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => null,
            'signed_off_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'signoff_at' => null,
        ]);
    }

    public function signedOff(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'signed_off',
                'signoff_at' => now(),
                'signed_off_by' => User::factory()->state(['tenant_id' => $attributes['tenant_id']]),
            ];
        });
    }

    public function protested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'protested',
            'protest_id' => (string) Str::ulid(),
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