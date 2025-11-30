<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Warehouse entity interface
 */
interface WarehouseInterface
{
    /**
     * Get warehouse unique identifier
     * 
     * @return string
     */
    public function getId(): string;
    
    /**
     * Get warehouse code
     * 
     * @return string
     */
    public function getCode(): string;
    
    /**
     * Get warehouse name
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Get warehouse address
     * 
     * @return string|null
     */
    public function getAddress(): ?string;
    
    /**
     * Check if warehouse is active
     * 
     * @return bool
     */
    public function isActive(): bool;
    
    /**
     * Get warehouse metadata
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
