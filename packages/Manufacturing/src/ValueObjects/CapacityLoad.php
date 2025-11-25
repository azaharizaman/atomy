<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * Capacity Load value object.
 *
 * Represents a capacity load from an operation or work order.
 */
final readonly class CapacityLoad
{
    /**
     * @param string $sourceId Source ID (work order ID or planned order ID)
     * @param string $sourceType Source type: 'work_order', 'planned_order'
     * @param string $workCenterId Work center ID
     * @param float $hours Hours of capacity consumed
     * @param float $setupHours Setup hours portion
     * @param float $runHours Run hours portion
     * @param \DateTimeImmutable $loadDate Date the load occurs
     * @param int|null $operationNumber Operation number if applicable
     * @param string|null $productId Product being produced
     * @param float $quantity Quantity being produced
     */
    public function __construct(
        public string $sourceId,
        public string $sourceType,
        public string $workCenterId,
        public float $hours,
        public float $setupHours = 0.0,
        public float $runHours = 0.0,
        public ?\DateTimeImmutable $loadDate = null,
        public ?int $operationNumber = null,
        public ?string $productId = null,
        public float $quantity = 0.0,
    ) {
        if ($this->hours < 0) {
            throw new \InvalidArgumentException('Hours cannot be negative');
        }
        if (!in_array($this->sourceType, ['work_order', 'planned_order'], true)) {
            throw new \InvalidArgumentException('Source type must be "work_order" or "planned_order"');
        }
    }

    /**
     * Check if this is from a firm work order.
     */
    public function isFirm(): bool
    {
        return $this->sourceType === 'work_order';
    }

    /**
     * Check if this is from a planned order.
     */
    public function isPlanned(): bool
    {
        return $this->sourceType === 'planned_order';
    }

    /**
     * Get setup hours as percentage of total.
     */
    public function getSetupPercentage(): float
    {
        if ($this->hours <= 0) {
            return 0.0;
        }
        return ($this->setupHours / $this->hours) * 100;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sourceId' => $this->sourceId,
            'sourceType' => $this->sourceType,
            'workCenterId' => $this->workCenterId,
            'hours' => $this->hours,
            'setupHours' => $this->setupHours,
            'runHours' => $this->runHours,
            'loadDate' => $this->loadDate?->format('Y-m-d'),
            'operationNumber' => $this->operationNumber,
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'isFirm' => $this->isFirm(),
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sourceId: $data['sourceId'],
            sourceType: $data['sourceType'],
            workCenterId: $data['workCenterId'],
            hours: (float) $data['hours'],
            setupHours: (float) ($data['setupHours'] ?? 0.0),
            runHours: (float) ($data['runHours'] ?? 0.0),
            loadDate: isset($data['loadDate'])
                ? new \DateTimeImmutable($data['loadDate'])
                : null,
            operationNumber: isset($data['operationNumber'])
                ? (int) $data['operationNumber']
                : null,
            productId: $data['productId'] ?? null,
            quantity: (float) ($data['quantity'] ?? 0.0),
        );
    }
}
