<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * Trusted device persist interface (CQRS Write Model)
 *
 * Handles write operations for trusted devices.
 * Consuming applications provide concrete implementations.
 */
interface TrustedDevicePersistInterface
{
    /**
     * Save a trusted device record
     */
    public function save(TrustedDeviceInterface $device): void;

    /**
     * Delete a device record
     */
    public function delete(string $id): void;

    /**
     * Delete all devices for a user
     */
    public function deleteByUserId(string $userId): void;

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(string $id, \DateTimeInterface $lastUsedAt): void;

    /**
     * Mark device as trusted
     */
    public function markTrusted(string $id): void;

    /**
     * Mark device as untrusted
     */
    public function markUntrusted(string $id): void;
}
