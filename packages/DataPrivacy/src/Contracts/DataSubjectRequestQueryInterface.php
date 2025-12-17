<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;

/**
 * Read operations for data subject requests (CQRS Query Model).
 */
interface DataSubjectRequestQueryInterface
{
    /**
     * Find request by ID.
     */
    public function findById(string $id): ?DataSubjectRequest;

    /**
     * Find all requests for a data subject.
     *
     * @return array<DataSubjectRequest>
     */
    public function findByDataSubject(string $dataSubjectId): array;

    /**
     * Find requests by type.
     *
     * @return array<DataSubjectRequest>
     */
    public function findByType(RequestType $type): array;

    /**
     * Find requests by status.
     *
     * @return array<DataSubjectRequest>
     */
    public function findByStatus(RequestStatus $status): array;

    /**
     * Find active (non-terminal) requests.
     *
     * @return array<DataSubjectRequest>
     */
    public function findActive(): array;

    /**
     * Find overdue requests (past deadline, not completed).
     *
     * @return array<DataSubjectRequest>
     */
    public function findOverdue(): array;

    /**
     * Find requests with deadline within given days.
     *
     * @return array<DataSubjectRequest>
     */
    public function findDeadlineWithinDays(int $days): array;

    /**
     * Find requests assigned to a handler.
     *
     * @return array<DataSubjectRequest>
     */
    public function findByAssignee(string $assignedTo): array;

    /**
     * Find requests submitted within date range.
     *
     * @return array<DataSubjectRequest>
     */
    public function findByDateRange(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array;

    /**
     * Check if data subject has pending request of given type.
     */
    public function hasPendingRequest(
        string $dataSubjectId,
        RequestType $type
    ): bool;

    /**
     * Count requests by status.
     *
     * @return array<string, int> Status => count mapping
     */
    public function countByStatus(): array;

    /**
     * Count requests by type.
     *
     * @return array<string, int> Type => count mapping
     */
    public function countByType(): array;

    /**
     * Get average completion time in days by request type.
     *
     * @return array<string, float> Type => average days mapping
     */
    public function getAverageCompletionTimeByType(): array;
}
