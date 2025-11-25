<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * Work Order Line value object.
 *
 * Represents a material line or operation line in a work order.
 */
final readonly class WorkOrderLine
{
    /**
     * @param int $lineNumber Line sequence number
     * @param string $lineType Type of line: 'material' or 'operation'
     * @param string|null $productId Product ID for material lines
     * @param float $plannedQuantity Planned quantity
     * @param float $issuedQuantity Quantity already issued/completed
     * @param string|null $uomCode Unit of measure code
     * @param int|null $operationNumber Operation number for operation lines
     * @param string|null $workCenterId Work center for operation lines
     * @param float $plannedSetupHours Planned setup hours
     * @param float $plannedRunHours Planned run hours
     * @param float $actualSetupHours Actual setup hours
     * @param float $actualRunHours Actual run hours
     * @param float $scrapQuantity Scrap quantity
     * @param string|null $warehouseId Warehouse for material lines
     * @param string|null $lotNumber Lot number for material tracking
     * @param string|null $status Line status
     * @param string|null $notes Additional notes
     */
    public function __construct(
        public int $lineNumber,
        public string $lineType,
        public ?string $productId = null,
        public float $plannedQuantity = 0.0,
        public float $issuedQuantity = 0.0,
        public ?string $uomCode = null,
        public ?int $operationNumber = null,
        public ?string $workCenterId = null,
        public float $plannedSetupHours = 0.0,
        public float $plannedRunHours = 0.0,
        public float $actualSetupHours = 0.0,
        public float $actualRunHours = 0.0,
        public float $scrapQuantity = 0.0,
        public ?string $warehouseId = null,
        public ?string $lotNumber = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {
        if (!in_array($this->lineType, ['material', 'operation'], true)) {
            throw new \InvalidArgumentException('Line type must be "material" or "operation"');
        }
        if ($this->lineType === 'material' && $this->productId === null) {
            throw new \InvalidArgumentException('Material lines require a product ID');
        }
        if ($this->lineType === 'operation' && $this->operationNumber === null) {
            throw new \InvalidArgumentException('Operation lines require an operation number');
        }
    }

    /**
     * Check if this is a material line.
     */
    public function isMaterial(): bool
    {
        return $this->lineType === 'material';
    }

    /**
     * Check if this is an operation line.
     */
    public function isOperation(): bool
    {
        return $this->lineType === 'operation';
    }

    /**
     * Get remaining quantity to issue/complete.
     */
    public function getRemainingQuantity(): float
    {
        return max(0, $this->plannedQuantity - $this->issuedQuantity);
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        if ($this->plannedQuantity <= 0) {
            return 100.0;
        }
        return min(100, ($this->issuedQuantity / $this->plannedQuantity) * 100);
    }

    /**
     * Check if line is complete.
     */
    public function isComplete(): bool
    {
        return $this->issuedQuantity >= $this->plannedQuantity;
    }

    /**
     * Get labor efficiency percentage for operation lines.
     */
    public function getLaborEfficiency(): ?float
    {
        if (!$this->isOperation()) {
            return null;
        }

        $plannedHours = $this->plannedSetupHours + $this->plannedRunHours;
        $actualHours = $this->actualSetupHours + $this->actualRunHours;

        if ($actualHours <= 0) {
            return null;
        }

        return ($plannedHours / $actualHours) * 100;
    }

    /**
     * Create a copy with updated issued quantity.
     */
    public function withIssuedQuantity(float $quantity): self
    {
        return new self(
            lineNumber: $this->lineNumber,
            lineType: $this->lineType,
            productId: $this->productId,
            plannedQuantity: $this->plannedQuantity,
            issuedQuantity: $quantity,
            uomCode: $this->uomCode,
            operationNumber: $this->operationNumber,
            workCenterId: $this->workCenterId,
            plannedSetupHours: $this->plannedSetupHours,
            plannedRunHours: $this->plannedRunHours,
            actualSetupHours: $this->actualSetupHours,
            actualRunHours: $this->actualRunHours,
            scrapQuantity: $this->scrapQuantity,
            warehouseId: $this->warehouseId,
            lotNumber: $this->lotNumber,
            status: $this->status,
            notes: $this->notes,
        );
    }

    /**
     * Create a copy with updated actual hours.
     */
    public function withActualHours(float $setupHours, float $runHours): self
    {
        return new self(
            lineNumber: $this->lineNumber,
            lineType: $this->lineType,
            productId: $this->productId,
            plannedQuantity: $this->plannedQuantity,
            issuedQuantity: $this->issuedQuantity,
            uomCode: $this->uomCode,
            operationNumber: $this->operationNumber,
            workCenterId: $this->workCenterId,
            plannedSetupHours: $this->plannedSetupHours,
            plannedRunHours: $this->plannedRunHours,
            actualSetupHours: $setupHours,
            actualRunHours: $runHours,
            scrapQuantity: $this->scrapQuantity,
            warehouseId: $this->warehouseId,
            lotNumber: $this->lotNumber,
            status: $this->status,
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
            'lineNumber' => $this->lineNumber,
            'lineType' => $this->lineType,
            'productId' => $this->productId,
            'plannedQuantity' => $this->plannedQuantity,
            'issuedQuantity' => $this->issuedQuantity,
            'uomCode' => $this->uomCode,
            'operationNumber' => $this->operationNumber,
            'workCenterId' => $this->workCenterId,
            'plannedSetupHours' => $this->plannedSetupHours,
            'plannedRunHours' => $this->plannedRunHours,
            'actualSetupHours' => $this->actualSetupHours,
            'actualRunHours' => $this->actualRunHours,
            'scrapQuantity' => $this->scrapQuantity,
            'warehouseId' => $this->warehouseId,
            'lotNumber' => $this->lotNumber,
            'status' => $this->status,
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
            lineNumber: (int) $data['lineNumber'],
            lineType: $data['lineType'],
            productId: $data['productId'] ?? null,
            plannedQuantity: (float) ($data['plannedQuantity'] ?? 0.0),
            issuedQuantity: (float) ($data['issuedQuantity'] ?? 0.0),
            uomCode: $data['uomCode'] ?? null,
            operationNumber: isset($data['operationNumber']) ? (int) $data['operationNumber'] : null,
            workCenterId: $data['workCenterId'] ?? null,
            plannedSetupHours: (float) ($data['plannedSetupHours'] ?? 0.0),
            plannedRunHours: (float) ($data['plannedRunHours'] ?? 0.0),
            actualSetupHours: (float) ($data['actualSetupHours'] ?? 0.0),
            actualRunHours: (float) ($data['actualRunHours'] ?? 0.0),
            scrapQuantity: (float) ($data['scrapQuantity'] ?? 0.0),
            warehouseId: $data['warehouseId'] ?? null,
            lotNumber: $data['lotNumber'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
