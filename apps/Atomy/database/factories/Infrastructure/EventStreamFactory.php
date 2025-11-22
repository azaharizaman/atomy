<?php

declare(strict_types=1);

namespace Database\Factories\Infrastructure;

use App\Models\Infrastructure\EventStream;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Infrastructure\EventStream>
 */
final class EventStreamFactory extends Factory
{
    protected $model = EventStream::class;
    
    public function definition(): array
    {
        return [
            'event_id' => Str::ulid()->toString(),
            'aggregate_id' => Str::ulid()->toString(),
            'event_type' => 'App\\Events\\TestEvent',
            'version' => 1,
            'occurred_at' => now(),
            'payload' => ['test' => 'data'],
            'causation_id' => null,
            'correlation_id' => Str::ulid()->toString(),
            'tenant_id' => Str::ulid()->toString(),
            'user_id' => Str::ulid()->toString(),
            'metadata' => ['source' => 'test'],
        ];
    }
    
    /**
     * Set a specific aggregate ID for the event.
     * 
     * @param string $aggregateId The aggregate identifier
     * @return static
     */
    public function forAggregate(string $aggregateId): static
    {
        return $this->state(fn (array $attributes) => [
            'aggregate_id' => $aggregateId,
        ]);
    }
    
    /**
     * Set a specific version for the event.
     * 
     * @param int $version The event version
     * @return static
     */
    public function withVersion(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }
    
    /**
     * Mark event as published (has correlation ID).
     * 
     * @return static
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'correlation_id' => Str::ulid()->toString(),
        ]);
    }
    
    /**
     * Mark event as pending (no correlation ID yet).
     * 
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'correlation_id' => null,
        ]);
    }
    
    /**
     * Set event type.
     * 
     * @param string $eventType Fully qualified class name
     * @return static
     */
    public function ofType(string $eventType): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $eventType,
        ]);
    }
    
    /**
     * Set event payload.
     * 
     * @param array $payload Event payload data
     * @return static
     */
    public function withPayload(array $payload): static
    {
        return $this->state(fn (array $attributes) => [
            'payload' => $payload,
        ]);
    }
}
