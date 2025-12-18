<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Interface for hashing operations.
 *
 * Provides cryptographic hashing for data integrity verification
 * using Nexus\Crypto, supporting algorithm agility and future
 * post-quantum cryptography (PQC) readiness.
 */
interface HashingServiceInterface
{
    /**
     * Calculate SHA-256 hash of content.
     *
     * @param string $content The content to hash
     * @return string Hex-encoded hash
     */
    public function sha256(string $content): string;

    /**
     * Calculate SHA-512 hash of content.
     *
     * @param string $content The content to hash
     * @return string Hex-encoded hash
     */
    public function sha512(string $content): string;

    /**
     * Calculate BLAKE2b hash of content.
     *
     * @param string $content The content to hash
     * @return string Hex-encoded hash
     */
    public function blake2b(string $content): string;

    /**
     * Calculate file checksum for integrity verification.
     *
     * Uses SHA-256 by default for bank file checksums.
     *
     * @param string $content The file content to checksum
     * @return string Hex-encoded checksum
     */
    public function calculateChecksum(string $content): string;

    /**
     * Verify content against expected checksum.
     *
     * @param string $content The content to verify
     * @param string $expectedChecksum The expected checksum
     * @return bool True if checksum matches
     */
    public function verifyChecksum(string $content, string $expectedChecksum): bool;
}
