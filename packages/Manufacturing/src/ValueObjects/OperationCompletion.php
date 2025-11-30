<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * Operation Completion value object.
 *
 * Represents a completion report for a work order operation.
 */
final readonly class OperationCompletion
{
    /**
     * @param int $operationNumber Operation sequence number
     * @param float $quantityCompleted Good quantity produced
     * @param float $scrapQuantity Scrap quantity
     * @param float $setupHours Actual setup hours
     * @param float $runHours Actual run hours
     * @param \DateTimeImmutable $completionDate Date/time of completion
     * @param string|null $workerId Worker/operator ID
     * @param string|null $machineId Machine ID used
     * @param string|null $scrapReasonCode Reason code for scrap
     * @param string|null $notes Additional notes
     */
    public function __construct(
        public int $operationNumber,
        public float $quantityCompleted,
        public float $scrapQuantity = 0.0,
        public float $setupHours = 0.0,
        public float $runHours = 0.0,
        public ?\DateTimeImmutable $completionDate = null,
        public ?string $workerId = null,
        public ?string $machineId = null,
        public ?string $scrapReasonCode = null,
        public ?string $notes = null,
    ) {
        if ($this->quantityCompleted < 0) {
            throw new \InvalidArgumentException('Quantity completed cannot be negative');
        }
        if ($this->scrapQuantity < 0) {
            throw new \InvalidArgumentException('Scrap quantity cannot be negative');
        }
        if ($this->setupHours < 0 || $this->runHours < 0) {
            throw new \InvalidArgumentException('Hours cannot be negative');
        }
    }

    /**
     * Get total quantity handled (good + scrap).
     */
    public function getTotalQuantity(): float
    {
        return $this->quantityCompleted + $this->scrapQuantity;
    }

    /**
     * Get yield percentage.
     */
    public function getYieldPercentage(): float
    {
        $total = $this->getTotalQuantity();
        if ($total <= 0) {
            return 100.0;
        }
        return ($this->quantityCompleted / $total) * 100;
    }

    /**
     * Get total labor hours.
     */
    public function getTotalHours(): float
    {
        return $this->setupHours + $this->runHours;
    }

    /**
     * Check if there was scrap.
     */
    public function hasScrap(): bool
    {
        return $this->scrapQuantity > 0;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operationNumber' => $this->operationNumber,
            'quantityCompleted' => $this->quantityCompleted,
            'scrapQuantity' => $this->scrapQuantity,
            'setupHours' => $this->setupHours,
            'runHours' => $this->runHours,
            'completionDate' => $this->completionDate?->format('Y-m-d H:i:s'),
            'workerId' => $this->workerId,
            'machineId' => $this->machineId,
            'scrapReasonCode' => $this->scrapReasonCode,
            'notes' => $this->notes,
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
            operationNumber: (int) $data['operationNumber'],
            quantityCompleted: (float) $data['quantityCompleted'],
            scrapQuantity: (float) ($data['scrapQuantity'] ?? 0.0),
            setupHours: (float) ($data['setupHours'] ?? 0.0),
            runHours: (float) ($data['runHours'] ?? 0.0),
            completionDate: isset($data['completionDate'])
                ? new \DateTimeImmutable($data['completionDate'])
                : null,
            workerId: $data['workerId'] ?? null,
            machineId: $data['machineId'] ?? null,
            scrapReasonCode: $data['scrapReasonCode'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
