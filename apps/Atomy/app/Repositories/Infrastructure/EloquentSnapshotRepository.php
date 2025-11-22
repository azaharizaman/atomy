<?php

declare(strict_types=1);

namespace App\Repositories\Infrastructure;

use App\Models\Infrastructure\EventSnapshot;
use DateTimeImmutable;
use Nexus\EventStream\Contracts\SnapshotInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;

final readonly class EloquentSnapshotRepository implements SnapshotRepositoryInterface
{
    public function __construct()
    {
        // All dependencies injected as interfaces
    }
    
    /**
     * Save a snapshot of aggregate state
     */
    public function save(string $aggregateId, int $version, array $state): void
    {
        $checksum = hash('sha256', json_encode($state));
        
        EventSnapshot::create([
            'aggregate_id' => $aggregateId,
            'version' => $version,
            'state' => $state,
            'checksum' => $checksum,
        ]);
    }
    
    /**
     * Get the latest snapshot for an aggregate
     */
    public function getLatest(string $aggregateId): ?SnapshotInterface
    {
        return EventSnapshot::where('aggregate_id', $aggregateId)
            ->orderByDesc('version')
            ->first();
    }
    
    /**
     * Get a snapshot at or before a specific version
     */
    public function getAtVersion(string $aggregateId, int $version): ?SnapshotInterface
    {
        return EventSnapshot::where('aggregate_id', $aggregateId)
            ->where('version', '<=', $version)
            ->orderByDesc('version')
            ->first();
    }
    
    /**
     * Delete all snapshots older than a specific date
     */
    public function deleteOlderThan(DateTimeImmutable $before): int
    {
        return EventSnapshot::where('created_at', '<', $before)->delete();
    }
    
    /**
     * Check if a snapshot exists for an aggregate
     */
    public function exists(string $aggregateId): bool
    {
        return EventSnapshot::where('aggregate_id', $aggregateId)->exists();
    }
}
