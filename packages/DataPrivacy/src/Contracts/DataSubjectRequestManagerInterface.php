<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;

/**
 * Manager interface for data subject request operations.
 */
interface DataSubjectRequestManagerInterface
{
    /**
     * Submit a new data subject request.
     *
     * @param int $deadlineDays Days until deadline (regulation-specific)
     */
    public function submitRequest(
        DataSubjectId $dataSubjectId,
        RequestType $type,
        int $deadlineDays = 30
    ): DataSubjectRequest;

    /**
     * Get request by ID.
     */
    public function getRequest(string $requestId): DataSubjectRequest;

    /**
     * Get all requests for a data subject.
     *
     * @return array<DataSubjectRequest>
     */
    public function getRequestsByDataSubject(DataSubjectId $dataSubjectId): array;

    /**
     * Transition request status.
     */
    public function transitionStatus(
        string $requestId,
        RequestStatus $newStatus
    ): DataSubjectRequest;

    /**
     * Assign request to a handler.
     */
    public function assignRequest(
        string $requestId,
        string $assignedTo
    ): DataSubjectRequest;

    /**
     * Complete a request.
     */
    public function completeRequest(string $requestId): DataSubjectRequest;

    /**
     * Reject a request with reason.
     */
    public function rejectRequest(
        string $requestId,
        string $reason
    ): DataSubjectRequest;

    /**
     * Cancel a request.
     */
    public function cancelRequest(
        string $requestId,
        string $reason
    ): DataSubjectRequest;

    /**
     * Extend request deadline.
     */
    public function extendDeadline(
        string $requestId,
        \DateTimeImmutable $newDeadline,
        string $reason
    ): DataSubjectRequest;

    /**
     * Get active (non-terminal) requests.
     *
     * @return array<DataSubjectRequest>
     */
    public function getActiveRequests(): array;

    /**
     * Get overdue requests.
     *
     * @return array<DataSubjectRequest>
     */
    public function getOverdueRequests(): array;

    /**
     * Get requests approaching deadline.
     *
     * @param int $withinDays Requests due within this many days
     * @return array<DataSubjectRequest>
     */
    public function getRequestsApproachingDeadline(int $withinDays = 5): array;

    /**
     * Check if data subject has pending request of given type.
     */
    public function hasPendingRequest(
        DataSubjectId $dataSubjectId,
        RequestType $type
    ): bool;

    /**
     * Get request metrics for reporting.
     *
     * @return array{
     *     total: int,
     *     by_type: array<string, int>,
     *     by_status: array<string, int>,
     *     overdue: int,
     *     average_completion_days: float
     * }
     */
    public function getRequestMetrics(): array;
}
