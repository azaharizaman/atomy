<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

use Nexus\Manufacturing\Enums\OperationType;

/**
 * Operation value object.
 *
 * Represents a single operation in a manufacturing routing.
 */
final readonly class Operation
{
    /**
     * @param int $operationNumber Operation sequence number
     * @param string $workCenterId Work center where operation is performed
     * @param string $description Operation description
     * @param OperationType $type Operation type
     * @param float $setupTimeMinutes Setup time in minutes
     * @param float $runTimeMinutes Run time per unit in minutes
     * @param float $queueTimeMinutes Queue/wait time in minutes
     * @param float $moveTimeMinutes Move time in minutes
     * @param int $resourceCount Number of resources (machines/people) required
     * @param float $overlapPercentage Overlap allowed with next operation (0-100)
     * @param \DateTimeImmutable|null $effectiveFrom Effectivity start date
     * @param \DateTimeImmutable|null $effectiveTo Effectivity end date
     * @param string|null $subcontractorId Subcontractor ID for subcontracted operations
     * @param float|null $subcontractCost Cost per unit for subcontracted operations
     * @param string|null $notes Additional notes
     */
    public function __construct(
        public int $operationNumber,
        public string $workCenterId,
        public string $description,
        public OperationType $type = OperationType::PRODUCTION,
        public float $setupTimeMinutes = 0.0,
        public float $runTimeMinutes = 0.0,
        public float $queueTimeMinutes = 0.0,
        public float $moveTimeMinutes = 0.0,
        public int $resourceCount = 1,
        public float $overlapPercentage = 0.0,
        public ?\DateTimeImmutable $effectiveFrom = null,
        public ?\DateTimeImmutable $effectiveTo = null,
        public ?string $subcontractorId = null,
        public ?float $subcontractCost = null,
        public ?string $notes = null,
    ) {
        if ($this->operationNumber < 1) {
            throw new \InvalidArgumentException('Operation number must be positive');
        }
        if ($this->setupTimeMinutes < 0 || $this->runTimeMinutes < 0 ||
            $this->queueTimeMinutes < 0 || $this->moveTimeMinutes < 0) {
            throw new \InvalidArgumentException('Time values cannot be negative');
        }
        if ($this->resourceCount < 1) {
            throw new \InvalidArgumentException('Resource count must be at least 1');
        }
        if ($this->overlapPercentage < 0 || $this->overlapPercentage > 100) {
            throw new \InvalidArgumentException('Overlap percentage must be between 0 and 100');
        }
        if ($this->type === OperationType::SUBCONTRACT && $this->subcontractorId === null) {
            throw new \InvalidArgumentException('Subcontracted operations require a subcontractor ID');
        }
    }

    /**
     * Calculate total operation time for a quantity.
     *
     * @return float Total time in minutes
     */
    public function calculateTotalTime(float $quantity): float
    {
        return $this->setupTimeMinutes
            + ($this->runTimeMinutes * $quantity)
            + $this->queueTimeMinutes
            + $this->moveTimeMinutes;
    }

    /**
     * Calculate total operation time in hours.
     */
    public function calculateTotalTimeHours(float $quantity): float
    {
        return $this->calculateTotalTime($quantity) / 60;
    }

    /**
     * Get capacity-consuming time in hours.
     */
    public function getCapacityTimeHours(float $quantity): float
    {
        if (!$this->type->consumesCapacity()) {
            return 0.0;
        }

        return ($this->setupTimeMinutes + ($this->runTimeMinutes * $quantity)) / 60;
    }

    /**
     * Check if operation is effective at a given date.
     */
    public function isEffectiveAt(?\DateTimeImmutable $date = null): bool
    {
        $date ??= new \DateTimeImmutable();

        if ($this->effectiveFrom !== null && $date < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $date > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a subcontracted operation.
     */
    public function isSubcontracted(): bool
    {
        return $this->type === OperationType::SUBCONTRACT;
    }

    /**
     * Create a copy with updated times.
     */
    public function withTimes(
        float $setupTimeMinutes,
        float $runTimeMinutes,
        ?float $queueTimeMinutes = null,
        ?float $moveTimeMinutes = null
    ): self {
        return new self(
            operationNumber: $this->operationNumber,
            workCenterId: $this->workCenterId,
            description: $this->description,
            type: $this->type,
            setupTimeMinutes: $setupTimeMinutes,
            runTimeMinutes: $runTimeMinutes,
            queueTimeMinutes: $queueTimeMinutes ?? $this->queueTimeMinutes,
            moveTimeMinutes: $moveTimeMinutes ?? $this->moveTimeMinutes,
            resourceCount: $this->resourceCount,
            overlapPercentage: $this->overlapPercentage,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $this->effectiveTo,
            subcontractorId: $this->subcontractorId,
            subcontractCost: $this->subcontractCost,
            notes: $this->notes,
        );
    }

    /**
     * Create a copy with different work center.
     */
    public function withWorkCenter(string $workCenterId): self
    {
        return new self(
            operationNumber: $this->operationNumber,
            workCenterId: $workCenterId,
            description: $this->description,
            type: $this->type,
            setupTimeMinutes: $this->setupTimeMinutes,
            runTimeMinutes: $this->runTimeMinutes,
            queueTimeMinutes: $this->queueTimeMinutes,
            moveTimeMinutes: $this->moveTimeMinutes,
            resourceCount: $this->resourceCount,
            overlapPercentage: $this->overlapPercentage,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $this->effectiveTo,
            subcontractorId: $this->subcontractorId,
            subcontractCost: $this->subcontractCost,
            notes: $this->notes,
        );
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
            'workCenterId' => $this->workCenterId,
            'description' => $this->description,
            'type' => $this->type->value,
            'setupTimeMinutes' => $this->setupTimeMinutes,
            'runTimeMinutes' => $this->runTimeMinutes,
            'queueTimeMinutes' => $this->queueTimeMinutes,
            'moveTimeMinutes' => $this->moveTimeMinutes,
            'resourceCount' => $this->resourceCount,
            'overlapPercentage' => $this->overlapPercentage,
            'effectiveFrom' => $this->effectiveFrom?->format('Y-m-d'),
            'effectiveTo' => $this->effectiveTo?->format('Y-m-d'),
            'subcontractorId' => $this->subcontractorId,
            'subcontractCost' => $this->subcontractCost,
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
            workCenterId: $data['workCenterId'],
            description: $data['description'],
            type: OperationType::from($data['type'] ?? 'production'),
            setupTimeMinutes: (float) ($data['setupTimeMinutes'] ?? 0.0),
            runTimeMinutes: (float) ($data['runTimeMinutes'] ?? 0.0),
            queueTimeMinutes: (float) ($data['queueTimeMinutes'] ?? 0.0),
            moveTimeMinutes: (float) ($data['moveTimeMinutes'] ?? 0.0),
            resourceCount: (int) ($data['resourceCount'] ?? 1),
            overlapPercentage: (float) ($data['overlapPercentage'] ?? 0.0),
            effectiveFrom: isset($data['effectiveFrom'])
                ? new \DateTimeImmutable($data['effectiveFrom'])
                : null,
            effectiveTo: isset($data['effectiveTo'])
                ? new \DateTimeImmutable($data['effectiveTo'])
                : null,
            subcontractorId: $data['subcontractorId'] ?? null,
            subcontractCost: isset($data['subcontractCost'])
                ? (float) $data['subcontractCost']
                : null,
            notes: $data['notes'] ?? null,
        );
    }
}
