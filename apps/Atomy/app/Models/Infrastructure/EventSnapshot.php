<?php

declare(strict_types=1);

namespace App\Models\Infrastructure;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nexus\EventStream\Contracts\SnapshotInterface;

/**
 * EventSnapshot Model
 * 
 * Represents a snapshot of aggregate state at a specific version.
 * Implements SnapshotInterface for framework-agnostic snapshots.
 * 
 * @property string $id
 * @property string $aggregate_id
 * @property int $version
 * @property array $state
 * @property string $checksum
 * @property \Carbon\Carbon $created_at
 */
final class EventSnapshot extends Model implements SnapshotInterface
{
    use HasFactory;
    
    protected $table = 'event_snapshots';
    
    public $timestamps = true;
    
    protected $fillable = [
        'aggregate_id',
        'version',
        'state',
        'checksum',
    ];
    
    protected $casts = [
        'version' => 'integer',
        'state' => 'array',
    ];
    
    /**
     * Get the aggregate ID this snapshot belongs to
     */
    public function getAggregateId(): string
    {
        return $this->aggregate_id;
    }
    
    /**
     * Get the version at which this snapshot was taken
     */
    public function getVersion(): int
    {
        return $this->version;
    }
    
    /**
     * Get the aggregate state
     */
    public function getState(): array
    {
        return $this->state ?? [];
    }
    
    /**
     * Get the timestamp when this snapshot was created
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }
    
    /**
     * Get the checksum for snapshot validation
     */
    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
