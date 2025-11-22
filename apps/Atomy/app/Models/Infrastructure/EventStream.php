<?php

declare(strict_types=1);

namespace App\Models\Infrastructure;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nexus\EventStream\Contracts\EventInterface;

/**
 * EventStream Model
 * 
 * Represents a single event in the event stream.
 * Implements EventInterface for framework-agnostic event sourcing.
 * 
 * @property string $id
 * @property string $event_id
 * @property string $aggregate_id
 * @property string $event_type
 * @property int $version
 * @property DateTimeImmutable $occurred_at
 * @property array $payload
 * @property string|null $causation_id
 * @property string|null $correlation_id
 * @property string $tenant_id
 * @property string|null $user_id
 * @property array|null $metadata
 */
final class EventStream extends Model implements EventInterface
{
    use HasFactory;
    
    protected $table = 'event_streams';
    
    protected $fillable = [
        'event_id',
        'aggregate_id',
        'event_type',
        'version',
        'occurred_at',
        'payload',
        'causation_id',
        'correlation_id',
        'tenant_id',
        'user_id',
        'metadata',
    ];
    
    protected $casts = [
        'version' => 'integer',
        'occurred_at' => 'datetime:Y-m-d H:i:s.u',
        'payload' => 'array',
        'metadata' => 'array',
    ];
    
    /**
     * Get the unique event identifier (ULID)
     */
    public function getEventId(): string
    {
        return $this->event_id;
    }
    
    /**
     * Get the aggregate ID this event belongs to
     */
    public function getAggregateId(): string
    {
        return $this->aggregate_id;
    }
    
    /**
     * Get the event type (fully qualified class name)
     */
    public function getEventType(): string
    {
        return $this->event_type;
    }
    
    /**
     * Get the event version number (for optimistic concurrency control)
     */
    public function getVersion(): int
    {
        return $this->version;
    }
    
    /**
     * Get the timestamp when the event occurred
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->occurred_at);
    }
    
    /**
     * Get the event payload (serializable data)
     */
    public function getPayload(): array
    {
        return $this->payload ?? [];
    }
    
    /**
     * Get causation ID (the event that triggered this event)
     */
    public function getCausationId(): ?string
    {
        return $this->causation_id;
    }
    
    /**
     * Get correlation ID (for distributed tracing)
     */
    public function getCorrelationId(): ?string
    {
        return $this->correlation_id;
    }
    
    /**
     * Get tenant ID for multi-tenancy isolation
     */
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }
    
    /**
     * Get user ID who triggered the event
     */
    public function getUserId(): ?string
    {
        return $this->user_id;
    }
    
    /**
     * Get event metadata (additional context)
     */
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
}
