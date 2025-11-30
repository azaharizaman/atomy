<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\ValueObjects;

/**
 * BOM Line value object.
 *
 * Represents a single line item in a Bill of Materials.
 */
final readonly class BomLine
{
    /**
     * @param string $productId Component product ID
     * @param float $quantity Required quantity per parent
     * @param string $uomCode Unit of measure code
     * @param int $lineNumber Line sequence number
     * @param string|null $operationNumber Operation where component is consumed
     * @param float $scrapPercentage Expected scrap percentage
     * @param bool $isPhantom Whether component is a phantom BOM
     * @param \DateTimeImmutable|null $effectiveFrom Effectivity start date
     * @param \DateTimeImmutable|null $effectiveTo Effectivity end date
     * @param string|null $position Physical position indicator
     * @param string|null $notes Additional notes
     */
    public function __construct(
        public string $productId,
        public float $quantity,
        public string $uomCode,
        public int $lineNumber = 0,
        public ?string $operationNumber = null,
        public float $scrapPercentage = 0.0,
        public bool $isPhantom = false,
        public ?\DateTimeImmutable $effectiveFrom = null,
        public ?\DateTimeImmutable $effectiveTo = null,
        public ?string $position = null,
        public ?string $notes = null,
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
        if ($this->scrapPercentage < 0 || $this->scrapPercentage > 100) {
            throw new \InvalidArgumentException('Scrap percentage must be between 0 and 100');
        }
    }

    /**
     * Calculate quantity including scrap allowance.
     */
    public function getQuantityWithScrap(): float
    {
        return $this->quantity * (1 + $this->scrapPercentage / 100);
    }

    /**
     * Check if line is effective at a given date.
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
     * Create a copy with updated quantity.
     */
    public function withQuantity(float $quantity): self
    {
        return new self(
            productId: $this->productId,
            quantity: $quantity,
            uomCode: $this->uomCode,
            lineNumber: $this->lineNumber,
            operationNumber: $this->operationNumber,
            scrapPercentage: $this->scrapPercentage,
            isPhantom: $this->isPhantom,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $this->effectiveTo,
            position: $this->position,
            notes: $this->notes,
        );
    }

    /**
     * Create a copy with updated effectivity dates.
     */
    public function withEffectivity(
        ?\DateTimeImmutable $effectiveFrom,
        ?\DateTimeImmutable $effectiveTo = null
    ): self {
        return new self(
            productId: $this->productId,
            quantity: $this->quantity,
            uomCode: $this->uomCode,
            lineNumber: $this->lineNumber,
            operationNumber: $this->operationNumber,
            scrapPercentage: $this->scrapPercentage,
            isPhantom: $this->isPhantom,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
            position: $this->position,
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
            'productId' => $this->productId,
            'quantity' => $this->quantity,
            'uomCode' => $this->uomCode,
            'lineNumber' => $this->lineNumber,
            'operationNumber' => $this->operationNumber,
            'scrapPercentage' => $this->scrapPercentage,
            'isPhantom' => $this->isPhantom,
            'effectiveFrom' => $this->effectiveFrom?->format('Y-m-d'),
            'effectiveTo' => $this->effectiveTo?->format('Y-m-d'),
            'position' => $this->position,
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
            productId: $data['productId'],
            quantity: (float) $data['quantity'],
            uomCode: $data['uomCode'],
            lineNumber: (int) ($data['lineNumber'] ?? 0),
            operationNumber: $data['operationNumber'] ?? null,
            scrapPercentage: (float) ($data['scrapPercentage'] ?? 0.0),
            isPhantom: (bool) ($data['isPhantom'] ?? false),
            effectiveFrom: isset($data['effectiveFrom'])
                ? new \DateTimeImmutable($data['effectiveFrom'])
                : null,
            effectiveTo: isset($data['effectiveTo'])
                ? new \DateTimeImmutable($data['effectiveTo'])
                : null,
            position: $data['position'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
