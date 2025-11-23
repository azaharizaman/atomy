<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EventStream;
use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * DbStreamReaderRepository
 *
 * SQL-based implementation of StreamReaderInterface using Eloquent.
 * Provides efficient querying of event streams with tenant isolation.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - BUS-EVS-7107: Tenant isolation for all event operations
 * - FUN-EVS-7202: StreamReaderInterface for reading event streams with filtering
 * - FUN-EVS-7206: Read event stream by aggregate ID with version range filtering
 * - FUN-EVS-7207: Read event stream by event type
 * - FUN-EVS-7208: Replay event stream to rebuild aggregate state at specific point in time
 *
 * @package App\Repositories
 */
final readonly class DbStreamReaderRepository implements StreamReaderInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * {@inheritDoc}
     */
    public function readStream(string $aggregateId): array
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->orderBy('version')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function readStreamFromVersion(
        string $aggregateId,
        int $fromVersion,
        ?int $toVersion = null
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $query = EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->where('version', '>=', $fromVersion);

        if ($toVersion !== null) {
            $query->where('version', '<=', $toVersion);
        }

        return $query->orderBy('version')->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function readStreamUntil(
        string $aggregateId,
        DateTimeImmutable $timestamp
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->where('occurred_at', '<=', $timestamp->format('Y-m-d H:i:s'))
            ->orderBy('version')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function readStreamFromDate(
        string $aggregateId,
        DateTimeImmutable $fromDate
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->where('occurred_at', '>=', $fromDate->format('Y-m-d H:i:s'))
            ->orderBy('version')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function readStreamUpToDate(
        string $aggregateId,
        DateTimeImmutable $upToDate
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventStream::where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->where('occurred_at', '<=', $upToDate->format('Y-m-d H:i:s'))
            ->orderBy('version')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function readEventsByType(string $eventType, ?int $limit = null): array
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $query = EventStream::where('event_type', $eventType)
            ->where('tenant_id', $tenantId)
            ->orderBy('occurred_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function readEventsByTypeAndDateRange(
        string $eventType,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventStream::where('event_type', $eventType)
            ->where('tenant_id', $tenantId)
            ->whereBetween('occurred_at', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ])
            ->orderBy('occurred_at')
            ->get()
            ->all();
    }
}
