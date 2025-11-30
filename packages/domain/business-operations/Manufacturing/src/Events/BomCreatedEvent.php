<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

/**
 * Event raised when a new BOM is created.
 */
final readonly class BomCreatedEvent
{
    public function __construct(
        public string $bomId,
        public string $productId,
        public string $version,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'bom.created',
            'bomId' => $this->bomId,
            'productId' => $this->productId,
            'version' => $this->version,
            'effectiveFrom' => $this->effectiveFrom->format('c'),
            'effectiveTo' => $this->effectiveTo?->format('c'),
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }
}
