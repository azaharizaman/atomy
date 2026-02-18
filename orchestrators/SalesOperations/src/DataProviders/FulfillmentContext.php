<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

final readonly class FulfillmentContext
{
    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public string $tenantId,
        public string $customerId,
        public string $orderStatus,
        public array $lines,
        public array $shipments,
        public array $stockAvailability,
        public array $fulfillmentStatus,
    ) {}

    public function canCreateShipment(): bool
    {
        return $this->fulfillmentStatus['remaining_quantity'] > 0;
    }

    public function isFullyShipped(): bool
    {
        return $this->fulfillmentStatus['is_complete'];
    }

    public function hasSufficientStock(): bool
    {
        return $this->stockAvailability['available'];
    }

    public function getLinesToShip(): array
    {
        return array_filter(
            $this->lines,
            fn($line) => $line['remaining_to_ship'] > 0
        );
    }
}
