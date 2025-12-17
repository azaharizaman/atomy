<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Services;

use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestQueryInterface;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestPersistInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\NotificationDispatcherInterface;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Exceptions\RequestNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Manages data subject request lifecycle.
 */
final readonly class DataSubjectRequestManager implements DataSubjectRequestManagerInterface
{
    public function __construct(
        private DataSubjectRequestQueryInterface $requestQuery,
        private DataSubjectRequestPersistInterface $requestPersist,
        private ?AuditLoggerInterface $auditLogger = null,
        private ?NotificationDispatcherInterface $notifier = null
    ) {
    }

    public function submitRequest(
        DataSubjectId $dataSubjectId,
        RequestType $type,
        int $deadlineDays = 30
    ): DataSubjectRequest {
        // Check for existing pending request of same type
        if ($this->requestQuery->hasPendingRequest($dataSubjectId->getValue(), $type)) {
            throw new InvalidRequestException(
                "A pending request of type '{$type->value}' already exists for this data subject"
            );
        }

        $request = DataSubjectRequest::create(
            id: $this->generateRequestId(),
            dataSubjectId: $dataSubjectId,
            type: $type,
            deadlineDays: $deadlineDays
        );

        $requestId = $this->requestPersist->save($request);

        $this->auditLogger?->logRequestSubmitted(
            $requestId,
            $dataSubjectId->getValue(),
            $type->value
        );

        $this->notifier?->notifyTeamNewRequest(
            $requestId,
            $type->value,
            $request->deadline
        );

        $this->notifier?->notifyRequestStatusChange(
            $dataSubjectId->getValue(),
            $requestId,
            $type->value,
            RequestStatus::PENDING->value,
            'Your request has been submitted and is being processed.'
        );

        return $request;
    }

    public function getRequest(string $requestId): DataSubjectRequest
    {
        $request = $this->requestQuery->findById($requestId);

        if ($request === null) {
            throw RequestNotFoundException::withId($requestId);
        }

        return $request;
    }

    public function getRequestsByDataSubject(DataSubjectId $dataSubjectId): array
    {
        return $this->requestQuery->findByDataSubject($dataSubjectId->getValue());
    }

    public function transitionStatus(
        string $requestId,
        RequestStatus $newStatus
    ): DataSubjectRequest {
        $request = $this->getRequest($requestId);

        if (!$request->status->canTransitionTo($newStatus)) {
            throw new InvalidRequestException(
                "Cannot transition from '{$request->status->value}' to '{$newStatus->value}'"
            );
        }

        $updatedRequest = $request->transitionTo($newStatus);
        $this->requestPersist->update($updatedRequest);

        $this->auditLogger?->log(
            'request',
            $requestId,
            'status_changed',
            "Status changed to: {$newStatus->value}",
            [
                'old_status' => $request->status->value,
                'new_status' => $newStatus->value,
            ]
        );

        $this->notifier?->notifyRequestStatusChange(
            $request->dataSubjectId->getValue(),
            $requestId,
            $request->type->value,
            $newStatus->value,
            null
        );

        return $updatedRequest;
    }

    public function assignRequest(
        string $requestId,
        string $assignedTo
    ): DataSubjectRequest {
        $request = $this->getRequest($requestId);

        $updatedRequest = $request->assignTo($assignedTo);
        $this->requestPersist->update($updatedRequest);

        $this->auditLogger?->log(
            'request',
            $requestId,
            'assigned',
            "Request assigned to: {$assignedTo}",
            ['assigned_to' => $assignedTo]
        );

        return $updatedRequest;
    }

    public function completeRequest(string $requestId): DataSubjectRequest
    {
        $request = $this->getRequest($requestId);

        $completedRequest = $request->transitionTo(RequestStatus::COMPLETED);
        $this->requestPersist->update($completedRequest);

        $this->auditLogger?->logRequestCompleted(
            $requestId,
            $request->dataSubjectId->getValue(),
            $request->type->value
        );

        $this->notifier?->notifyRequestStatusChange(
            $request->dataSubjectId->getValue(),
            $requestId,
            $request->type->value,
            RequestStatus::COMPLETED->value,
            'Your request has been completed.'
        );

        return $completedRequest;
    }

    public function rejectRequest(
        string $requestId,
        string $reason
    ): DataSubjectRequest {
        $request = $this->getRequest($requestId);

        $rejectedRequest = $request->transitionTo(RequestStatus::REJECTED);
        $this->requestPersist->update($rejectedRequest);

        $this->auditLogger?->log(
            'request',
            $requestId,
            'rejected',
            "Request rejected: {$reason}",
            ['reason' => $reason]
        );

        $this->notifier?->notifyRequestStatusChange(
            $request->dataSubjectId->getValue(),
            $requestId,
            $request->type->value,
            RequestStatus::REJECTED->value,
            "Your request has been rejected. Reason: {$reason}"
        );

        return $rejectedRequest;
    }

    public function cancelRequest(
        string $requestId,
        string $reason
    ): DataSubjectRequest {
        $request = $this->getRequest($requestId);

        $cancelledRequest = $request->transitionTo(RequestStatus::CANCELLED);
        $this->requestPersist->update($cancelledRequest);

        $this->auditLogger?->log(
            'request',
            $requestId,
            'cancelled',
            "Request cancelled: {$reason}",
            ['reason' => $reason]
        );

        return $cancelledRequest;
    }

    public function extendDeadline(
        string $requestId,
        \DateTimeImmutable $newDeadline,
        string $reason
    ): DataSubjectRequest {
        $request = $this->getRequest($requestId);

        $extendedRequest = $this->requestPersist->extendDeadline(
            $requestId,
            $newDeadline,
            $reason
        );

        $this->auditLogger?->log(
            'request',
            $requestId,
            'deadline_extended',
            "Deadline extended: {$reason}",
            [
                'old_deadline' => $request->deadline->format('Y-m-d'),
                'new_deadline' => $newDeadline->format('Y-m-d'),
                'reason' => $reason,
            ]
        );

        $this->notifier?->notifyRequestStatusChange(
            $request->dataSubjectId->getValue(),
            $requestId,
            $request->type->value,
            $request->status->value,
            "The deadline for your request has been extended to {$newDeadline->format('Y-m-d')}. Reason: {$reason}"
        );

        return $extendedRequest;
    }

    public function getActiveRequests(): array
    {
        return $this->requestQuery->findActive();
    }

    public function getOverdueRequests(): array
    {
        return $this->requestQuery->findOverdue();
    }

    public function getRequestsApproachingDeadline(int $withinDays = 5): array
    {
        return $this->requestQuery->findDeadlineWithinDays($withinDays);
    }

    public function hasPendingRequest(
        DataSubjectId $dataSubjectId,
        RequestType $type
    ): bool {
        return $this->requestQuery->hasPendingRequest(
            $dataSubjectId->getValue(),
            $type
        );
    }

    public function getRequestMetrics(): array
    {
        $byStatus = $this->requestQuery->countByStatus();
        $byType = $this->requestQuery->countByType();
        $avgCompletion = $this->requestQuery->getAverageCompletionTimeByType();

        $total = array_sum($byStatus);
        $overdue = count($this->requestQuery->findOverdue());

        $avgDays = 0.0;
        if (!empty($avgCompletion)) {
            $avgDays = array_sum($avgCompletion) / count($avgCompletion);
        }

        return [
            'total' => $total,
            'by_type' => $byType,
            'by_status' => $byStatus,
            'overdue' => $overdue,
            'average_completion_days' => round($avgDays, 2),
        ];
    }

    /**
     * Generate unique request ID.
     */
    private function generateRequestId(): string
    {
        return 'REQ-' . bin2hex(random_bytes(12));
    }
}
