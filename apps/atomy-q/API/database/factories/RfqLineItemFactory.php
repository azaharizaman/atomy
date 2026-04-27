<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RfqLineItemFactory extends Factory
{
    protected $model = RfqLineItem::class;

    public function definition(): array
    {
        $rfq = Rfq::factory()->create();

        return [
            'tenant_id' => $rfq->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => fake()->sentence(6),
            'quantity' => fake()->randomFloat(4, 1, 100),
            'uom' => fake()->randomElement(['EA', 'KG', 'L', 'M', 'SET', 'BOX']),
            'unit_price' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'USD',
            'specifications' => [],
            'sort_order' => 0,
        ];
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
        ]);
    }
}