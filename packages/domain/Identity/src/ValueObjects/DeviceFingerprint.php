<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents a device fingerprint for trusted device management.
 *
 * Device fingerprints are HMAC-based hashes of device characteristics
 * (platform, user agent, etc.) used to implement "remember this device"
 * functionality and detect suspicious login patterns.
 *
 * @immutable
 */
final readonly class DeviceFingerprint
{
    /**
     * Create a new device fingerprint.
     *
     * @param string $hash The HMAC-SHA256 hash of device characteristics
     * @param string $platform The platform identifier (web, ios, android, desktop)
     * @param string $userAgent The user agent string (for web platform)
     * @param DateTimeImmutable $createdAt When the fingerprint was created
     * @throws InvalidArgumentException If any parameter is invalid
     */
    public function __construct(
        public string $hash,
        public string $platform,
        public string $userAgent,
        public DateTimeImmutable $createdAt,
    ) {
        $this->validateHash($hash);
        $this->validatePlatform($platform);
        $this->validateUserAgent($userAgent);
    }

    /**
     * Validate the hash format.
     *
     * @throws InvalidArgumentException If hash is invalid
     */
    private function validateHash(string $hash): void
    {
        if (empty($hash)) {
            throw new InvalidArgumentException('Device fingerprint hash cannot be empty');
        }

        // HMAC-SHA256 produces 64 hex characters
        if (!preg_match('/^[a-f0-9]{64}$/i', $hash)) {
            throw new InvalidArgumentException('Device fingerprint hash must be a valid SHA256 hex string (64 characters)');
        }
    }

    /**
     * Validate the platform.
     *
     * @throws InvalidArgumentException If platform is invalid
     */
    private function validatePlatform(string $platform): void
    {
        $validPlatforms = ['web', 'ios', 'android', 'desktop', 'unknown'];

        if (!in_array($platform, $validPlatforms, true)) {
            throw new InvalidArgumentException(
                "Invalid platform '{$platform}'. Must be one of: " . implode(', ', $validPlatforms)
            );
        }
    }

    /**
     * Validate the user agent.
     *
     * @throws InvalidArgumentException If user agent is invalid
     */
    private function validateUserAgent(string $userAgent): void
    {
        $trimmed = trim($userAgent);
        
        if (empty($trimmed)) {
            throw new InvalidArgumentException('User agent cannot be empty or whitespace only');
        }

        if (strlen($trimmed) > 500) {
            throw new InvalidArgumentException('User agent cannot exceed 500 characters');
        }
    }

    /**
     * Check if this fingerprint matches another (timing-attack resistant).
     *
     * @param string $otherHash The hash to compare against
     * @return bool True if hashes match
     */
    public function matches(string $otherHash): bool
    {
        return hash_equals($this->hash, $otherHash);
    }

    /**
     * Check if this fingerprint is expired based on a TTL.
     *
     * @param int $ttlSeconds Time-to-live in seconds (e.g., 2592000 for 30 days)
     * @param DateTimeImmutable|null $now The current time (for testing)
     * @return bool True if fingerprint is expired
     */
    public function isExpired(int $ttlSeconds, ?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable();
        $expiresAt = $this->createdAt->modify("+{$ttlSeconds} seconds");
        
        return $now >= $expiresAt;
    }

    /**
     * Get the fingerprint age in seconds.
     *
     * @param DateTimeImmutable|null $now The current time (for testing)
     * @return int Age in seconds
     */
    public function getAgeInSeconds(?DateTimeImmutable $now = null): int
    {
        $now = $now ?? new DateTimeImmutable();
        return $now->getTimestamp() - $this->createdAt->getTimestamp();
    }

    /**
     * Check if this is a web platform fingerprint.
     */
    public function isWebPlatform(): bool
    {
        return $this->platform === 'web';
    }

    /**
     * Check if this is a mobile platform fingerprint.
     */
    public function isMobilePlatform(): bool
    {
        return in_array($this->platform, ['ios', 'android'], true);
    }

    /**
     * Get a display-friendly platform name.
     */
    public function getPlatformDisplayName(): string
    {
        return match ($this->platform) {
            'web' => 'Web Browser',
            'ios' => 'iOS',
            'android' => 'Android',
            'desktop' => 'Desktop App',
            'unknown' => 'Unknown Device',
        };
    }

    /**
     * Extract browser name from user agent (best effort, for display only).
     *
     * @return string Browser name or 'Unknown Browser'
     */
    public function getBrowserName(): string
    {
        if (!$this->isWebPlatform()) {
            return 'N/A';
        }

        return match (true) {
            str_contains($this->userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($this->userAgent, 'Chrome/') => 'Google Chrome',
            str_contains($this->userAgent, 'Safari/') && !str_contains($this->userAgent, 'Chrome') => 'Safari',
            str_contains($this->userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($this->userAgent, 'Opera/') || str_contains($this->userAgent, 'OPR/') => 'Opera',
            default => 'Unknown Browser',
        };
    }

    /**
     * Generate a static fingerprint from device characteristics.
     *
     * This is a helper method to create the HMAC hash from raw data.
     * The secret should be stored securely (e.g., in environment config).
     *
     * @param string $secret The HMAC secret key
     * @param string $platform The platform identifier
     * @param string $userAgent The user agent string
     * @param string|null $ipAddress Optional IP address for additional entropy
     * @return string The HMAC-SHA256 hash (64 hex chars)
     */
    public static function generateHash(
        string $secret,
        string $platform,
        string $userAgent,
        ?string $ipAddress = null
    ): string {
        // Concatenate device characteristics
        $data = implode('|', array_filter([
            $platform,
            $userAgent,
            $ipAddress,
        ]));

        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Convert to array representation (for storage).
     *
     * @return array{
     *     hash: string,
     *     platform: string,
     *     user_agent: string,
     *     created_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'platform' => $this->platform,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
