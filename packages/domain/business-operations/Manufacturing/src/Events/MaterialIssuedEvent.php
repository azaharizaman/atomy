<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

/**
 * Event raised when material is issued to a work order.
 */
final readonly class MaterialIssuedEvent
{
    public function __construct(
        public string $workOrderId,
        public string $workOrderNumber,
        public string $componentId,
        public float $quantity,
        public string $uomCode,
        public ?string $lotNumber,
        public ?string $serialNumber,
        public string $warehouseId,
        public ?string $locationId,
        public ?string $issuedBy,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'material.issued',
            'workOrderId' => $this->workOrderId,
            'workOrderNumber' => $this->workOrderNumber,
            'componentId' => $this->componentId,
            'quantity' => $this->quantity,
            'uomCode' => $this->uomCode,
            'lotNumber' => $this->lotNumber,
            'serialNumber' => $this->serialNumber,
            'warehouseId' => $this->warehouseId,
            'locationId' => $this->locationId,
            'issuedBy' => $this->issuedBy,
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }

    /**
     * Check if this is a lot-tracked issue.
     */
    public function isLotTracked(): bool
    {
        return $this->lotNumber !== null;
    }

    /**
     * Check if this is a serial-tracked issue.
     */
    public function isSerialTracked(): bool
    {
        return $this->serialNumber !== null;
    }
}
