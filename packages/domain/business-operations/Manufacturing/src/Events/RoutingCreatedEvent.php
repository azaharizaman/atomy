<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Events;

/**
 * Event raised when a new Routing is created.
 */
final readonly class RoutingCreatedEvent
{
    public function __construct(
        public string $routingId,
        public string $productId,
        public string $version,
        public int $operationCount,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event' => 'routing.created',
            'routingId' => $this->routingId,
            'productId' => $this->productId,
            'version' => $this->version,
            'operationCount' => $this->operationCount,
            'effectiveFrom' => $this->effectiveFrom->format('c'),
            'effectiveTo' => $this->effectiveTo?->format('c'),
            'occurredAt' => $this->occurredAt->format('c'),
        ];
    }
}
