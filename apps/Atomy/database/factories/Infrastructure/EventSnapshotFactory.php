<?php

declare(strict_types=1);

namespace Database\Factories\Infrastructure;

use App\Models\Infrastructure\EventSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Infrastructure\EventSnapshot>
 */
final class EventSnapshotFactory extends Factory
{
    protected $model = EventSnapshot::class;
    
    public function definition(): array
    {
        $state = ['balance' => 1000, 'status' => 'active'];
        
        return [
            'aggregate_id' => Str::ulid()->toString(),
            'version' => 10,
            'state' => $state,
            'checksum' => hash('sha256', json_encode($state)),
        ];
    }
    
    /**
     * Set a specific aggregate ID for the snapshot.
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
     * Set a specific version for the snapshot.
     * 
     * @param int $version The snapshot version
     * @return static
     */
    public function atVersion(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }
    
    /**
     * Set snapshot state.
     * 
     * @param array $state Aggregate state data
     * @return static
     */
    public function withState(array $state): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => $state,
            'checksum' => hash('sha256', json_encode($state)),
        ]);
    }
}
