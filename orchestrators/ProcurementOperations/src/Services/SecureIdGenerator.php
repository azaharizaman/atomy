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
 *
 * Note on randomHex(): The length parameter specifies the number of
 * random bytes to generate. Since each byte becomes 2 hex characters,
 * the output string length is 2 * $length. For example, randomHex(8)
 * returns 16 hex characters.
 */
final readonly class SecureIdGenerator implements SecureIdGeneratorInterface
{
    /**
     * @param CryptoManagerInterface $crypto Crypto operations provider
     * @param string $clientIdDerivationKey Secret key for deterministic client ID derivation.
     *                                       Should be stored in secure configuration, not hardcoded.
     *                                       Minimum 32 bytes recommended for HMAC-SHA256.
     */
    public function __construct(
        private CryptoManagerInterface $crypto,
        private string $clientIdDerivationKey = '',
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
        // The derivation key should be injected from secure configuration
        if ($this->clientIdDerivationKey === '') {
            // Fallback to random-only generation if no key provided
            // bin2hex() on 4 bytes produces exactly 8 hex characters
            return sprintf(
                'vnd_%s_%s',
                bin2hex($this->crypto->randomBytes(4)),
                $this->randomHex(8),
            );
        }

        $hash = $this->crypto->hmac(
            data: $entityId,
            secret: $this->clientIdDerivationKey,
        );

        return sprintf(
            'vnd_%s_%s',
            substr($hash, 0, 8),
            /**
             * Generates a secure, unique ID using HMAC-based derivation with a random component.
             * 
             * This method creates an ID that is partially deterministic based on the entity ID
             * and includes a random suffix for enhanced uniqueness and security. The HMAC
             * ensures the entity-based portion is cryptographically secure, while the random
             * component prevents ID collision and adds unpredictability.
             * 
             * @param string $entityId The entity identifier used as input for HMAC derivation
             * @return string A 24-character hexadecimal ID (16 chars from HMAC + 8 random chars)
             */
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
     * Generate hex-encoded random bytes.
     *
     * Note: Each byte produces 2 hex characters, so the output
     * string length is 2 * $length. For example:
     * - randomHex(4) returns 8 hex characters
     * - randomHex(8) returns 16 hex characters
     * - randomHex(16) returns 32 hex characters
     *
     * @inheritDoc
     */
    public function randomHex(int $length): string
    {
        return bin2hex($this->crypto->randomBytes($length));
    }
}
