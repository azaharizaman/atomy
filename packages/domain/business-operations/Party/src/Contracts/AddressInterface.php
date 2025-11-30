<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\AddressType;
use Nexus\Party\ValueObjects\PostalAddress;

/**
 * Party address interface.
 * 
 * Represents a physical address associated with a party.
 */
interface AddressInterface
{
    /**
     * Get the unique identifier for this address.
     */
    public function getId(): string;
    
    /**
     * Get the party identifier this address belongs to.
     */
    public function getPartyId(): string;
    
    /**
     * Get the address type classification.
     */
    public function getAddressType(): AddressType;
    
    /**
     * Get the postal address value object.
     */
    public function getPostalAddress(): PostalAddress;
    
    /**
     * Check if this is the primary address for the party.
     */
    public function isPrimary(): bool;
    
    /**
     * Get the effective start date for this address.
     */
    public function getEffectiveFrom(): ?\DateTimeInterface;
    
    /**
     * Get the effective end date for this address (null if currently active).
     */
    public function getEffectiveTo(): ?\DateTimeInterface;
    
    /**
     * Check if this address is currently active.
     */
    public function isActive(?\DateTimeInterface $asOf = null): bool;
    
    /**
     * Get additional metadata as key-value pairs.
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
    
    /**
     * Get the creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;
    
    /**
     * Get the last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeInterface;
}
