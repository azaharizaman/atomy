<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\ProcurementOperations\Contracts\HashingServiceInterface;

/**
 * Hashing service using Nexus\Crypto for all cryptographic operations.
 *
 * Provides algorithm-agile hashing with support for SHA-256, SHA-512,
 * and BLAKE2b. Designed to be easily extensible for post-quantum
 * cryptography (PQC) when available.
 */
final readonly class HashingService implements HashingServiceInterface
{
    public function __construct(
        private CryptoManagerInterface $crypto
    ) {}

    /**
     * {@inheritDoc}
     */
    public function sha256(string $content): string
    {
        $result = $this->crypto->hash($content, HashAlgorithm::SHA256);

        return $result->hash;
    }

    /**
     * {@inheritDoc}
     */
    public function sha512(string $content): string
    {
        $result = $this->crypto->hash($content, HashAlgorithm::SHA512);

        return $result->hash;
    }

    /**
     * {@inheritDoc}
     */
    public function blake2b(string $content): string
    {
        $result = $this->crypto->hash($content, HashAlgorithm::BLAKE2B);

        return $result->hash;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateChecksum(string $content): string
    {
        // Use SHA-256 for file checksums - widely accepted for banking
        return $this->sha256($content);
    }

    /**
     * {@inheritDoc}
     */
    public function verifyChecksum(string $content, string $expectedChecksum): bool
    {
        $actualChecksum = $this->calculateChecksum($content);

        return hash_equals($expectedChecksum, $actualChecksum);
    }
}
