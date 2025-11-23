<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Exceptions;

/**
 * Exception thrown when cached flag data is stale or corrupted.
 *
 * Use Cases:
 * - Checksum mismatch (flag changed in DB but cache not invalidated)
 * - Cache corruption (serialization error)
 * - Version mismatch (flag schema evolved)
 *
 * Recovery Strategy: Evict stale cache entry and refetch from repository
 */
final class StaleCacheException extends FeatureFlagException
{
    public static function checksumMismatch(
        string $flagName,
        string $storedChecksum,
        string $computedChecksum
    ): self {
        return new self(
            "Stale cache for flag '{$flagName}': " .
            "stored checksum '{$storedChecksum}' does not match computed checksum '{$computedChecksum}'."
        );
    }

    public static function corruptedData(string $flagName, string $reason): self
    {
        return new self(
            "Corrupted cache data for flag '{$flagName}': {$reason}"
        );
    }
}
