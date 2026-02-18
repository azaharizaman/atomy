<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Listeners;

use Nexus\Sales\Events\SalesOrderConfirmedEvent;
use Nexus\SupplyChainOperations\Coordinators\DropshipCoordinator;
use Nexus\SupplyChainOperations\DataProviders\DropshipDataProvider;
use Psr\Log\LoggerInterface;

/**
 * Listens for confirmed sales orders and triggers dropship PO creation.
 *
 * This listener coordinates the dropshipping workflow by:
 * 1. Detecting dropship orders (via warehouse ID or metadata)
 * 2. Using DropshipDataProvider to resolve and group vendors
 * 3. Creating purchase orders per vendor via DropshipCoordinator
 */
final readonly class DropshipListener
{
    private const DROPSHIP_WAREHOUSE_ID = 'WH-DS';
    private const DROPSHIP_METADATA_KEY = 'is_dropship';

    public function __construct(
        private DropshipCoordinator $dropshipCoordinator,
        private DropshipDataProvider $dataProvider,
        private LoggerInterface $logger
    ) {
    }

    public function onSalesOrderConfirmed(SalesOrderConfirmedEvent $event): void
    {
        $order = $event->salesOrder;

        $isDropship = $this->isDropshipOrder($order);

        if (!$isDropship) {
            return;
        }

        $this->logger->info("Dropship order detected: {$order->getOrderNumber()}");

        $lines = $order->getLines();

        if (empty($lines)) {
            $this->logger->warning("Dropship order {$order->getOrderNumber()} has no lines.");
            return;
        }

        $vendorGroups = $this->dataProvider->groupLinesByVendor(
            $order->getTenantId(),
            $lines
        );

        if (empty($vendorGroups)) {
            $this->logger->error(
                "No vendors found for dropship order {$order->getOrderNumber()}. Cannot create POs."
            );
            return;
        }

        $createdPoIds = [];
        foreach ($vendorGroups as $vendorId => $vendorLines) {
            try {
                $poId = $this->dropshipCoordinator->createDropshipPo(
                    $order,
                    $vendorLines,
                    $vendorId
                );
                $createdPoIds[] = $poId;
                $this->logger->info(
                    "Created dropship PO {$poId} for vendor {$vendorId} with " . count($vendorLines) . " line(s)"
                );
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Failed to create dropship PO for vendor {$vendorId}: " . $e->getMessage(),
                    ['exception' => $e]
                );
            }
        }

        if (!empty($createdPoIds)) {
            $this->logger->info(
                "Dropship order {$order->getOrderNumber()} processed. Created " . count($createdPoIds) . " PO(s)."
            );
        }
    }

    private function isDropshipOrder($order): bool
    {
        if ($order->getPreferredWarehouseId() === self::DROPSHIP_WAREHOUSE_ID) {
            return true;
        }

        $metadata = $order->getMetadata();
        if (isset($metadata[self::DROPSHIP_METADATA_KEY]) && $metadata[self::DROPSHIP_METADATA_KEY] === true) {
            return true;
        }

        return false;
    }
}
