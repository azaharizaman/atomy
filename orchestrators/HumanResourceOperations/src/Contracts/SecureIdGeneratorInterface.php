<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

/**
 * Interface for generating secure, cryptographically random identifiers.
 *
 * This interface wraps Nexus\Crypto for secure ID generation,
 * replacing direct random_bytes() calls for better standardization
 * and future post-quantum cryptography (PQC) readiness.
 */
interface SecureIdGeneratorInterface
{
    /**
     * Generate a unique identifier with the specified prefix.
     *
     * @param string $prefix Optional prefix for the ID (e.g., 'emp_', 'pay_', 'leave_')
     * @param int $length Number of random bytes to use (default: 16)
     * @return string The generated identifier
     */
    public function generateId(string $prefix = '', int $length = 16): string;

    /**
     * Generate a UUID v4 compatible identifier.
     *
     * @return string UUID v4 string
     */
    public function generateUuid(): string;

    /**
     * Generate cryptographically secure random bytes.
     *
     * @param int $length Number of bytes
     * @return string Raw bytes
     */
    public function randomBytes(int $length): string;

    /**
     * Generate hex-encoded random bytes.
     *
     * @param int $length Number of bytes before hex encoding
     * @return string Hex-encoded random bytes
     */
    public function randomHex(int $length): string;
}
