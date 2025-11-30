<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

/**
 * Serial number management interface
 */
interface SerialManagerInterface
{
    /**
     * Allocate serial number to product instance
     * 
     * Publishes: SerialAllocatedEvent
     * 
     * @param string $serialNumber Serial number
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param string|null $lotId Optional lot assignment
     * @return string Serial ID
     * @throws \Nexus\Inventory\Exceptions\DuplicateSerialException
     */
    public function allocateSerial(
        string $serialNumber,
        string $productId,
        string $warehouseId,
        ?string $lotId = null
    ): string;
    
    /**
     * Mark serial as issued
     * 
     * @param string $serialId Serial identifier
     * @param string $referenceId Reference document (SO, WO, etc.)
     * @return void
     */
    public function issueSerial(string $serialId, string $referenceId): void;
    
    /**
     * Get all serials for a product in warehouse
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param bool $availableOnly Only return unissued serials
     * @return array<array{serial_id: string, serial_number: string, status: string}>
     */
    public function getSerials(string $productId, string $warehouseId, bool $availableOnly = false): array;
}
