<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\ProcessingActivity;

/**
 * Write operations for processing activities (CQRS Command Model).
 */
interface ProcessingActivityPersistInterface
{
    /**
     * Save a new processing activity.
     *
     * @return string The generated activity ID
     */
    public function save(ProcessingActivity $activity): string;

    /**
     * Update an existing processing activity.
     */
    public function update(ProcessingActivity $activity): void;

    /**
     * Delete a processing activity.
     */
    public function delete(string $id): void;

    /**
     * Mark activity as reviewed.
     *
     * @return ProcessingActivity The updated activity
     */
    public function markReviewed(
        string $id,
        \DateTimeImmutable $reviewedAt,
        string $reviewedBy,
        ?string $notes = null
    ): ProcessingActivity;

    /**
     * Deactivate a processing activity.
     *
     * @return ProcessingActivity The updated activity
     */
    public function deactivate(
        string $id,
        string $reason
    ): ProcessingActivity;

    /**
     * Reactivate a processing activity.
     *
     * @return ProcessingActivity The updated activity
     */
    public function reactivate(string $id): ProcessingActivity;

    /**
     * Update DPIA status for activity.
     */
    public function updateDpiaStatus(
        string $id,
        bool $dpiaCompleted,
        ?\DateTimeImmutable $dpiaDate = null,
        ?string $dpiaReference = null
    ): void;
}
