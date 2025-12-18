<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

/**
 * Interface for generating secure, cryptographically random identifiers.
 *
 * This interface provides a standardized API for secure ID generation,
 * wrapping cryptographic primitives for better standardization and
 * future post-quantum cryptography (PQC) readiness.
 *
 * All implementations MUST use cryptographically secure random number
 * generation (CSPRNG) per NIST SP 800-90A recommendations.
 *
 * @package Nexus\Crypto
 * @since 1.0.0
 */
interface SecureIdGeneratorInterface
{
    /**
     * Generate a unique identifier with the specified prefix.
     *
     * The identifier consists of a prefix followed by hex-encoded random bytes.
     * The total length of the identifier is: strlen($prefix) + ($length * 2).
     *
     * Examples:
     * - generateId('emp_', 8)  → "emp_a1b2c3d4e5f6g7h8" (20 chars)
     * - generateId('pay_', 16) → "pay_" + 32 hex chars  (36 chars)
     * - generateId('', 16)     → 32 hex chars only
     *
     * @param string $prefix Optional prefix for the ID (e.g., 'emp_', 'po_', 'inv_')
     * @param int $length Number of random bytes to use (default: 16, range: 4-64)
     * @return string The generated identifier (prefix + hex-encoded random bytes)
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generateId(string $prefix = '', int $length = 16): string;

    /**
     * Generate a UUID v4 compatible identifier.
     *
     * Generates a universally unique identifier conforming to RFC 4122.
     * The UUID is generated using cryptographically secure random bytes.
     *
     * Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     * Where 'y' is one of [8, 9, a, b] per RFC 4122.
     *
     * @return string UUID v4 string in standard 36-character format
     */
    public function generateUuid(): string;

    /**
     * Generate cryptographically secure random bytes.
     *
     * Returns raw binary bytes suitable for cryptographic operations.
     * Use bin2hex() to convert to hex string, or base64_encode() for
     * text-safe representation.
     *
     * @param int $length Number of bytes to generate (range: 1-1048576)
     * @return string Raw binary bytes (NOT hex or base64 encoded)
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function randomBytes(int $length): string;

    /**
     * Generate hex-encoded random bytes.
     *
     * Convenience method that generates random bytes and returns them
     * as a lowercase hexadecimal string.
     *
     * The output string length is 2 * $length since each byte
     * produces 2 hex characters.
     *
     * Examples:
     * - randomHex(4)  → 8 hex characters (e.g., "a1b2c3d4")
     * - randomHex(8)  → 16 hex characters
     * - randomHex(16) → 32 hex characters
     * - randomHex(32) → 64 hex characters
     *
     * @param int $length Number of random bytes to generate (range: 1-1048576)
     * @return string Lowercase hex-encoded string (length = 2 * $length)
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function randomHex(int $length): string;
}
