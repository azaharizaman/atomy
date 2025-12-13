<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Vendor portal session DTO.
 *
 * Manages vendor portal user session state.
 */
final readonly class VendorPortalSession
{
    /**
     * @param string $sessionId Unique session identifier
     * @param string $vendorId Associated vendor ID
     * @param string $userId Portal user ID
     * @param VendorPortalTier $tier Portal tier
     * @param string $userEmail User email
     * @param string $userName User display name
     * @param array<string> $permissions User permissions
     * @param string $status Session status
     * @param string $ipAddress Client IP address
     * @param string $userAgent Client user agent
     * @param \DateTimeImmutable $createdAt Session creation time
     * @param \DateTimeImmutable $lastActivityAt Last activity time
     * @param \DateTimeImmutable $expiresAt Session expiration time
     * @param array<string, mixed> $context Session context data
     */
    public function __construct(
        public string $sessionId,
        public string $vendorId,
        public string $userId,
        public VendorPortalTier $tier,
        public string $userEmail,
        public string $userName,
        public array $permissions = [],
        public string $status = 'active',
        public string $ipAddress = '',
        public string $userAgent = '',
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public \DateTimeImmutable $lastActivityAt = new \DateTimeImmutable(),
        public \DateTimeImmutable $expiresAt = new \DateTimeImmutable('+8 hours'),
        public array $context = [],
    ) {}

    /**
     * Create new session for vendor user.
     */
    public static function create(
        string $vendorId,
        string $userId,
        string $userEmail,
        string $userName,
        VendorPortalTier $tier,
        array $permissions,
        string $ipAddress,
        string $userAgent,
        int $sessionDurationHours = 8,
    ): self {
        $sessionId = self::generateSessionId();
        $now = new \DateTimeImmutable();

        return new self(
            sessionId: $sessionId,
            vendorId: $vendorId,
            userId: $userId,
            tier: $tier,
            userEmail: $userEmail,
            userName: $userName,
            permissions: $permissions,
            status: 'active',
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            createdAt: $now,
            lastActivityAt: $now,
            expiresAt: $now->modify("+{$sessionDurationHours} hours"),
        );
    }

    private static function generateSessionId(): string
    {
        return sprintf(
            'sess_%s_%s',
            date('YmdHis'),
            bin2hex(random_bytes(16)),
        );
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    public function hasPermission(string $permission): bool
    {
        // Admin has all permissions
        if (in_array('admin', $this->permissions, true)) {
            return true;
        }

        // Check for wildcard permissions
        $parts = explode('.', $permission);
        $wildcard = $parts[0] . '.*';

        if (in_array($wildcard, $this->permissions, true)) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }

    public function canAccess(string $resource): bool
    {
        return match ($resource) {
            'orders' => $this->hasPermission('orders.read') || $this->hasPermission('orders.write'),
            'invoices' => $this->hasPermission('invoices.read') || $this->hasPermission('invoices.write'),
            'catalog' => $this->hasPermission('catalog.read') || $this->hasPermission('catalog.write'),
            'inventory' => $this->hasPermission('inventory.read'),
            'reports' => $this->hasPermission('reports.read'),
            'settings' => $this->hasPermission('settings.read') || $this->hasPermission('settings.write'),
            'users' => $this->hasPermission('users.read') || $this->hasPermission('users.write'),
            default => false,
        };
    }

    public function getSessionDuration(): int
    {
        return (int) $this->createdAt->diff(new \DateTimeImmutable())->format('%i');
    }

    public function getIdleTime(): int
    {
        return (int) $this->lastActivityAt->diff(new \DateTimeImmutable())->format('%i');
    }

    public function getRemainingTime(): int
    {
        $now = new \DateTimeImmutable();

        if ($this->expiresAt <= $now) {
            return 0;
        }

        return (int) $now->diff($this->expiresAt)->format('%i');
    }

    public function withRefreshedActivity(): self
    {
        return new self(
            sessionId: $this->sessionId,
            vendorId: $this->vendorId,
            userId: $this->userId,
            tier: $this->tier,
            userEmail: $this->userEmail,
            userName: $this->userName,
            permissions: $this->permissions,
            status: $this->status,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            createdAt: $this->createdAt,
            lastActivityAt: new \DateTimeImmutable(),
            expiresAt: $this->expiresAt,
            context: $this->context,
        );
    }

    public function withExtendedExpiry(int $additionalHours = 2): self
    {
        return new self(
            sessionId: $this->sessionId,
            vendorId: $this->vendorId,
            userId: $this->userId,
            tier: $this->tier,
            userEmail: $this->userEmail,
            userName: $this->userName,
            permissions: $this->permissions,
            status: $this->status,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            createdAt: $this->createdAt,
            lastActivityAt: new \DateTimeImmutable(),
            expiresAt: (new \DateTimeImmutable())->modify("+{$additionalHours} hours"),
            context: $this->context,
        );
    }

    public function withTerminated(): self
    {
        return new self(
            sessionId: $this->sessionId,
            vendorId: $this->vendorId,
            userId: $this->userId,
            tier: $this->tier,
            userEmail: $this->userEmail,
            userName: $this->userName,
            permissions: $this->permissions,
            status: 'terminated',
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            createdAt: $this->createdAt,
            lastActivityAt: new \DateTimeImmutable(),
            expiresAt: $this->expiresAt,
            context: $this->context,
        );
    }

    public function withContext(string $key, mixed $value): self
    {
        $context = $this->context;
        $context[$key] = $value;

        return new self(
            sessionId: $this->sessionId,
            vendorId: $this->vendorId,
            userId: $this->userId,
            tier: $this->tier,
            userEmail: $this->userEmail,
            userName: $this->userName,
            permissions: $this->permissions,
            status: $this->status,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            createdAt: $this->createdAt,
            lastActivityAt: $this->lastActivityAt,
            expiresAt: $this->expiresAt,
            context: $context,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'vendor_id' => $this->vendorId,
            'user_id' => $this->userId,
            'tier' => $this->tier->value,
            'user_email' => $this->userEmail,
            'user_name' => $this->userName,
            'permissions' => $this->permissions,
            'status' => $this->status,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'last_activity_at' => $this->lastActivityAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'is_valid' => $this->isValid(),
            'session_duration_minutes' => $this->getSessionDuration(),
            'idle_time_minutes' => $this->getIdleTime(),
            'remaining_time_minutes' => $this->getRemainingTime(),
        ];
    }
}
