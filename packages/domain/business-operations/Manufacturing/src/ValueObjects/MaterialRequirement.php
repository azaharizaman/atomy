<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * Material Requirement value object.
 *
 * Represents a calculated material requirement from MRP.
 */
final readonly class MaterialRequirement
{
    /**
     * @param string $productId Product required
     * @param float $grossRequirement Gross quantity required
     * @param float $netRequirement Net quantity after netting
     * @param \DateTimeImmutable $requiredDate Date requirement is needed
     * @param \DateTimeImmutable $orderDate Date order should be placed/released
     * @param float $onHandQuantity Current on-hand inventory
     * @param float $scheduledReceipts Scheduled receipts (open orders)
     * @param float $safetyStock Safety stock level
     * @param int $level BOM level (0 = finished good)
     * @param string|null $parentProductId Parent product in BOM
     * @param string|null $sourceWorkOrderId Work order generating requirement
     * @param string $uomCode Unit of measure code
     */
    public function __construct(
        public string $productId,
        public float $grossRequirement,
        public float $netRequirement,
        public \DateTimeImmutable $requiredDate,
        public \DateTimeImmutable $orderDate,
        public float $onHandQuantity = 0.0,
        public float $scheduledReceipts = 0.0,
        public float $safetyStock = 0.0,
        public int $level = 0,
        public ?string $parentProductId = null,
        public ?string $sourceWorkOrderId = null,
        public string $uomCode = 'EA',
    ) {
        if ($this->grossRequirement < 0) {
            throw new \InvalidArgumentException('Gross requirement cannot be negative');
        }
        if ($this->level < 0) {
            throw new \InvalidArgumentException('Level cannot be negative');
        }
    }

    /**
     * Check if there is a net requirement.
     */
    public function hasNetRequirement(): bool
    {
        return $this->netRequirement > 0;
    }

    /**
     * Get available quantity (on hand + scheduled - safety stock).
     */
    public function getAvailableQuantity(): float
    {
        return max(0, $this->onHandQuantity + $this->scheduledReceipts - $this->safetyStock);
    }

    /**
     * Get shortage quantity.
     */
    public function getShortage(): float
    {
        return max(0, $this->grossRequirement - $this->getAvailableQuantity());
    }

    /**
     * Check if this is a top-level requirement.
     */
    public function isTopLevel(): bool
    {
        return $this->level === 0;
    }

    /**
     * Get days until required.
     */
    public function getDaysUntilRequired(): int
    {
        $now = new \DateTimeImmutable();
        return max(0, (int) $now->diff($this->requiredDate)->days);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'grossRequirement' => $this->grossRequirement,
            'netRequirement' => $this->netRequirement,
            'requiredDate' => $this->requiredDate->format('Y-m-d'),
            'orderDate' => $this->orderDate->format('Y-m-d'),
            'onHandQuantity' => $this->onHandQuantity,
            'scheduledReceipts' => $this->scheduledReceipts,
            'safetyStock' => $this->safetyStock,
            'level' => $this->level,
            'parentProductId' => $this->parentProductId,
            'sourceWorkOrderId' => $this->sourceWorkOrderId,
            'uomCode' => $this->uomCode,
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
            productId: $data['productId'],
            grossRequirement: (float) $data['grossRequirement'],
            netRequirement: (float) $data['netRequirement'],
            requiredDate: new \DateTimeImmutable($data['requiredDate']),
            orderDate: new \DateTimeImmutable($data['orderDate']),
            onHandQuantity: (float) ($data['onHandQuantity'] ?? 0.0),
            scheduledReceipts: (float) ($data['scheduledReceipts'] ?? 0.0),
            safetyStock: (float) ($data['safetyStock'] ?? 0.0),
            level: (int) ($data['level'] ?? 0),
            parentProductId: $data['parentProductId'] ?? null,
            sourceWorkOrderId: $data['sourceWorkOrderId'] ?? null,
            uomCode: $data['uomCode'] ?? 'EA',
        );
    }
}
