<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

/**
 * Domain interface for shipping/logistics providers.
 *
 * Vendors: FedEx, UPS, DHL, USPS, etc.
 */
interface ShippingProviderConnectorInterface
{
    /**
     * Create a new shipment.
     *
     * @param array<string, mixed> $shipmentData Shipment details (origin, destination, package, service)
     * @return array{tracking_number: string, label_url: string, cost: float}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function createShipment(array $shipmentData): array;

    /**
     * Track a shipment by tracking number.
     *
     * @param string $trackingNumber Tracking number
     * @return array{status: string, location: string, estimated_delivery: ?string, events: array}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function trackShipment(string $trackingNumber): array;

    /**
     * Calculate shipping rates for a package.
     *
     * @param array<string, mixed> $packageData Package details (weight, dimensions, origin, destination)
     * @return array<int, array{service: string, cost: float, delivery_days: int}>
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function calculateRates(array $packageData): array;

    /**
     * Cancel a shipment.
     *
     * @param string $trackingNumber Tracking number
     * @return bool True if cancellation was successful
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function cancelShipment(string $trackingNumber): bool;
}
