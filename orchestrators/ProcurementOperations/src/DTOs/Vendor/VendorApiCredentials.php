<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Vendor API credentials DTO.
 *
 * Manages API access credentials for vendor portal integration.
 */
final readonly class VendorApiCredentials
{
    /**
     * @param string $vendorId Associated vendor ID
     * @param string $clientId API client identifier
     * @param string $clientSecret API client secret (hashed in storage)
     * @param VendorPortalTier $tier Portal tier determining rate limits
     * @param string $status Credential status (active, suspended, revoked)
     * @param array<string> $allowedScopes Allowed API scopes
     * @param array<string> $allowedIpAddresses IP whitelist (empty = all allowed)
     * @param int $rateLimit Requests per minute
     * @param int $dailyQuota Daily request quota
     * @param int $usedToday Requests used today
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $lastUsedAt Last usage timestamp
     * @param \DateTimeImmutable|null $expiresAt Expiration timestamp
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $vendorId,
        public string $clientId,
        public string $clientSecret,
        public VendorPortalTier $tier,
        public string $status = 'active',
        public array $allowedScopes = ['read'],
        public array $allowedIpAddresses = [],
        public int $rateLimit = 100,
        public int $dailyQuota = 10000,
        public int $usedToday = 0,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $lastUsedAt = null,
        public ?\DateTimeImmutable $expiresAt = null,
        public array $metadata = [],
    ) {}

    /**
     * Create new credentials for vendor.
     *
     * @param array<string> $scopes
     */
    public static function create(
        string $vendorId,
        VendorPortalTier $tier,
        array $scopes = ['read'],
        ?int $expiresInDays = null,
    ): self {
        $clientId = self::generateClientId($vendorId);
        $clientSecret = self::generateClientSecret();

        $rateLimit = $tier->getApiRateLimit();
        $dailyQuota = $rateLimit * 60 * 24; // Rate limit * minutes * hours

        return new self(
            vendorId: $vendorId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            tier: $tier,
            status: 'active',
            allowedScopes: $scopes,
            allowedIpAddresses: [],
            rateLimit: $rateLimit,
            dailyQuota: $dailyQuota,
            usedToday: 0,
            createdAt: new \DateTimeImmutable(),
            lastUsedAt: null,
            expiresAt: $expiresInDays !== null
                ? new \DateTimeImmutable("+{$expiresInDays} days")
                : null,
        );
    }

    /**
     * Create read-only credentials.
     */
    public static function readOnly(string $vendorId, VendorPortalTier $tier): self
    {
        return self::create(
            vendorId: $vendorId,
            tier: $tier,
            scopes: ['read', 'catalog'],
        );
    }

    /**
     * Create full-access credentials.
     */
    public static function fullAccess(string $vendorId, VendorPortalTier $tier): self
    {
        return self::create(
            vendorId: $vendorId,
            tier: $tier,
            scopes: ['read', 'write', 'catalog', 'orders', 'invoices', 'inventory'],
        );
    }

    private static function generateClientId(string $vendorId): string
    {
        return sprintf(
            'vnd_%s_%s',
            substr(md5($vendorId), 0, 8),
            bin2hex(random_bytes(8)),
        );
    }

    private static function generateClientSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expiresAt !== null && $this->expiresAt <= new \DateTimeImmutable()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->allowedScopes, true) || in_array('admin', $this->allowedScopes, true);
    }

    public function isIpAllowed(string $ipAddress): bool
    {
        // Empty whitelist means all IPs allowed
        if (empty($this->allowedIpAddresses)) {
            return true;
        }

        return in_array($ipAddress, $this->allowedIpAddresses, true);
    }

    public function canMakeRequest(): bool
    {
        return $this->isActive() && $this->usedToday < $this->dailyQuota;
    }

    public function getRemainingQuota(): int
    {
        return max(0, $this->dailyQuota - $this->usedToday);
    }

    public function getQuotaUsagePercent(): float
    {
        if ($this->dailyQuota === 0) {
            return 100.0;
        }

        return round(($this->usedToday / $this->dailyQuota) * 100, 2);
    }

    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now = new \DateTimeImmutable();

        if ($this->expiresAt <= $now) {
            return 0;
        }

        return (int) $now->diff($this->expiresAt)->days;
    }

    public function withIncrementedUsage(): self
    {
        return new self(
            vendorId: $this->vendorId,
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            tier: $this->tier,
            status: $this->status,
            allowedScopes: $this->allowedScopes,
            allowedIpAddresses: $this->allowedIpAddresses,
            rateLimit: $this->rateLimit,
            dailyQuota: $this->dailyQuota,
            usedToday: $this->usedToday + 1,
            createdAt: $this->createdAt,
            lastUsedAt: new \DateTimeImmutable(),
            expiresAt: $this->expiresAt,
            metadata: $this->metadata,
        );
    }

    public function withResetDailyUsage(): self
    {
        return new self(
            vendorId: $this->vendorId,
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            tier: $this->tier,
            status: $this->status,
            allowedScopes: $this->allowedScopes,
            allowedIpAddresses: $this->allowedIpAddresses,
            rateLimit: $this->rateLimit,
            dailyQuota: $this->dailyQuota,
            usedToday: 0,
            createdAt: $this->createdAt,
            lastUsedAt: $this->lastUsedAt,
            expiresAt: $this->expiresAt,
            metadata: $this->metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'client_id' => $this->clientId,
            // Never expose client_secret in toArray
            'tier' => $this->tier->value,
            'status' => $this->status,
            'allowed_scopes' => $this->allowedScopes,
            'allowed_ip_addresses' => $this->allowedIpAddresses,
            'rate_limit' => $this->rateLimit,
            'daily_quota' => $this->dailyQuota,
            'used_today' => $this->usedToday,
            'remaining_quota' => $this->getRemainingQuota(),
            'quota_usage_percent' => $this->getQuotaUsagePercent(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'last_used_at' => $this->lastUsedAt?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'is_active' => $this->isActive(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
        ];
    }
}
