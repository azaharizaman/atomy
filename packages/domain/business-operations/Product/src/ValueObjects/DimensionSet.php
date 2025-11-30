<?php

declare(strict_types=1);

namespace Nexus\Product\ValueObjects;

use JsonSerializable;
use Nexus\Uom\ValueObjects\Quantity;

/**
 * Dimension Set Value Object
 *
 * Complete physical specifications for a product using Nexus\Uom\Quantity.
 * All dimensions are optional but must use valid UOM if provided.
 */
final readonly class DimensionSet implements JsonSerializable
{
    /**
     * @param Quantity|null $weight Product weight (e.g., kg, lb)
     * @param Quantity|null $length Product length (e.g., cm, in)
     * @param Quantity|null $width Product width (e.g., cm, in)
     * @param Quantity|null $height Product height (e.g., cm, in)
     * @param Quantity|null $volume Product volume (e.g., L, gal)
     */
    public function __construct(
        public ?Quantity $weight = null,
        public ?Quantity $length = null,
        public ?Quantity $width = null,
        public ?Quantity $height = null,
        public ?Quantity $volume = null
    ) {}

    /**
     * Check if any dimension is set
     *
     * @return bool
     */
    public function hasAnyDimension(): bool
    {
        return $this->weight !== null
            || $this->length !== null
            || $this->width !== null
            || $this->height !== null
            || $this->volume !== null;
    }

    /**
     * Check if all physical dimensions are set (length, width, height)
     *
     * @return bool
     */
    public function hasCompleteDimensions(): bool
    {
        return $this->length !== null
            && $this->width !== null
            && $this->height !== null;
    }

    /**
     * Get calculated volume from dimensions (if complete)
     * Returns null if dimensions are incomplete
     *
     * @return Quantity|null
     */
    public function getCalculatedVolume(): ?Quantity
    {
        if (!$this->hasCompleteDimensions()) {
            return null;
        }

        // All dimensions must be in same unit for calculation
        // This is simplified - real implementation should use ConversionEngine
        $volumeValue = $this->length->value * $this->width->value * $this->height->value;
        
        return new Quantity($volumeValue, $this->length->unitCode . '³');
    }

    /**
     * Convert to array representation
     *
     * @return array<string, array{value: float, unit: string}|null>
     */
    public function toArray(): array
    {
        return [
            'weight' => $this->weight?->toArray(),
            'length' => $this->length?->toArray(),
            'width' => $this->width?->toArray(),
            'height' => $this->height?->toArray(),
            'volume' => $this->volume?->toArray(),
        ];
    }

    /**
     * Create from array representation
     *
     * @param array<string, array{value: float, unit: string}|null> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            weight: isset($data['weight']) ? Quantity::fromArray($data['weight']) : null,
            length: isset($data['length']) ? Quantity::fromArray($data['length']) : null,
            width: isset($data['width']) ? Quantity::fromArray($data['width']) : null,
            height: isset($data['height']) ? Quantity::fromArray($data['height']) : null,
            volume: isset($data['volume']) ? Quantity::fromArray($data['volume']) : null
        );
    }

    /**
     * JSON serialization
     *
     * @return array<string, array{value: float, unit: string}|null>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create empty dimension set
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self();
    }
}
