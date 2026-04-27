<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rfq;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RfqFactory extends Factory
{
    protected $model = Rfq::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_number' => 'RFQ-' . fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement([
                'Rotating equipment',
                'Instrumentation',
                'Valves & piping',
                'Electrical',
                'Instrumentation',
                'Process equipment',
            ]),
            'department' => fake()->randomElement(['Maintenance', 'Projects', 'Operations']),
            'status' => 'draft',
            'owner_id' => User::factory(),
            'project_id' => Project::factory(),
            'estimated_value' => fake()->randomFloat(2, 10000, 500000),
            'savings_percentage' => fake()->randomFloat(2, 2.5, 18.0),
            'submission_deadline' => fake()->dateTimeBetween('+1 week', '+4 weeks'),
            'closing_date' => fake()->dateTimeBetween('+2 weeks', '+6 weeks'),
            'expected_award_at' => fake()->dateTimeBetween('+3 weeks', '+8 weeks'),
            'technical_review_due_at' => fake()->dateTimeBetween('+2 weeks', '+5 weeks'),
            'financial_review_due_at' => fake()->dateTimeBetween('+2 weeks', '+5 weeks'),
            'payment_terms' => fake()->randomElement(['Net 30', 'Net 45 EOM', 'Net 60']),
            'evaluation_method' => 'weighted',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'submission_deadline' => fake()->dateTimeBetween('+1 week', '+4 weeks'),
            'closing_date' => fake()->dateTimeBetween('+2 weeks', '+6 weeks'),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'submission_deadline' => fake()->dateTimeBetween('+3 days', '+2 weeks'),
            'closing_date' => fake()->dateTimeBetween('+1 week', '+3 weeks'),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closing_date' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'closing_date' => fake()->dateTimeBetween('-4 weeks', '-2 weeks'),
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
            'tenant_id' => $project->tenant_id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ]);
    }

    public function withLineItems(int $count = 3): static
    {
        return $this->has(RfqLineItem::factory()->count($count));
    }
}