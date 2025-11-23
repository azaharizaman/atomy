<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EventStream;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Exceptions\ConcurrencyException;
use Nexus\EventStream\Exceptions\DuplicateEventException;
use Nexus\EventStream\Exceptions\EventStreamException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;

/**
 * DbEventStoreRepository
 *
 * SQL-based implementation of EventStoreInterface using Eloquent.
 * Provides ACID compliance for event appending with audit logging and error handling.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - ARC-EVS-7008: Support multiple event store backends via adapter pattern
 * - BUS-EVS-7105: Event streams MUST support optimistic concurrency control
 * - BUS-EVS-7107: Tenant isolation for all event operations
 * - REL-EVS-7401: Event appending uses database transactions (ACID compliance)
 * - REL-EVS-7405: Support idempotent event appending (duplicate detection via EventId)
 * - FUN-EVS-7201: EventStoreInterface implementation
 * - FUN-EVS-7205: Optimistic concurrency control with version checking
 *
 * @package App\Repositories
 */
final readonly class DbEventStoreRepository implements EventStoreInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function append(
        string $aggregateId,
        EventInterface $event,
        ?int $expectedVersion = null
    ): void {
        $tenantId = $this->tenantContext->getCurrentTenant();

        try {
            DB::transaction(function () use ($aggregateId, $event, $expectedVersion, $tenantId) {
                // Check optimistic concurrency if expected version provided
                if ($expectedVersion !== null) {
                    $currentVersion = $this->getCurrentVersion($aggregateId);
                    
                    if ($currentVersion !== $expectedVersion) {
                        throw new ConcurrencyException(
                            $aggregateId,
                            $expectedVersion,
                            $currentVersion
                        );
                    }
                }

                // Calculate next version
                $nextVersion = $this->getCurrentVersion($aggregateId) + 1;

                try {
                    EventStream::create([
                        'event_id' => $event->getEventId(),
                        'aggregate_id' => $aggregateId,
                        'aggregate_type' => $this->extractAggregateType($aggregateId),
                        'version' => $nextVersion,
                        'event_type' => $event->getEventType(),
                        'payload' => $event->getPayload(),
                        'metadata' => $event->getMetadata(),
                        'causation_id' => $event->getCausationId(),
                        'correlation_id' => $event->getCorrelationId(),
                        'tenant_id' => $tenantId,
                        'user_id' => $event->getUserId(),
                        'occurred_at' => $event->getOccurredAt(),
                    ]);
                } catch (QueryException $e) {
                    // Check for duplicate event_id (primary key violation)
                    if ($this->isDuplicateKeyError($e)) {
                        throw new DuplicateEventException(
                            "Event with ID '{$event->getEventId()}' already exists",
                            previous: $e
                        );
                    }

                    throw $e;
                }

                // Log to audit trail
                $this->auditLogger->log(
                    $aggregateId,
                    'event_appended',
                    "Event '{$event->getEventType()}' appended to stream (version {$nextVersion})"
                );
            });
        } catch (ConcurrencyException | DuplicateEventException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to append event', [
                'aggregate_id' => $aggregateId,
                'event_type' => $event->getEventType(),
                'error' => $e->getMessage(),
            ]);

            throw new EventStreamException(
                'Failed to append event: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function appendBatch(
        string $aggregateId,
        array $events,
        ?int $expectedVersion = null
    ): void {
        $tenantId = $this->tenantContext->getCurrentTenant();

        try {
            DB::transaction(function () use ($aggregateId, $events, $expectedVersion, $tenantId) {
                // Check optimistic concurrency if expected version provided
                if ($expectedVersion !== null) {
                    $currentVersion = $this->getCurrentVersion($aggregateId);
                    
                    if ($currentVersion !== $expectedVersion) {
                        throw new ConcurrencyException(
                            $aggregateId,
                            $expectedVersion,
                            $currentVersion
                        );
                    }
                }

                $currentVersion = $this->getCurrentVersion($aggregateId);

                foreach ($events as $index => $event) {
                    $nextVersion = $currentVersion + $index + 1;

                    try {
                        EventStream::create([
                            'event_id' => $event->getEventId(),
                            'aggregate_id' => $aggregateId,
                            'aggregate_type' => $this->extractAggregateType($aggregateId),
                            'version' => $nextVersion,
                            'event_type' => $event->getEventType(),
                            'payload' => $event->getPayload(),
                            'metadata' => $event->getMetadata(),
                            'causation_id' => $event->getCausationId(),
                            'correlation_id' => $event->getCorrelationId(),
                            'tenant_id' => $tenantId,
                            'user_id' => $event->getUserId(),
                            'occurred_at' => $event->getOccurredAt(),
                        ]);
                    } catch (QueryException $e) {
                        // Check for duplicate event_id (primary key violation)
                        if ($this->isDuplicateKeyError($e)) {
                            throw new DuplicateEventException(
                                "Event with ID '{$event->getEventId()}' already exists",
                                previous: $e
                            );
                        }

                        throw $e;
                    }
                }

                // Log batch append to audit trail
                $this->auditLogger->log(
                    $aggregateId,
                    'events_batch_appended',
                    count($events) . ' events appended to stream in batch'
                );
            });
        } catch (ConcurrencyException | DuplicateEventException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to append batch events', [
                'aggregate_id' => $aggregateId,
                'event_count' => count($events),
                'error' => $e->getMessage(),
            ]);

            throw new EventStreamException(
                'Failed to append batch events: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentVersion(string $aggregateId): int
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $maxVersion = EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->max('version');

        return $maxVersion ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function streamExists(string $aggregateId): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function query(
        array $filters,
        array $inFilters,
        string $orderByField,
        string $orderDirection,
        int $limit,
        ?array $cursorData = null
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $query = EventStream::where('tenant_id', $tenantId);

        // Apply standard filters
        foreach ($filters as $field => $condition) {
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? $condition;
            $query->where($field, $operator, $value);
        }

        // Apply IN filters
        foreach ($inFilters as $field => $values) {
            $query->whereIn($field, $values);
        }

        // Apply cursor pagination if provided
        if ($cursorData !== null) {
            $query->where('event_id', '>', $cursorData['event_id']);
        }

        // Apply ordering
        $query->orderBy($orderByField, $orderDirection);

        // Apply limit
        $query->limit($limit);

        return $query->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $filters, array $inFilters): int
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $query = EventStream::where('tenant_id', $tenantId);

        // Apply standard filters
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        // Apply IN filters
        foreach ($inFilters as $field => $values) {
            $query->whereIn($field, $values);
        }

        return $query->count();
    }

    /**
     * Extract aggregate type from aggregate ID
     *
     * @param string $aggregateId
     * @return string
     */
    private function extractAggregateType(string $aggregateId): string
    {
        // Example: "account-1000" -> "account"
        $parts = explode('-', $aggregateId, 2);
        return $parts[0] ?? 'unknown';
    }

    /**
     * Check if a query exception is a duplicate key error
     *
     * @param QueryException $e
     * @return bool
     */
    private function isDuplicateKeyError(QueryException $e): bool
    {
        // Check for MySQL duplicate entry error (1062)
        // Check for PostgreSQL unique violation (23505)
        return $e->getCode() === '23000' 
            || $e->getCode() === '23505'
            || str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'unique constraint');
    }
}
