<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Trusted device query interface (CQRS Read Model)
 *
 * Handles read-only operations for trusted devices.
 * Consuming applications provide concrete implementations.
 */
interface TrustedDeviceQueryInterface
{
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
}
