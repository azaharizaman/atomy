<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\SerialManagerInterface;
use Nexus\Inventory\Events\SerialAllocatedEvent;
use Nexus\Inventory\Exceptions\DuplicateSerialException;
use Psr\Log\LoggerInterface;

/**
 * Serial number management service
 */
final readonly class SerialManager implements SerialManagerInterface
{
    public function __construct(
        private SerialRepositoryInterface $repository,
        private EventPublisherInterface $eventPublisher,
        private string $tenantId,
        private LoggerInterface $logger
    ) {
    }
    
    public function allocateSerial(
        string $serialNumber,
        string $productId,
        string $warehouseId,
        ?string $lotId = null
    ): string {
        $this->logger->info('Allocating serial number', [
            'serial_number' => $serialNumber,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);
        
        // Check for duplicate within tenant
        if ($this->repository->exists($serialNumber, $this->tenantId)) {
            throw DuplicateSerialException::forSerial($serialNumber, $this->tenantId);
        }
        
        $serialId = $this->repository->create($serialNumber, $productId, $warehouseId, $lotId, $this->tenantId);
        
        // Publish event
        $event = new SerialAllocatedEvent(
            serialId: $serialId,
            serialNumber: $serialNumber,
            productId: $productId,
            warehouseId: $warehouseId,
            allocatedDate: new \DateTimeImmutable(),
            lotId: $lotId
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Serial allocated', ['serial_id' => $serialId]);
        
        return $serialId;
    }
    
    public function issueSerial(string $serialId, string $referenceId): void
    {
        $this->repository->markAsIssued($serialId, $referenceId);
        
        $this->logger->info('Serial issued', [
            'serial_id' => $serialId,
            'reference_id' => $referenceId,
        ]);
    }
    
    public function getSerials(string $productId, string $warehouseId, bool $availableOnly = false): array
    {
        return $this->repository->findByProductAndWarehouse($productId, $warehouseId, $availableOnly);
    }
}

/**
 * Serial repository contract
 */
interface SerialRepositoryInterface
{
    public function exists(string $serialNumber, string $tenantId): bool;
    public function create(string $serialNumber, string $productId, string $warehouseId, ?string $lotId, string $tenantId): string;
    public function markAsIssued(string $serialId, string $referenceId): void;
    public function findByProductAndWarehouse(string $productId, string $warehouseId, bool $availableOnly): array;
}
