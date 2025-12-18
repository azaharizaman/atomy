<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\KeyPair;

/**
 * Key Generator Interface
 *
 * Generates cryptographic keys for symmetric and asymmetric operations.
 * Implemented by the service layer (e.g., SodiumKeyGenerator, OpenSSLKeyGenerator).
 */
interface KeyGeneratorInterface
{
    /**
     * Generate symmetric encryption key
     *
     * @param SymmetricAlgorithm $algorithm Algorithm for the key
     * @param int|null $expirationDays Days until expiration (null = never expires)
     * @return EncryptionKey Generated encryption key with metadata
     */
    public function generateSymmetricKey(
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM,
        ?int $expirationDays = null
    ): EncryptionKey;
    
    /**
     * Generate asymmetric key pair for signing
     *
     * @param AsymmetricAlgorithm $algorithm Algorithm for the key pair
     * @return KeyPair Generated public/private key pair
     * @throws \Nexus\Crypto\Exceptions\UnsupportedAlgorithmException If algorithm not supported
     * @throws \Nexus\Crypto\Exceptions\FeatureNotImplementedException If PQC algorithm not ready
     */
    public function generateKeyPair(
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): KeyPair;
    
    /**
     * Generate cryptographically secure random bytes
     *
     * Returns raw binary bytes suitable for cryptographic operations.
     * Use bin2hex() to convert to hex string, or base64_encode() for
     * text-safe representation.
     *
     * @param int $length Number of bytes to generate (1 to 1048576)
     * @return string Raw binary bytes (NOT base64-encoded)
     * @throws \InvalidArgumentException If length is invalid
     */
    public function generateRandomBytes(int $length): string;
}
