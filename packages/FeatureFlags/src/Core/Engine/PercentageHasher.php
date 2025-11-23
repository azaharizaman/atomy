<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Core\Engine;

/**
 * Consistent hashing for percentage-based rollout.
 *
 * Uses xxHash3 + CRC32 for fast, uniform distribution across 0-99 buckets.
 */
final readonly class PercentageHasher
{
    /**
     * Get the bucket (0-99) for a given identifier and flag name.
     *
     * Uses consistent hashing to ensure:
     * - Same identifier + flag name always returns same bucket
     * - Roughly uniform distribution across all buckets
     * - Different flags produce different distributions for same identifier
     *
     * Algorithm: CRC32(xxHash3(flagName|identifier)) % 100
     *
     * @param string $identifier The stable identifier (userId, sessionId, tenantId)
     * @param string $flagName The flag name (ensures different flags get different distributions)
     * @return int Bucket number 0-99
     */
    public function getBucket(string $identifier, string $flagName): int
    {
        // Combine flag name and identifier to ensure different flags
        // produce different distributions for the same user
        $input = $flagName . '|' . $identifier;

        // Use xxHash3 for fast, high-quality hashing
        // binary: true returns raw binary string instead of hex
        $hash = hash('xxh3', $input, binary: true);

        // CRC32 for final mixing and conversion to integer
        // Use unpack to handle CRC32's unsigned int properly
        $crc = crc32($hash);

        // Ensure positive value and map to 0-99
        return abs($crc) % 100;
    }
}
