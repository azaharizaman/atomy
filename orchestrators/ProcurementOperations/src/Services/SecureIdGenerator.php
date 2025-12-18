<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;

/**
 * Secure ID generator implementation using Nexus\Crypto.
 *
 * Provides cryptographically secure ID generation for:
 * - Workflow IDs
 * - Saga IDs
 * - Payment IDs
 * - Vendor API credentials
 * - Session tokens
 *
 * This implementation leverages Nexus\Crypto for post-quantum
 * cryptography (PQC) readiness and algorithm agility.
 */
final readonly class SecureIdGenerator implements SecureIdGeneratorInterface
{
    public function __construct(
        private CryptoManagerInterface $crypto,
    ) {}

    /**
     * @inheritDoc
     */
    public function generateId(string $prefix = '', int $length = 16): string
    {
        $randomPart = $this->randomHex($length);

        if ($prefix === '') {
            return $randomPart;
        }

        return $prefix . $randomPart;
    }

    /**
     * @inheritDoc
     */
    public function generateUuid(): string
    {
        // Generate 16 random bytes for UUID v4
        $data = $this->crypto->randomBytes(16);

        // Set version to 4 (random)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @inheritDoc
     */
    public function generateClientId(string $entityId): string
    {
        // Use HMAC-based derivation for deterministic but secure client IDs
        $hash = $this->crypto->hmac(
            data: $entityId,
            key: 'client_id_derivation_key',
        );

        return sprintf(
            'vnd_%s_%s',
            substr($hash, 0, 8),
            $this->randomHex(8),
        );
    }

    /**
     * @inheritDoc
     */
    public function generateClientSecret(int $length = 32): string
    {
        return $this->randomHex($length);
    }

    /**
     * @inheritDoc
     */
    public function generateSessionToken(int $length = 32): string
    {
        return $this->randomHex($length);
    }

    /**
     * @inheritDoc
     */
    public function randomBytes(int $length): string
    {
        return $this->crypto->randomBytes($length);
    }

    /**
     * @inheritDoc
     */
    public function randomHex(int $length): string
    {
        return bin2hex($this->crypto->randomBytes($length));
    }
}
