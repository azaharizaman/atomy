<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

/**
 * Event raised when a new version of a BOM is created.
 */
final readonly class BomVersionedEvent
{
    public function __construct(
        public string $bomId,
        public string $productId,
        public string $previousVersion,
        public string $newVersion,
        public string $sourceBomId,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'bom.versioned',
            'bomId' => $this->bomId,
            'productId' => $this->productId,
            'previousVersion' => $this->previousVersion,
            'newVersion' => $this->newVersion,
            'sourceBomId' => $this->sourceBomId,
            'effectiveFrom' => $this->effectiveFrom->format('c'),
            'effectiveTo' => $this->effectiveTo?->format('c'),
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }
}
