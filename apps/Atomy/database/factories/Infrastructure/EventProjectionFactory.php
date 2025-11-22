<?php

declare(strict_types=1);

namespace Database\Factories\Infrastructure;

use App\Models\Infrastructure\EventProjection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Infrastructure\EventProjection>
 */
final class EventProjectionFactory extends Factory
{
    protected $model = EventProjection::class;
    
    public function definition(): array
    {
        return [
            'projector_name' => 'TestProjector',
            'last_processed_event_id' => Str::ulid()->toString(),
            'last_processed_version' => 1,
            'last_processed_at' => now(),
            'status' => 'active',
            'error_message' => null,
        ];
    }
    
    /**
     * Mark projection as active.
     * 
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'error_message' => null,
        ]);
    }
    
    /**
     * Mark projection as paused.
     * 
     * @return static
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }
    
    /**
     * Mark projection as having an error.
     * 
     * @param string $errorMessage The error message
     * @return static
     */
    public function withError(string $errorMessage): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'error_message' => $errorMessage,
        ]);
    }
    
    /**
     * Set the projector name.
     * 
     * @param string $name The projector name
     * @return static
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'projector_name' => $name,
        ]);
    }
}
