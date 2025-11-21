<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Services;

use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\Warehouse\Contracts\WarehouseInterface;
use Psr\Log\LoggerInterface;

/**
 * Warehouse management service
 */
final readonly class WarehouseManager implements WarehouseManagerInterface
{
    public function __construct(
        private WarehouseRepositoryInterface $repository,
        private string $tenantId,
        private LoggerInterface $logger
    ) {
    }
    
    public function createWarehouse(string $code, string $name, array $metadata = []): string
    {
        $this->logger->info('Creating warehouse', [
            'code' => $code,
            'name' => $name,
        ]);
        
        // Note: Actual warehouse creation requires a factory or model instantiation
        // This is a placeholder that needs to be implemented in the Atomy layer
        throw new \RuntimeException('Warehouse creation not yet implemented - requires factory pattern');
    }
    
    public function getWarehouse(string $warehouseId): array
    {
        $warehouse = $this->repository->findById($warehouseId);
        
        if ($warehouse === null) {
            return [];
        }
        
        return $this->convertToArray($warehouse);
    }
    
    public function listWarehouses(): array
    {
        $warehouses = $this->repository->findByTenant($this->tenantId);
        
        return array_map(
            fn(WarehouseInterface $warehouse) => $this->convertToArray($warehouse),
            $warehouses
        );
    }
    
    private function convertToArray(WarehouseInterface $warehouse): array
    {
        return [
            'id' => $warehouse->getId(),
            'code' => $warehouse->getCode(),
            'name' => $warehouse->getName(),
            'address' => $warehouse->getAddress(),
            'is_active' => $warehouse->isActive(),
            'metadata' => $warehouse->getMetadata(),
        ];
    }
}
