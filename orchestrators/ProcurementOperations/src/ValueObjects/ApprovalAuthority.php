<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\ValueObjects;

/**
 * Immutable value object representing a user's approval authority.
 *
 * Encapsulates the effective approval limits for a user based on
 * their roles, department, and any override configurations.
 */
final readonly class ApprovalAuthority
{
    /**
     * @param string $userId User identifier
     * @param array<string, int> $limits Document type => limit in cents
     * @param array<string> $roles User's roles that contributed to limits
     * @param string|null $departmentId Department context (if applicable)
     * @param bool $hasOverrides Whether user-specific overrides are applied
     * @param \DateTimeImmutable|null $effectiveFrom When this authority became effective
     * @param \DateTimeImmutable|null $effectiveUntil When this authority expires (null = indefinite)
     */
    public function __construct(
        public string $userId,
        public array $limits,
        public array $roles = [],
        public ?string $departmentId = null,
        public bool $hasOverrides = false,
        public ?\DateTimeImmutable $effectiveFrom = null,
        public ?\DateTimeImmutable $effectiveUntil = null,
    ) {}

    /**
     * Create authority with no approval limits (viewer only).
     */
    public static function noAuthority(string $userId): self
    {
        return new self(
            userId: $userId,
            limits: [],
            roles: [],
            departmentId: null,
            hasOverrides: false,
        );
    }

    /**
     * Create authority with unlimited approval for all document types.
     */
    public static function unlimited(string $userId, array $documentTypes, array $roles = []): self
    {
        $limits = [];
        foreach ($documentTypes as $type) {
            $limits[$type] = PHP_INT_MAX;
        }

        return new self(
            userId: $userId,
            limits: $limits,
            roles: $roles,
            hasOverrides: false,
        );
    }

    /**
     * Get the limit for a specific document type.
     */
    public function getLimitForType(string $documentType): int
    {
        return $this->limits[$documentType] ?? 0;
    }

    /**
     * Check if user has any approval authority.
     */
    public function hasAnyAuthority(): bool
    {
        return ! empty($this->limits);
    }

    /**
     * Check if user can approve a specific amount for a document type.
     */
    public function canApprove(string $documentType, int $amountCents): bool
    {
        $limit = $this->getLimitForType($documentType);

        return $limit >= $amountCents;
    }

    /**
     * Check if user has unlimited authority for a document type.
     */
    public function isUnlimited(string $documentType): bool
    {
        return $this->getLimitForType($documentType) === PHP_INT_MAX;
    }

    /**
     * Check if this authority is currently effective.
     */
    public function isEffective(?\DateTimeImmutable $at = null): bool
    {
        $at ??= new \DateTimeImmutable();

        if ($this->effectiveFrom !== null && $at < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveUntil !== null && $at > $this->effectiveUntil) {
            return false;
        }

        return true;
    }

    /**
     * Get the highest limit across all document types.
     */
    public function getHighestLimit(): int
    {
        if (empty($this->limits)) {
            return 0;
        }

        return max($this->limits);
    }

    /**
     * Get the document types this authority covers.
     *
     * @return array<string>
     */
    public function getCoveredDocumentTypes(): array
    {
        return array_keys($this->limits);
    }

    /**
     * Create a new authority with updated limit for a document type.
     */
    public function withLimit(string $documentType, int $limitCents): self
    {
        $limits = $this->limits;
        $limits[$documentType] = $limitCents;

        return new self(
            userId: $this->userId,
            limits: $limits,
            roles: $this->roles,
            departmentId: $this->departmentId,
            hasOverrides: true,
            effectiveFrom: $this->effectiveFrom,
            effectiveUntil: $this->effectiveUntil,
        );
    }

    /**
     * Compare with another authority.
     */
    public function equals(self $other): bool
    {
        return $this->userId === $other->userId
            && $this->limits === $other->limits
            && $this->departmentId === $other->departmentId;
    }

    /**
     * Serialize to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'limits' => $this->limits,
            'roles' => $this->roles,
            'department_id' => $this->departmentId,
            'has_overrides' => $this->hasOverrides,
            'effective_from' => $this->effectiveFrom?->format(\DATE_ATOM),
            'effective_until' => $this->effectiveUntil?->format(\DATE_ATOM),
        ];
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            limits: $data['limits'] ?? [],
            roles: $data['roles'] ?? [],
            departmentId: $data['department_id'] ?? null,
            hasOverrides: $data['has_overrides'] ?? false,
            effectiveFrom: isset($data['effective_from'])
                ? new \DateTimeImmutable($data['effective_from'])
                : null,
            effectiveUntil: isset($data['effective_until'])
                ? new \DateTimeImmutable($data['effective_until'])
                : null,
        );
    }
}
