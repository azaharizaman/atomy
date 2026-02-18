<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface ShipmentInterface
{
    public function getId(): string;

    public function getShipmentNumber(): string;

    public function getOrderId(): string;

    public function getWarehouseId(): string;

    public function getStatus(): string;

    public function getTrackingNumber(): ?string;

    public function getShippedAt(): ?\DateTimeImmutable;

    public function getLines(): array;

    public function isShipped(): bool;
}

interface ShipmentLineInterface
{
    public function getId(): string;

    public function getProductVariantId(): string;

    public function getQuantityShipped(): float;
}

interface ShipmentProviderInterface
{
    public function findById(string $tenantId, string $shipmentId): ?ShipmentInterface;

    public function findByOrder(string $tenantId, string $orderId): array;

    public function create(string $tenantId, array $data): ShipmentInterface;

    public function confirmShipment(string $tenantId, string $shipmentId, string $trackingNumber): void;

    public function cancel(string $tenantId, string $shipmentId, string $reason): void;
}
