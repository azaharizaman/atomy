<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * Planned Order value object.
 *
 * Represents a planned manufacturing or purchase order from MRP.
 */
final readonly class PlannedOrder
{
    /**
     * @param string $productId Product to order
     * @param float $quantity Order quantity (after lot sizing)
     * @param \DateTimeImmutable $startDate Planned start/release date
     * @param \DateTimeImmutable $dueDate Planned completion/receipt date
     * @param string $orderType Type: 'manufacturing' or 'purchase'
     * @param int $level BOM level
     * @param string $lotSizingStrategy Lot sizing strategy used
     * @param float $originalRequirement Original net requirement before lot sizing
     * @param string|null $sourceReference Reference to demand source
     * @param array<MaterialRequirement> $materialRequirements Component requirements
     * @param string $uomCode Unit of measure code
     */
    public function __construct(
        public string $productId,
        public float $quantity,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $dueDate,
        public string $orderType,
        public int $level = 0,
        public string $lotSizingStrategy = 'lot_for_lot',
        public float $originalRequirement = 0.0,
        public ?string $sourceReference = null,
        public array $materialRequirements = [],
        public string $uomCode = 'EA',
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
        if (!in_array($this->orderType, ['manufacturing', 'purchase'], true)) {
            throw new \InvalidArgumentException('Order type must be "manufacturing" or "purchase"');
        }
    }

    /**
     * Check if this is a manufacturing order.
     */
    public function isManufacturing(): bool
    {
        return $this->orderType === 'manufacturing';
    }

    /**
     * Check if this is a purchase order.
     */
    public function isPurchase(): bool
    {
        return $this->orderType === 'purchase';
    }

    /**
     * Get lead time in days.
     */
    public function getLeadTimeDays(): int
    {
        return (int) $this->startDate->diff($this->dueDate)->days;
    }

    /**
     * Check if order was lot-sized (quantity differs from original requirement).
     */
    public function wasLotSized(): bool
    {
        return abs($this->quantity - $this->originalRequirement) > 0.001;
    }

    /**
     * Get excess quantity from lot sizing.
     */
    public function getExcessQuantity(): float
    {
        return max(0, $this->quantity - $this->originalRequirement);
    }

    /**
     * Check if this is a top-level order.
     */
    public function isTopLevel(): bool
    {
        return $this->level === 0;
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
            'quantity' => $this->quantity,
            'startDate' => $this->startDate->format('Y-m-d'),
            'dueDate' => $this->dueDate->format('Y-m-d'),
            'orderType' => $this->orderType,
            'level' => $this->level,
            'lotSizingStrategy' => $this->lotSizingStrategy,
            'originalRequirement' => $this->originalRequirement,
            'sourceReference' => $this->sourceReference,
            'materialRequirements' => array_map(
                fn (MaterialRequirement $mr) => $mr->toArray(),
                $this->materialRequirements
            ),
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
            quantity: (float) $data['quantity'],
            startDate: new \DateTimeImmutable($data['startDate']),
            dueDate: new \DateTimeImmutable($data['dueDate']),
            orderType: $data['orderType'],
            level: (int) ($data['level'] ?? 0),
            lotSizingStrategy: $data['lotSizingStrategy'] ?? 'lot_for_lot',
            originalRequirement: (float) ($data['originalRequirement'] ?? 0.0),
            sourceReference: $data['sourceReference'] ?? null,
            materialRequirements: array_map(
                fn (array $mr) => MaterialRequirement::fromArray($mr),
                $data['materialRequirements'] ?? []
            ),
            uomCode: $data['uomCode'] ?? 'EA',
        );
    }
}
