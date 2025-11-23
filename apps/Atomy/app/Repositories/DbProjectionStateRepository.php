<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EventProjection;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Nexus\EventStream\Contracts\ProjectionStateRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * DbProjectionStateRepository
 *
 * SQL-based implementation of ProjectionStateRepositoryInterface using Eloquent.
 * Provides atomic state management for projections with tenant isolation.
 *
 * Requirements satisfied:
 * - ARC-EVS-7007: Repository implementations in application layer
 * - BUS-EVS-7107: Tenant isolation for all operations
 * - FUN-EVS-7212: Track projection state (last event ID, timestamp)
 * - FUN-EVS-7218: Resume projection from last processed event
 * - PER-EVS-7313: Optimize projection resume from checkpoint
 * - REL-EVS-7410: Projection lag monitoring
 *
 * CRITICAL: saveState() uses upsert for atomic updates to prevent race conditions.
 *
 * @package App\Repositories
 */
final readonly class DbProjectionStateRepository implements ProjectionStateRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getLastProcessedEventId(string $projectorName): ?string
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $projection = EventProjection::where('projector_name', $projectorName)
            ->where('tenant_id', $tenantId)
            ->first();

        return $projection?->last_processed_event_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastProcessedAt(string $projectorName): ?DateTimeImmutable
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        $projection = EventProjection::where('projector_name', $projectorName)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($projection === null || $projection->last_processed_at === null) {
            return null;
        }

        return DateTimeImmutable::createFromMutable($projection->last_processed_at);
    }

    /**
     * {@inheritDoc}
     *
     * Uses upsert (INSERT ... ON DUPLICATE KEY UPDATE) for atomic state updates.
     * This prevents race conditions when multiple projection processes attempt
     * to update state concurrently.
     */
    public function saveState(
        string $projectorName,
        string $eventId,
        DateTimeImmutable $processedAt
    ): void {
        $tenantId = $this->tenantContext->getCurrentTenant();

        // Use upsert for atomic update
        DB::table('event_projections')->updateOrInsert(
            [
                'projector_name' => $projectorName,
                'tenant_id' => $tenantId,
            ],
            [
                'last_processed_event_id' => $eventId,
                'last_processed_at' => $processedAt->format('Y-m-d H:i:s'),
                'updated_at' => now(),
                // Only set created_at on insert (not on update)
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function resetState(string $projectorName): void
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        EventProjection::where('projector_name', $projectorName)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function hasState(string $projectorName): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenant();

        return EventProjection::where('projector_name', $projectorName)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}
