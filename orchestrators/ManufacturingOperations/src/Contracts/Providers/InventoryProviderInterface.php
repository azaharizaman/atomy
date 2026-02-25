<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Contracts\Providers;

use Nexus\Orchestrators\ManufacturingOperations\DTOs\StockCheckRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\StockReservationRequest;
use Nexus\Orchestrators\ManufacturingOperations\DTOs\StockReservationResult;

interface InventoryProviderInterface
{
    /**
     * Check stock availability for multiple items.
     * Returns true if all items are available in requested quantities.
     */
    public function checkStockAvailability(string $tenantId, StockCheckRequest $request): bool;

    /**
     * Reserve stock for a production order.
     * 
     * @throws \Nexus\Orchestrators\ManufacturingOperations\Exceptions\StockShortageException
     */
    public function reserveStock(string $tenantId, StockReservationRequest $request): StockReservationResult;

    /**
     * Release reserved stock back to available inventory (e.g., on order cancellation).
     */
    public function releaseReservation(string $tenantId, string $reservationId): void;

    /**
     * Issue stock to a work center (consumption).
     */
    public function issueStock(string $tenantId, string $reservationId, float $quantity): void;

    /**
     * Receive finished goods into inventory.
     */
    public function receiveStock(string $tenantId, string $productId, float $quantity, string $warehouseId): void;
}
