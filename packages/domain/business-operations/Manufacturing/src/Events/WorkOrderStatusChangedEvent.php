<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Enums\WorkOrderStatus;

/**
 * Event raised when a work order status changes.
 */
final readonly class WorkOrderStatusChangedEvent
{
    public function __construct(
        public string $workOrderId,
        public string $number,
        public WorkOrderStatus $previousStatus,
        public WorkOrderStatus $newStatus,
        public ?string $reason,
        public ?string $changedBy,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'work_order.status_changed',
            'workOrderId' => $this->workOrderId,
            'number' => $this->number,
            'previousStatus' => $this->previousStatus->value,
            'newStatus' => $this->newStatus->value,
            'reason' => $this->reason,
            'changedBy' => $this->changedBy,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Check if this was a release transition.
     */
    public function isReleased(): bool
    {
        return $this->newStatus === WorkOrderStatus::RELEASED;
    }

    /**
     * Check if this was a start transition.
     */
    public function isStarted(): bool
    {
        return $this->newStatus === WorkOrderStatus::IN_PROGRESS
            && $this->previousStatus === WorkOrderStatus::RELEASED;
    }

    /**
     * Check if this was a completion transition.
     */
    public function isCompleted(): bool
    {
        return $this->newStatus === WorkOrderStatus::COMPLETED;
    }

    /**
     * Check if this was a cancellation.
     */
    public function isCancelled(): bool
    {
        return $this->newStatus === WorkOrderStatus::CANCELLED;
    }
}
