<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\ActivityType;
use Nexus\CRM\ValueObjects\ActivityDuration;

/**
 * Activity Persist Interface
 * 
 * Provides write operations for activities.
 * Implements CQRS command separation pattern.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface ActivityPersistInterface
{
    /**
     * Create a new activity
     */
    public function create(
        string $tenantId,
        ActivityType $type,
        string $title,
        ?string $description = null,
        ?ActivityDuration $duration = null,
        ?string $relatedEntityType = null,
        ?string $relatedEntityId = null,
        ?\DateTimeImmutable $scheduledAt = null
    ): ActivityInterface;

    /**
     * Update activity details
     */
    public function update(
        string $id,
        ?string $title = null,
        ?string $description = null,
        ?ActivityDuration $duration = null,
        ?\DateTimeImmutable $scheduledAt = null
    ): ActivityInterface;

    /**
     * Start activity (mark as in progress)
     */
    public function start(string $id, \DateTimeImmutable $startedAt = null): ActivityInterface;

    /**
     * Complete activity
     */
    public function complete(string $id, \DateTimeImmutable $endedAt = null): ActivityInterface;

    /**
     * Reschedule activity
     */
    public function reschedule(string $id, \DateTimeImmutable $newScheduledAt): ActivityInterface;

    /**
     * Cancel activity
     */
    public function cancel(string $id, ?string $reason = null): ActivityInterface;

    /**
     * Delete activity (soft delete)
     */
    public function delete(string $id): void;

    /**
     * Restore deleted activity
     */
    public function restore(string $id): ActivityInterface;
}
