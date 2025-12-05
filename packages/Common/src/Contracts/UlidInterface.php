<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * ULID Generator Interface
 * 
 * Standardizes ULID (Universally Unique Lexicographically Sortable Identifier) generation
 * across all Nexus packages.
 * 
 * ULIDs are:
 * - 128-bit unique identifiers
 * - Lexicographically sortable (timestamp-based prefix)
 * - Case-insensitive, URL-safe Base32 encoded
 * - 26 characters long
 * 
 * This interface allows for dependency injection and testing by abstracting
 * the ULID generation implementation (typically Symfony\Component\Uid\Ulid).
 */
interface UlidInterface
{
    /**
     * Generate a new ULID as a string.
     * 
     * @return string A new ULID in canonical string format (26 characters, uppercase)
     */
    public function generate(): string;

    /**
     * Validate if a given string is a valid ULID format.
     * 
     * @param string $ulid The string to validate
     * @return bool True if valid ULID format, false otherwise
     */
    public function isValid(string $ulid): bool;

    /**
     * Parse a ULID string and extract its timestamp.
     * 
     * @param string $ulid The ULID to parse
     * @return \DateTimeImmutable The timestamp when the ULID was generated
     * @throws \InvalidArgumentException If the ULID is invalid
     */
    public function getTimestamp(string $ulid): \DateTimeImmutable;
}
