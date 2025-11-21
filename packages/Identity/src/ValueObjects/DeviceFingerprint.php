<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

use Nexus\Crypto\Contracts\HasherInterface;

/**
 * Device fingerprint value object
 * 
 * Immutable representation of a device's unique fingerprint
 */
final readonly class DeviceFingerprint
{
    /**
     * Create new device fingerprint
     * 
     * @param string $hash SHA-256 hash of device characteristics
     * @param string $userAgent Full user agent string
     * @param string $platform Platform/OS (e.g., "Windows 10", "iOS 17.0")
     * @param string $browser Browser name and version
     * @param string|null $deviceId Hardware UUID if available
     */
    public function __construct(
        public string $hash,
        public string $userAgent,
        public string $platform,
        public string $browser,
        public ?string $deviceId = null
    ) {
        if (strlen($this->hash) !== 64) {
            throw new \InvalidArgumentException('Fingerprint hash must be 64 characters (SHA-256)');
        }

        if (empty($this->userAgent)) {
            throw new \InvalidArgumentException('User agent cannot be empty');
        }
    }

    /**
     * Create fingerprint from HTTP request data
     * 
     * @param array<string, mixed> $requestData Request data including user_agent, accept_language, etc.
     * @param HasherInterface $hasher Hasher for creating fingerprint hash
     * @return self
     */
    public static function fromRequest(array $requestData, HasherInterface $hasher): self
    {
        $userAgent = $requestData['user_agent'] ?? 'Unknown';
        $acceptLanguage = $requestData['accept_language'] ?? '';
        $acceptEncoding = $requestData['accept_encoding'] ?? '';
        $deviceId = $requestData['device_id'] ?? null;

        // Create fingerprint from stable characteristics
        $components = [
            $userAgent,
            $acceptLanguage,
            $acceptEncoding,
        ];

        $hashResult = $hasher->hash(implode('|', array_filter($components)));
        $hash = $hashResult->hash;

        // Parse platform and browser from user agent
        [$platform, $browser] = self::parseUserAgent($userAgent);

        return new self(
            hash: $hash,
            userAgent: $userAgent,
            platform: $platform,
            browser: $browser,
            deviceId: $deviceId
        );
    }

    /**
     * Parse user agent to extract platform and browser
     * 
     * @return array{0: string, 1: string}
     */
    private static function parseUserAgent(string $userAgent): array
    {
        // Simple parsing - in production, use a library like WhichBrowser
        $platform = 'Unknown';
        $browser = 'Unknown';

        // Detect platform
        if (str_contains($userAgent, 'Windows NT 10.0')) {
            $platform = 'Windows 10';
        } elseif (str_contains($userAgent, 'Windows NT')) {
            $platform = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches);
            $platform = 'macOS ' . ($matches[1] ?? '');
        } elseif (str_contains($userAgent, 'Linux')) {
            $platform = 'Linux';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $platform = 'iOS';
        } elseif (str_contains($userAgent, 'Android')) {
            preg_match('/Android ([0-9.]+)/', $userAgent, $matches);
            $platform = 'Android ' . ($matches[1] ?? '');
        }

        // Detect browser
        if (str_contains($userAgent, 'Edg/')) {
            preg_match('/Edg\/([0-9.]+)/', $userAgent, $matches);
            $browser = 'Edge ' . ($matches[1] ?? '');
        } elseif (str_contains($userAgent, 'Chrome/')) {
            preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches);
            $browser = 'Chrome ' . ($matches[1] ?? '');
        } elseif (str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome')) {
            preg_match('/Version\/([0-9.]+)/', $userAgent, $matches);
            $browser = 'Safari ' . ($matches[1] ?? '');
        } elseif (str_contains($userAgent, 'Firefox/')) {
            preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches);
            $browser = 'Firefox ' . ($matches[1] ?? '');
        }

        return [$platform, $browser];
    }

    /**
     * Get short description of device
     */
    public function getDescription(): string
    {
        return "{$this->browser} on {$this->platform}";
    }

    /**
     * Convert to array
     * 
     * @return array{hash: string, user_agent: string, platform: string, browser: string, device_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'user_agent' => $this->userAgent,
            'platform' => $this->platform,
            'browser' => $this->browser,
            'device_id' => $this->deviceId,
        ];
    }
}
