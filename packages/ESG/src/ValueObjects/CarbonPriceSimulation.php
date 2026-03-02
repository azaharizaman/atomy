<?php

declare(strict_types=1);

namespace Nexus\ESG\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;

/**
 * Immutable value object representing a carbon price simulation result.
 */
final readonly class CarbonPriceSimulation implements SerializableVO
{
    public function __construct(
        public float $projectedTonnes,
        public float $carbonPrice,
        public string $currency,
        public float $projectedLiability,
        public array $hotSpots = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'projected_tonnes' => $this->projectedTonnes,
            'carbon_price' => $this->carbonPrice,
            'currency' => $this->currency,
            'projected_liability' => $this->projectedLiability,
            'hot_spots' => $this->hotSpots,
        ];
    }

    public function toString(): string
    {
        return "Projected Liability: {$this->projectedLiability} {$this->currency} (@ {$this->carbonPrice}/tonne)";
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromArray(array $data): static
    {
        return new self(
            (float)$data['projected_tonnes'],
            (float)$data['carbon_price'],
            $data['currency'],
            (float)$data['projected_liability'],
            $data['hot_spots'] ?? []
        );
    }
}
