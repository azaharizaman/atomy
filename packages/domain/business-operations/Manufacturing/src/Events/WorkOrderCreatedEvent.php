<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

use Nexus\Manufacturing\Enums\WorkOrderStatus;

/**
 * Event raised when a new work order is created.
 */
final readonly class WorkOrderCreatedEvent
{
    public function __construct(
        public string $workOrderId,
        public string $number,
        public string $productId,
        public float $quantity,
        public string $uomCode,
        public WorkOrderStatus $status,
        public \DateTimeImmutable $plannedStartDate,
        public \DateTimeImmutable $plannedEndDate,
        public ?string $bomId,
        public ?string $routingId,
        public ?string $salesOrderId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'work_order.created',
            'workOrderId' => $this->workOrderId,
            'number' => $this->number,
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'uomCode' => $this->uomCode,
            'status' => $this->status->value,
            'plannedStartDate' => $this->plannedStartDate->format('c'),
            'plannedEndDate' => $this->plannedEndDate->format('c'),
            'bomId' => $this->bomId,
            'routingId' => $this->routingId,
            'salesOrderId' => $this->salesOrderId,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }
}
