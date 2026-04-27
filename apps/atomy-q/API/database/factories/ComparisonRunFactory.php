<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ComparisonRun;
use App\Models\Rfq;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ComparisonRunFactory extends Factory
{
    protected $model = ComparisonRun::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id,
            'rfq_id' => Rfq::factory()->create(['tenant_id' => $tenant->id])->id,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'idempotency_key' => fake()->uuid(),
            'is_preview' => false,
            'created_by' => User::factory()->create(['tenant_id' => $tenant->id])->id,
            'request_payload' => [],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [],
            'readiness_payload' => [],
            'status' => 'draft',
            'version' => 1,
            'expires_at' => now()->addDays(30),
            'discarded_at' => null,
            'discarded_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_preview' => false,
        ]);
    }

    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'final',
            'is_preview' => false,
            'matrix_payload' => ['items' => fake()->numberBetween(3, 10)],
            'scoring_payload' => ['model' => 'weighted'],
        ]);
    }

    public function preview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_preview' => true,
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }
}