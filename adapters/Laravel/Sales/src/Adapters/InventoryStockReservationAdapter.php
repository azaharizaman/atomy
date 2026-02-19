<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sales\Adapters;

use Illuminate\Support\Facades\DB;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Sales\Exceptions\InsufficientStockException;
use Nexus\Sales\Exceptions\SalesOrderNotFoundException;
use Nexus\Sales\ValueObjects\StockAvailabilityResult;
use Nexus\Inventory\Contracts\ReservationManagerInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Exceptions\InsufficientStockException as InventoryInsufficientStockException;
use Psr\Log\LoggerInterface;

/**
 * Stock reservation adapter that integrates with Nexus\Inventory package.
 *
 * This adapter implements the Sales package's StockReservationInterface by
 * delegating stock reservation to the Inventory package's ReservationManager.
 *
 * This adapter belongs in the Laravel adapter layer because it requires
 * the Inventory package as a concrete dependency, which would violate
 * the atomic package independence rule if placed in the Sales package.
 */
final readonly class InventoryStockReservationAdapter implements StockReservationInterface
{
    private const REFERENCE_TYPE = 'sales_order';
    private const DEFAULT_TTL_HOURS = 24;
    private const RESERVATION_TABLE = 'sales_stock_reservations';

    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private ReservationManagerInterface $reservationManager,
        private StockLevelRepositoryInterface $stockLevelRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function reserveStockForOrder(string $salesOrderId): array
    {
        $this->logger->info('Reserving stock for sales order via Inventory adapter', [
            'sales_order_id' => $salesOrderId,
        ]);

        $order = $this->getSalesOrder($salesOrderId);
        $warehouseId = $order->getPreferredWarehouseId() ?? $this->getDefaultWarehouseId();

        $reservations = [];
        $errors = [];

        foreach ($order->getLines() as $line) {
            try {
                $reservationId = $this->reservationManager->reserveStock(
                    productId: $line->getProductVariantId(),
                    warehouseId: $warehouseId,
                    quantity: $line->getQuantity(),
                    referenceType: self::REFERENCE_TYPE,
                    referenceId: $salesOrderId,
                    ttlHours: self::DEFAULT_TTL_HOURS
                );

                $reservations[$line->getId()] = $reservationId;

                $this->logger->debug('Stock reserved for line', [
                    'line_id' => $line->getId(),
                    'reservation_id' => $reservationId,
                    'product_id' => $line->getProductVariantId(),
                    'quantity' => $line->getQuantity(),
                ]);
            } catch (InventoryInsufficientStockException $e) {
                // Parse available quantity from exception message
                // Message format: "Insufficient stock for product X in warehouse Y. Requested: Z, Available: W"
                $available = $this->parseAvailableQuantity($e->getMessage());

                $errors[] = sprintf(
                    'Insufficient stock for product %s: requested %s, available %s',
                    $line->getProductVariantId(),
                    $line->getQuantity(),
                    $available
                );

                $this->logger->warning('Insufficient stock for line', [
                    'line_id' => $line->getId(),
                    'product_id' => $line->getProductVariantId(),
                    'requested' => $line->getQuantity(),
                    'available' => $available,
                ]);
            }
        }

        // If any reservations failed, release the ones that succeeded
        if (!empty($errors)) {
            foreach ($reservations as $reservationId) {
                try {
                    $this->reservationManager->releaseReservation($reservationId);
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to release reservation during rollback', [
                        'reservation_id' => $reservationId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            throw InsufficientStockException::forOrder(
                $salesOrderId,
                implode('; ', $errors)
            );
        }

        $this->logger->info('Stock reserved successfully for sales order', [
            'sales_order_id' => $salesOrderId,
            'reservation_count' => count($reservations),
        ]);

        return $reservations;
    }

    /**
     * {@inheritDoc}
     */
    public function releaseStockReservation(string $salesOrderId): void
    {
        $this->logger->info('Releasing stock reservations for sales order', [
            'sales_order_id' => $salesOrderId,
        ]);

        $reservations = $this->getOrderReservations($salesOrderId);

        if (empty($reservations)) {
            $this->logger->debug('No reservations found for sales order', [
                'sales_order_id' => $salesOrderId,
            ]);
            return;
        }

        $releasedCount = 0;
        $failedCount = 0;

        foreach ($reservations as $reservationId => $reservation) {
            try {
                // Release the reservation through the Inventory package's ReservationManager
                $this->reservationManager->releaseReservation($reservationId);

                // Update the status in the sales_stock_reservations table
                $now = new \DateTimeImmutable();
                DB::table(self::RESERVATION_TABLE)
                    ->where('id', $reservationId)
                    ->update([
                        'status' => 'released',
                        'released_quantity' => $reservation['reserved_quantity'] - $reservation['fulfilled_quantity'],
                        'released_at' => $now,
                        'updated_at' => $now,
                    ]);

                $releasedCount++;

                $this->logger->debug('Reservation released', [
                    'reservation_id' => $reservationId,
                    'sales_order_id' => $salesOrderId,
                ]);
            } catch (\Throwable $e) {
                $failedCount++;

                $this->logger->error('Failed to release reservation', [
                    'reservation_id' => $reservationId,
                    'sales_order_id' => $salesOrderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Stock reservations release completed', [
            'sales_order_id' => $salesOrderId,
            'released_count' => $releasedCount,
            'failed_count' => $failedCount,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderReservations(string $salesOrderId): array
    {
        $this->logger->debug('Getting reservations for sales order', [
            'sales_order_id' => $salesOrderId,
        ]);

        // Query the sales_stock_reservations table directly to find all active reservations
        // for this sales order. We only get reservations that are in 'active' or 'partial' status
        // to avoid releasing already released/fulfilled reservations.
        $records = DB::table(self::RESERVATION_TABLE)
            ->where('sales_order_id', $salesOrderId)
            ->whereIn('status', ['active', 'partial'])
            ->get();

        $reservations = [];
        foreach ($records as $record) {
            $reservations[$record->id] = [
                'id' => $record->id,
                'sales_order_id' => $record->sales_order_id,
                'sales_order_line_id' => $record->sales_order_line_id,
                'product_variant_id' => $record->product_variant_id,
                'warehouse_id' => $record->warehouse_id,
                'reserved_quantity' => (float) $record->reserved_quantity,
                'allocated_quantity' => (float) $record->allocated_quantity,
                'fulfilled_quantity' => (float) $record->fulfilled_quantity,
                'released_quantity' => (float) $record->released_quantity,
                'status' => $record->status,
                'reservation_date' => $record->reservation_date,
                'fulfillment_deadline' => $record->fulfillment_deadline,
            ];
        }

        $this->logger->debug('Found reservations for sales order', [
            'sales_order_id' => $salesOrderId,
            'reservation_count' => count($reservations),
        ]);

        return $reservations;
    }

    /**
     * {@inheritDoc}
     */
    public function checkStockAvailability(string $salesOrderId): StockAvailabilityResult
    {
        $this->logger->debug('Checking stock availability for sales order', [
            'sales_order_id' => $salesOrderId,
        ]);

        $order = $this->getSalesOrder($salesOrderId);
        $warehouseId = $order->getPreferredWarehouseId() ?? $this->getDefaultWarehouseId();

        $lineItems = [];
        $unavailableLines = [];

        foreach ($order->getLines() as $line) {
            $onHand = $this->stockLevelRepository->getCurrentLevel(
                $line->getProductVariantId(),
                $warehouseId
            );
            $reserved = $this->stockLevelRepository->getReservedQuantity(
                $line->getProductVariantId(),
                $warehouseId
            );
            $available = $onHand - $reserved;

            $isAvailable = $available >= $line->getQuantity();

            $lineItems[$line->getId()] = new \Nexus\Sales\ValueObjects\LineItemAvailability(
                lineId: $line->getId(),
                productVariantId: $line->getProductVariantId(),
                warehouseId: $warehouseId,
                requestedQuantity: $line->getQuantity(),
                availableQuantity: $available,
                isAvailable: $isAvailable
            );

            if (!$isAvailable) {
                $unavailableLines[] = $line->getId();
            }
        }

        if (!empty($unavailableLines)) {
            $message = sprintf(
                'Insufficient stock for %d line item(s): %s',
                count($unavailableLines),
                implode(', ', $unavailableLines)
            );

            $this->logger->warning('Stock availability check failed', [
                'sales_order_id' => $salesOrderId,
                'unavailable_lines' => $unavailableLines,
            ]);

            return StockAvailabilityResult::unavailable(
                lineItems: $lineItems,
                unavailableLines: $unavailableLines,
                message: $message
            );
        }

        $this->logger->debug('Stock availability check passed', [
            'sales_order_id' => $salesOrderId,
            'total_lines' => count($lineItems),
        ]);

        return StockAvailabilityResult::available(lineItems: $lineItems);
    }

    /**
     * Get sales order by ID.
     *
     * @throws SalesOrderNotFoundException
     */
    private function getSalesOrder(string $salesOrderId): \Nexus\Sales\Contracts\SalesOrderInterface
    {
        return $this->salesOrderRepository->findById($salesOrderId);
    }

    /**
     * Get default warehouse ID.
     * In a production system, this would come from configuration or tenant settings.
     */
    private function getDefaultWarehouseId(): string
    {
        // TODO: Implement proper warehouse selection logic
        // This could come from:
        // - Tenant configuration
        // - Order shipping address
        // - Product default warehouse
        return 'default';
    }

    /**
     * Parse available quantity from exception message.
     *
     * Message format: "Insufficient stock for product X in warehouse Y. Requested: Z, Available: W"
     */
    private function parseAvailableQuantity(string $message): float
    {
        if (preg_match('/Available:\s*([0-9.]+)/', $message, $matches)) {
            return (float) $matches[1];
        }

        return 0.0;
    }
}