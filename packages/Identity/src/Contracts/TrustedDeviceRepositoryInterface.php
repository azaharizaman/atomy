<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Trusted device repository interface
 * 
 * Handles persistence of trusted devices
 */
interface TrustedDeviceRepositoryInterface
{
    /**
     * Save a trusted device record
     */
    public function save(TrustedDeviceInterface $device): void;

    /**
     * Find device by identifier
     */
    public function findById(string $id): ?TrustedDeviceInterface;

    /**
     * Find device by fingerprint
     */
    public function findByFingerprint(string $fingerprint): ?TrustedDeviceInterface;

    /**
     * Find device by user ID and fingerprint
     */
    public function findByUserIdAndFingerprint(string $userId, string $fingerprint): ?TrustedDeviceInterface;

    /**
     * Find all devices for a user
     * 
     * @return array<TrustedDeviceInterface>
     */
    public function findByUserId(string $userId): array;

    /**
     * Find trusted devices for a user
     * 
     * @return array<TrustedDeviceInterface>
     */
    public function findTrustedByUserId(string $userId): array;

    /**
     * Check if fingerprint exists for user
     */
    public function exists(string $userId, string $fingerprint): bool;

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
