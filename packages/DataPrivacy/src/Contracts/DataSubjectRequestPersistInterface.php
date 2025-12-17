<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestStatus;

/**
 * Write operations for data subject requests (CQRS Command Model).
 */
interface DataSubjectRequestPersistInterface
{
    /**
     * Save a new request.
     *
     * @return string The generated request ID
     */
    public function save(DataSubjectRequest $request): string;

    /**
     * Update an existing request.
     */
    public function update(DataSubjectRequest $request): void;

    /**
     * Delete a request.
     */
    public function delete(string $id): void;

    /**
     * Transition request to new status.
     *
     * @return DataSubjectRequest The updated request
     */
    public function transitionStatus(
        string $id,
        RequestStatus $newStatus
    ): DataSubjectRequest;

    /**
     * Assign request to a handler.
     *
     * @return DataSubjectRequest The updated request
     */
    public function assign(string $id, string $assignedTo): DataSubjectRequest;

    /**
     * Mark request as completed.
     *
     * @return DataSubjectRequest The updated request
     */
    public function complete(
        string $id,
        ?\DateTimeImmutable $completedAt = null
    ): DataSubjectRequest;

    /**
     * Mark request as rejected with reason.
     *
     * @return DataSubjectRequest The updated request
     */
    public function reject(string $id, string $reason): DataSubjectRequest;

    /**
     * Extend request deadline.
     *
     * @return DataSubjectRequest The updated request
     */
    public function extendDeadline(
        string $id,
        \DateTimeImmutable $newDeadline,
        string $reason
    ): DataSubjectRequest;

    /**
     * Bulk expire overdue requests.
     *
     * @return int Number of requests expired
     */
    public function bulkExpireOverdue(int $daysOverdue = 30): int;
}
