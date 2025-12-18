<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\ValueObjects\EncryptionKey;

/**
 * Key Rotation Service Interface
 *
 * Defines the contract for key rotation operations.
 * Focused interface following ISP for rotation-specific needs.
 *
 * @see \Nexus\Crypto\Services\CryptoManager Implementation
 * @see \Nexus\Crypto\Handlers\KeyRotationHandler Consumer
 */
interface KeyRotationServiceInterface
{
    /**
     * Find encryption keys expiring within the specified time window
     *
     * @param int $daysFromNow Number of days to look ahead for expiring keys
     * @return array<string> Array of key IDs that are expiring
     */
    public function findExpiringKeys(int $daysFromNow = 7): array;

    /**
     * Rotate an encryption key
     *
     * Creates a new key based on the old key's configuration
     * and marks the old key for eventual retirement.
     *
     * @param string $keyId The ID of the key to rotate
     * @return EncryptionKey The new encryption key
     * @throws \Nexus\Crypto\Exceptions\KeyNotFoundException If key doesn't exist
     * @throws \Nexus\Crypto\Exceptions\KeyRotationException If rotation fails
     */
    public function rotateKey(string $keyId): EncryptionKey;
}
