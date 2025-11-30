<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\TransferManagerInterface;
use Nexus\Inventory\Enums\TransferStatus;
use Nexus\Inventory\Events\StockTransferredEvent;
use Psr\Log\LoggerInterface;

/**
 * Stock transfer management service
 * 
 * FSM workflow: pending → in_transit → completed/cancelled
 */
final readonly class TransferManager implements TransferManagerInterface
{
    public function __construct(
        private TransferRepositoryInterface $repository,
        private StockManagerInterface $stockManager,
        private EventPublisherInterface $eventPublisher,
        private LoggerInterface $logger
    ) {
    }
    
    public function createTransfer(
        string $productId,
        string $fromWarehouseId,
        string $toWarehouseId,
        float $quantity,
        ?string $referenceId = null
    ): string {
        $this->logger->info('Creating stock transfer', [
            'product_id' => $productId,
            'from' => $fromWarehouseId,
            'to' => $toWarehouseId,
            'quantity' => $quantity,
        ]);
        
        $transferId = $this->repository->create(
            $productId,
            $fromWarehouseId,
            $toWarehouseId,
            $quantity,
            TransferStatus::PENDING,
            $referenceId
        );
        
        // Publish event
        $event = new StockTransferredEvent(
            transferId: $transferId,
            productId: $productId,
            fromWarehouseId: $fromWarehouseId,
            toWarehouseId: $toWarehouseId,
            quantity: $quantity,
            status: TransferStatus::PENDING,
            occurredAt: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Transfer created', ['transfer_id' => $transferId]);
        
        return $transferId;
    }
    
    public function shipTransfer(string $transferId): void
    {
        $transfer = $this->repository->findById($transferId);
        
        // Deduct from source warehouse
        $this->stockManager->issueStock(
            $transfer['product_id'],
            $transfer['from_warehouse_id'],
            $transfer['quantity'],
            \Nexus\Inventory\Enums\IssueReason::TRANSFER,
            $transferId
        );
        
        // Update status
        $this->repository->updateStatus($transferId, TransferStatus::IN_TRANSIT);
        
        // Publish event
        $event = new StockTransferredEvent(
            transferId: $transferId,
            productId: $transfer['product_id'],
            fromWarehouseId: $transfer['from_warehouse_id'],
            toWarehouseId: $transfer['to_warehouse_id'],
            quantity: $transfer['quantity'],
            status: TransferStatus::IN_TRANSIT,
            occurredAt: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Transfer shipped', ['transfer_id' => $transferId]);
    }
    
    public function receiveTransfer(string $transferId, ?float $receivedQty = null): void
    {
        $transfer = $this->repository->findById($transferId);
        $quantity = $receivedQty ?? $transfer['quantity'];
        
        // Add to destination warehouse
        $currentCost = $this->stockManager->getCurrentStock($transfer['product_id'], $transfer['from_warehouse_id']);
        
        $this->stockManager->receiveStock(
            $transfer['product_id'],
            $transfer['to_warehouse_id'],
            $quantity,
            0.0, // Transfer cost handled via valuation engine
            $transferId
        );
        
        // Update status
        $this->repository->updateStatus($transferId, TransferStatus::COMPLETED);
        $this->repository->updateReceivedQuantity($transferId, $quantity);
        
        // Publish event
        $event = new StockTransferredEvent(
            transferId: $transferId,
            productId: $transfer['product_id'],
            fromWarehouseId: $transfer['from_warehouse_id'],
            toWarehouseId: $transfer['to_warehouse_id'],
            quantity: $quantity,
            status: TransferStatus::COMPLETED,
            occurredAt: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Transfer received', ['transfer_id' => $transferId]);
    }
    
    public function cancelTransfer(string $transferId): void
    {
        $transfer = $this->repository->findById($transferId);
        
        $this->repository->updateStatus($transferId, TransferStatus::CANCELLED);
        
        // Publish event
        $event = new StockTransferredEvent(
            transferId: $transferId,
            productId: $transfer['product_id'],
            fromWarehouseId: $transfer['from_warehouse_id'],
            toWarehouseId: $transfer['to_warehouse_id'],
            quantity: $transfer['quantity'],
            status: TransferStatus::CANCELLED,
            occurredAt: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Transfer cancelled', ['transfer_id' => $transferId]);
    }
    
    public function getTransferStatus(string $transferId): TransferStatus
    {
        $transfer = $this->repository->findById($transferId);
        return TransferStatus::from($transfer['status']);
    }
}

/**
 * Transfer repository contract
 */
interface TransferRepositoryInterface
{
    public function create(string $productId, string $fromWarehouseId, string $toWarehouseId, float $quantity, TransferStatus $status, ?string $referenceId): string;
    public function findById(string $transferId): array;
    public function updateStatus(string $transferId, TransferStatus $status): void;
    public function updateReceivedQuantity(string $transferId, float $quantity): void;
}
