<?php

declare(strict_types=1);

namespace Nexus\SharedKernel\ValueObjects;

use Nexus\SharedKernel\Exceptions\InvalidValueException;

/**
 * TenantId Value Object
 *
 * Strongly-typed tenant identifier using ULID format.
 * All Nexus packages use ULIDs (26-character Base32) for primary keys.
 *
 * Benefits:
 * - Distributed generation without collisions
 * - Lexicographically sortable (time-based)
 * - URL-safe and case-insensitive
 */
final readonly class TenantId
{
    private const ULID_LENGTH = 26;
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/i';

    private function __construct(
        private string $value
    ) {
        $this->validate($value);
    }

    /**
     * Generate a new TenantId with a random ULID
     */
    public static function generate(): self
    {
        return new self(self::generateUlid());
    }

    /**
     * Create TenantId from an existing ULID string
     *
     * @param string $ulid ULID string (26 characters, Crockford Base32)
     * @throws InvalidValueException if ULID format is invalid
     */
    public static function fromString(string $ulid): self
    {
        return new self(strtoupper($ulid));
    }

    /**
     * Get the ULID value as string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another TenantId
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validate ULID format
     *
     * @throws InvalidValueException if format is invalid
     */
    private function validate(string $ulid): void
    {
        if (strlen($ulid) !== self::ULID_LENGTH) {
            throw new InvalidValueException(
                sprintf('TenantId must be %d characters, got %d', self::ULID_LENGTH, strlen($ulid))
            );
        }

        if (!preg_match(self::ULID_PATTERN, $ulid)) {
            throw new InvalidValueException(
                sprintf('TenantId must be valid ULID (Crockford Base32), got: %s', $ulid)
            );
        }
    }

    /**
     * Generate a ULID (Universally Unique Lexicographically Sortable Identifier)
     *
     * Format: 10 chars timestamp (48 bits) + 16 chars randomness (80 bits)
     * Encoding: Crockford Base32 (excludes I, L, O, U)
     */
    private static function generateUlid(): string
    {
        $encodingChars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

        // 48-bit timestamp in milliseconds
        $timestamp = (int)(microtime(true) * 1000);

        // Encode timestamp (10 characters - 50 bits, but we only use 48)
        $timeEncoded = '';
        for ($i = 9; $i >= 0; $i--) {
            $timeEncoded = $encodingChars[$timestamp & 0x1F] . $timeEncoded;
            $timestamp >>= 5;
        }

        // 80-bit randomness (16 characters)
        // We need exactly 80 bits = 16 base32 characters
        // Each base32 char encodes 5 bits, so 16 * 5 = 80 bits
        $randomBytes = random_bytes(10); // 80 bits
        $randomEncoded = '';

        // Convert 10 bytes (80 bits) to 16 base32 characters
        // Process as a bit stream
        $bitBuffer = 0;
        $bitCount = 0;
        $byteIndex = 0;

        for ($i = 0; $i < 16; $i++) {
            // Need 5 bits for each character
            while ($bitCount < 5 && $byteIndex < 10) {
                $bitBuffer = ($bitBuffer << 8) | ord($randomBytes[$byteIndex]);
                $bitCount += 8;
                $byteIndex++;
            }
            $bitCount -= 5;
            $randomEncoded .= $encodingChars[($bitBuffer >> $bitCount) & 0x1F];
        }

        return $timeEncoded . $randomEncoded;
    }
}
