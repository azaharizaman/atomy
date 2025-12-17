<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

/**
 * Represents a data subject access request (DSAR) or other privacy request.
 *
 * This is an immutable value object representing the state of a request
 * at a specific point in time.
 */
final class DataSubjectRequest
{
    /**
     * @param string $id Unique request identifier
     * @param DataSubjectId $dataSubjectId The data subject making the request
     * @param RequestType $type Type of request
     * @param RequestStatus $status Current status
     * @param DateTimeImmutable $submittedAt When the request was submitted
     * @param DateTimeImmutable $deadline Regulatory deadline for response
     * @param DateTimeImmutable|null $completedAt When request was completed
     * @param string|null $assignedTo User/team assigned to handle request
     * @param string|null $description Additional details from data subject
     * @param string|null $responseNotes Internal notes on response
     * @param string|null $rejectionReason Reason if request was rejected
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly DataSubjectId $dataSubjectId,
        public readonly RequestType $type,
        public readonly RequestStatus $status,
        public readonly DateTimeImmutable $submittedAt,
        public readonly DateTimeImmutable $deadline,
        public readonly ?DateTimeImmutable $completedAt = null,
        public readonly ?string $assignedTo = null,
        public readonly ?string $description = null,
        public readonly ?string $responseNotes = null,
        public readonly ?string $rejectionReason = null,
        public readonly array $metadata = [],
    ) {
        if (trim($id) === '') {
            throw new InvalidRequestException('Request ID cannot be empty');
        }

        if ($this->deadline < $this->submittedAt) {
            throw new InvalidRequestException('Deadline cannot be before submission date');
        }

        if ($this->completedAt !== null && $this->completedAt < $this->submittedAt) {
            throw new InvalidRequestException('Completion date cannot be before submission date');
        }

        if ($this->status === RequestStatus::REJECTED && $this->rejectionReason === null) {
            throw new InvalidRequestException('Rejection reason is required for rejected requests');
        }
    }

    /**
     * Create a new request with minimal required fields.
     *
     * @param int $deadlineDays Number of days for the deadline
     */
    public static function create(
        string $id,
        DataSubjectId $dataSubjectId,
        RequestType $type,
        int $deadlineDays,
        ?string $description = null,
    ): self {
        $submittedAt = new DateTimeImmutable();
        $deadline = $submittedAt->modify("+{$deadlineDays} days");

        return new self(
            id: $id,
            dataSubjectId: $dataSubjectId,
            type: $type,
            status: RequestStatus::PENDING,
            submittedAt: $submittedAt,
            deadline: $deadline,
            description: $description,
        );
    }

    /**
     * Check if the request is overdue.
     */
    public function isOverdue(?DateTimeImmutable $asOf = null): bool
    {
        if ($this->status->isTerminal()) {
            return false;
        }

        $asOf ??= new DateTimeImmutable();

        return $asOf > $this->deadline;
    }

    /**
     * Get days remaining until deadline.
     */
    public function getDaysUntilDeadline(?DateTimeImmutable $asOf = null): int
    {
        $asOf ??= new DateTimeImmutable();

        $diff = $asOf->diff($this->deadline);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Alias for getDaysUntilDeadline() for backward compatibility.
     */
    public function getDaysRemaining(?DateTimeImmutable $asOf = null): int
    {
        return $this->getDaysUntilDeadline($asOf);
    }

    /**
     * Check if the request is completed (successfully or not).
     */
    public function isCompleted(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if the request is actively being processed.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Transition to a new status.
     *
     * @throws InvalidRequestException If transition is not allowed
     */
    public function transitionTo(
        RequestStatus $newStatus,
        ?string $assignedTo = null,
        ?string $responseNotes = null,
        ?string $rejectionReason = null,
    ): self {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvalidRequestException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        $completedAt = $newStatus->isTerminal() ? new DateTimeImmutable() : $this->completedAt;

        return new self(
            id: $this->id,
            dataSubjectId: $this->dataSubjectId,
            type: $this->type,
            status: $newStatus,
            submittedAt: $this->submittedAt,
            deadline: $this->deadline,
            completedAt: $completedAt,
            assignedTo: $assignedTo ?? $this->assignedTo,
            description: $this->description,
            responseNotes: $responseNotes ?? $this->responseNotes,
            rejectionReason: $rejectionReason ?? $this->rejectionReason,
            metadata: $this->metadata,
        );
    }

    /**
     * Get urgency level based on deadline proximity.
     *
     * @return string 'critical', 'high', 'medium', or 'low'
     */
    public function getUrgencyLevel(?DateTimeImmutable $asOf = null): string
    {
        $daysRemaining = $this->getDaysUntilDeadline($asOf);

        return match (true) {
            $daysRemaining < 0 => 'critical', // Overdue
            $daysRemaining <= 3 => 'high',
            $daysRemaining <= 7 => 'medium',
            default => 'low',
        };
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'data_subject_id' => $this->dataSubjectId->value,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'submitted_at' => $this->submittedAt->format(DateTimeImmutable::ATOM),
            'deadline' => $this->deadline->format(DateTimeImmutable::ATOM),
            'completed_at' => $this->completedAt?->format(DateTimeImmutable::ATOM),
            'assigned_to' => $this->assignedTo,
            'description' => $this->description,
            'response_notes' => $this->responseNotes,
            'rejection_reason' => $this->rejectionReason,
            'is_overdue' => $this->isOverdue(),
            'days_until_deadline' => $this->getDaysUntilDeadline(),
            'urgency_level' => $this->getUrgencyLevel(),
            'metadata' => $this->metadata,
        ];
    }
}
