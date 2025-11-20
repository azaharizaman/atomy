<?php

declare(strict_types=1);

namespace Nexus\Party\Contracts;

use Nexus\Party\Enums\ContactMethodType;

/**
 * Party contact method interface.
 * 
 * Represents a method of contacting a party (email, phone, etc.).
 */
interface ContactMethodInterface
{
    /**
     * Get the unique identifier for this contact method.
     */
    public function getId(): string;
    
    /**
     * Get the party identifier this contact method belongs to.
     */
    public function getPartyId(): string;
    
    /**
     * Get the contact method type.
     */
    public function getType(): ContactMethodType;
    
    /**
     * Get the contact value (email address, phone number, etc.).
     */
    public function getValue(): string;
    
    /**
     * Check if this is the primary contact method for this type.
     */
    public function isPrimary(): bool;
    
    /**
     * Check if this contact method has been verified.
     */
    public function isVerified(): bool;
    
    /**
     * Get the verification timestamp (if verified).
     */
    public function getVerifiedAt(): ?\DateTimeInterface;
    
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
