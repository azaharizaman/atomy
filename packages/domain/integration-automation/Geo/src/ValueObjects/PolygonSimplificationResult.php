<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

/**
 * Immutable value object representing polygon simplification result
 * 
 * Contains original/simplified vertex counts and compression ratio
 */
final readonly class PolygonSimplificationResult implements \JsonSerializable
{
    public function __construct(
        public array $originalPolygon,
        public array $simplifiedPolygon,
        public int $originalVertexCount,
        public int $simplifiedVertexCount,
        public float $tolerance
    ) {
    }

    /**
     * Calculate compression ratio
     */
    public function getCompressionRatio(): float
    {
        if ($this->originalVertexCount === 0) {
            return 0.0;
        }

        return ($this->simplifiedVertexCount / $this->originalVertexCount) * 100;
    }

    /**
     * Calculate vertices removed count
     */
    public function getVerticesRemoved(): int
    {
        return $this->originalVertexCount - $this->simplifiedVertexCount;
    }

    /**
     * Check if simplification was effective (removed >10% vertices)
     */
    public function isEffective(float $minimumReduction = 10.0): bool
    {
        $reductionPercentage = 100 - $this->getCompressionRatio();
        return $reductionPercentage >= $minimumReduction;
    }

    public function toArray(): array
    {
        return [
            'original_vertex_count' => $this->originalVertexCount,
            'simplified_vertex_count' => $this->simplifiedVertexCount,
            'vertices_removed' => $this->getVerticesRemoved(),
            'compression_ratio' => round($this->getCompressionRatio(), 2),
            'tolerance' => $this->tolerance,
            'simplified_polygon' => $this->simplifiedPolygon,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
