<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Approval;
use App\Models\Rfq;
use App\Models\ComparisonRun;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ApprovalFactory extends Factory
{
    protected $model = Approval::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => function (array $attributes) {
                return Rfq::factory()->create(['tenant_id' => $attributes['tenant_id']])->id;
            },
            'comparison_run_id' => function (array $attributes) {
                $rfq = Rfq::find($attributes['rfq_id']);
                return ComparisonRun::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'rfq_id' => $rfq->id,
                ])->id;
            },
            'type' => 'value_approval',
            'status' => 'pending',
            'requested_by' => function (array $attributes) {
                return User::factory()->create(['tenant_id' => $attributes['tenant_id']])->id;
            },
            'requested_at' => now(),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'currency' => 'USD',
            'level' => 1,
            'notes' => null,
            'approved_at' => null,
            'approved_by' => null,
            'snoozed_until' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => function (array $resolvedAttributes) {
                return User::factory()->create(['tenant_id' => $resolvedAttributes['tenant_id']])->id;
            },
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => function (array $resolvedAttributes) {
                return User::factory()->create(['tenant_id' => $resolvedAttributes['tenant_id']])->id;
            },
            'notes' => fake()->sentence(),
        ]);
    }

    public function snoozed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'snoozed',
            'snoozed_until' => fake()->dateTimeBetween('+1 day', '+7 days'),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
            'comparison_run_id' => ComparisonRun::factory()->create([
                'tenant_id' => $rfq->tenant_id,
                'rfq_id' => $rfq->id,
            ])->id,
        ]);
    }

    public function forComparisonRun(ComparisonRun $comparisonRun): static
    {
        return $this->state(fn (array $attributes) => [
            'comparison_run_id' => $comparisonRun->id,
            'tenant_id' => $comparisonRun->tenant_id,
            'rfq_id' => $comparisonRun->rfq_id,
        ]);
    }
}