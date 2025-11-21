<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\LotManagerInterface;
use Nexus\Inventory\Events\LotAssignedEvent;
use Nexus\Inventory\Exceptions\LotNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Lot management service with FEFO enforcement
 */
final readonly class LotManager implements LotManagerInterface
{
    public function __construct(
        private LotRepositoryInterface $repository,
        private EventPublisherInterface $eventPublisher,
        private LoggerInterface $logger
    ) {
    }
    
    public function createLot(
        string $lotNumber,
        string $productId,
        \DateTimeImmutable $expiryDate,
        ?\DateTimeImmutable $manufacturingDate = null
    ): string {
        $this->logger->info('Creating lot', [
            'lot_number' => $lotNumber,
            'product_id' => $productId,
            'expiry_date' => $expiryDate->format('Y-m-d'),
        ]);
        
        $lotId = $this->repository->create($lotNumber, $productId, $expiryDate, $manufacturingDate);
        
        $this->logger->info('Lot created', ['lot_id' => $lotId]);
        
        return $lotId;
    }
    
    public function assignLotToReceipt(
        string $lotId,
        string $productId,
        string $warehouseId,
        float $quantity
    ): void {
        $lot = $this->repository->findById($lotId);
        
        if ($lot === null) {
            throw LotNotFoundException::withId($lotId);
        }
        
        $this->repository->assignToWarehouse($lotId, $warehouseId, $quantity);
        
        // Publish event
        $event = new LotAssignedEvent(
            lotId: $lotId,
            lotNumber: $lot['lot_number'],
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            expiryDate: new \DateTimeImmutable($lot['expiry_date']),
            assignedDate: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
    }
    
    public function getLotsForIssue(string $productId, string $warehouseId): array
    {
        // FEFO: Order by expiry_date ASC
        return $this->repository->findByProductAndWarehouse(
            $productId,
            $warehouseId,
            orderBy: 'expiry_date',
            direction: 'ASC'
        );
    }
    
    public function issueFromLot(string $lotId, float $quantity): void
    {
        $this->repository->reduceQuantity($lotId, $quantity);
        
        $this->logger->info('Issued from lot', [
            'lot_id' => $lotId,
            'quantity' => $quantity,
        ]);
    }
}

/**
 * Lot repository contract
 */
interface LotRepositoryInterface
{
    public function create(string $lotNumber, string $productId, \DateTimeImmutable $expiryDate, ?\DateTimeImmutable $manufacturingDate): string;
    public function findById(string $lotId): ?array;
    public function assignToWarehouse(string $lotId, string $warehouseId, float $quantity): void;
    public function findByProductAndWarehouse(string $productId, string $warehouseId, string $orderBy, string $direction): array;
    public function reduceQuantity(string $lotId, float $quantity): void;
}
