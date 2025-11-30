<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * Trusted device interface
 * 
 * Represents a trusted device for user authentication
 */
interface TrustedDeviceInterface
{
    /**
     * Get device identifier
     */
    public function getId(): string;

    /**
     * Get user identifier
     */
    public function getUserId(): string;

    /**
     * Get device fingerprint hash
     */
    public function getFingerprint(): string;

    /**
     * Get device name (user-friendly identifier)
     */
    public function getDeviceName(): ?string;

    /**
     * Check if device is trusted
     */
    public function isTrusted(): bool;

    /**
     * Get when device was first trusted
     */
    public function getTrustedAt(): ?\DateTimeInterface;

    /**
     * Get when device was last used
     */
    public function getLastUsedAt(): ?\DateTimeInterface;

    /**
     * Get geographic location data
     * 
     * @return array<string, mixed>|null
     */
    public function getGeographicLocation(): ?array;

    /**
     * Get device metadata
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get last update timestamp
     */
    public function getUpdatedAt(): \DateTimeInterface;
}
