<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\ValueObjects\ApprovalThreshold;

/**
 * Configuration DTO for approval limits.
 *
 * Contains the complete approval limit configuration for a tenant,
 * including default limits, role-based limits, department limits,
 * and user-specific overrides.
 *
 * All monetary amounts are stored in cents to avoid floating-point issues.
 */
final readonly class ApprovalLimitConfig
{
    /**
     * @param array<string, int> $defaultLimits Document type => limit in cents
     * @param array<string, array<string, int>> $roleLimits Role ID => [Document type => limit in cents]
     * @param array<string, array<string, int>> $departmentLimits Department ID => [Document type => limit in cents]
     * @param array<string, array<string, int>> $userOverrides User ID => [Document type => limit in cents]
     * @param array<ApprovalThreshold> $thresholds Predefined threshold definitions
     * @param \DateTimeImmutable|null $lastModifiedAt Last modification timestamp
     * @param string|null $lastModifiedBy User who last modified
     */
    public function __construct(
        public array $defaultLimits = [],
        public array $roleLimits = [],
        public array $departmentLimits = [],
        public array $userOverrides = [],
        public array $thresholds = [],
        public ?\DateTimeImmutable $lastModifiedAt = null,
        public ?string $lastModifiedBy = null,
    ) {}

    /**
     * Create configuration with default values.
     */
    public static function createDefault(): self
    {
        return new self(
            defaultLimits: [
                'requisition' => 500000,     // $5,000
                'purchase_order' => 1000000, // $10,000
                'payment' => 2500000,        // $25,000
                'vendor_invoice' => 1000000, // $10,000
            ],
            roleLimits: [
                'department_manager' => [
                    'requisition' => 2500000,     // $25,000
                    'purchase_order' => 5000000,  // $50,000
                    'payment' => 10000000,        // $100,000
                    'vendor_invoice' => 5000000,  // $50,000
                ],
                'finance_director' => [
                    'requisition' => 10000000,    // $100,000
                    'purchase_order' => 25000000, // $250,000
                    'payment' => 50000000,        // $500,000
                    'vendor_invoice' => 25000000, // $250,000
                ],
                'cfo' => [
                    'requisition' => PHP_INT_MAX,     // Unlimited
                    'purchase_order' => PHP_INT_MAX,  // Unlimited
                    'payment' => PHP_INT_MAX,         // Unlimited
                    'vendor_invoice' => PHP_INT_MAX,  // Unlimited
                ],
            ],
            thresholds: [
                ApprovalThreshold::create('level_1', 500000, 'Direct Manager'),
                ApprovalThreshold::create('level_2', 2500000, 'Department Head'),
                ApprovalThreshold::create('level_3', 10000000, 'Finance Director'),
                ApprovalThreshold::create('level_4', PHP_INT_MAX, 'CFO/Board'),
            ],
        );
    }

    /**
     * Get the limit for a specific document type from defaults.
     */
    public function getDefaultLimit(string $documentType): int
    {
        return $this->defaultLimits[$documentType] ?? 0;
    }

    /**
     * Get the limit for a role and document type.
     */
    public function getRoleLimit(string $roleId, string $documentType): ?int
    {
        return $this->roleLimits[$roleId][$documentType] ?? null;
    }

    /**
     * Get the limit for a department and document type.
     */
    public function getDepartmentLimit(string $departmentId, string $documentType): ?int
    {
        return $this->departmentLimits[$departmentId][$documentType] ?? null;
    }

    /**
     * Get the user override limit for a document type.
     */
    public function getUserOverrideLimit(string $userId, string $documentType): ?int
    {
        return $this->userOverrides[$userId][$documentType] ?? null;
    }

    /**
     * Create a new config with updated default limits.
     */
    public function withDefaultLimits(array $limits): self
    {
        return new self(
            defaultLimits: $limits,
            roleLimits: $this->roleLimits,
            departmentLimits: $this->departmentLimits,
            userOverrides: $this->userOverrides,
            thresholds: $this->thresholds,
            lastModifiedAt: new \DateTimeImmutable(),
            lastModifiedBy: $this->lastModifiedBy,
        );
    }

    /**
     * Create a new config with updated role limits.
     */
    public function withRoleLimits(string $roleId, array $limits): self
    {
        $roleLimits = $this->roleLimits;
        $roleLimits[$roleId] = $limits;

        return new self(
            defaultLimits: $this->defaultLimits,
            roleLimits: $roleLimits,
            departmentLimits: $this->departmentLimits,
            userOverrides: $this->userOverrides,
            thresholds: $this->thresholds,
            lastModifiedAt: new \DateTimeImmutable(),
            lastModifiedBy: $this->lastModifiedBy,
        );
    }

    /**
     * Create a new config with updated department limits.
     */
    public function withDepartmentLimits(string $departmentId, array $limits): self
    {
        $departmentLimits = $this->departmentLimits;
        $departmentLimits[$departmentId] = $limits;

        return new self(
            defaultLimits: $this->defaultLimits,
            roleLimits: $this->roleLimits,
            departmentLimits: $departmentLimits,
            userOverrides: $this->userOverrides,
            thresholds: $this->thresholds,
            lastModifiedAt: new \DateTimeImmutable(),
            lastModifiedBy: $this->lastModifiedBy,
        );
    }

    /**
     * Create a new config with updated user overrides.
     */
    public function withUserOverrides(string $userId, ?array $limits): self
    {
        $userOverrides = $this->userOverrides;

        if ($limits === null) {
            unset($userOverrides[$userId]);
        } else {
            $userOverrides[$userId] = $limits;
        }

        return new self(
            defaultLimits: $this->defaultLimits,
            roleLimits: $this->roleLimits,
            departmentLimits: $this->departmentLimits,
            userOverrides: $userOverrides,
            thresholds: $this->thresholds,
            lastModifiedAt: new \DateTimeImmutable(),
            lastModifiedBy: $this->lastModifiedBy,
        );
    }

    /**
     * Serialize to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default_limits' => $this->defaultLimits,
            'role_limits' => $this->roleLimits,
            'department_limits' => $this->departmentLimits,
            'user_overrides' => $this->userOverrides,
            'thresholds' => array_map(
                fn(ApprovalThreshold $t) => $t->toArray(),
                $this->thresholds
            ),
            'last_modified_at' => $this->lastModifiedAt?->format(\DATE_ATOM),
            'last_modified_by' => $this->lastModifiedBy,
        ];
    }

    /**
     * Create from array (deserialization).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $thresholds = array_map(
            fn(array $t) => ApprovalThreshold::fromArray($t),
            $data['thresholds'] ?? []
        );

        return new self(
            defaultLimits: $data['default_limits'] ?? [],
            roleLimits: $data['role_limits'] ?? [],
            departmentLimits: $data['department_limits'] ?? [],
            userOverrides: $data['user_overrides'] ?? [],
            thresholds: $thresholds,
            lastModifiedAt: isset($data['last_modified_at'])
                ? new \DateTimeImmutable($data['last_modified_at'])
                : null,
            lastModifiedBy: $data['last_modified_by'] ?? null,
        );
    }
}
