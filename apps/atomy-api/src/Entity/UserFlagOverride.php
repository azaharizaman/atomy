<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\FeatureFlags\Enums\FlagOverride;

/**
 * User-level feature flag override.
 *
 * Allows individual users to have specific flag overrides
 * that take precedence over application-level settings.
 * This enables user-specific feature access in settings pages.
 */
#[ORM\Entity(repositoryClass: 'App\\Repository\\UserFlagOverrideRepository')]
#[ORM\Table(name: 'user_flag_overrides')]
#[ORM\UniqueConstraint(name: 'unique_user_flag', columns: ['user_id', 'flag_name'])]
#[ORM\Index(name: 'idx_user_flag_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_user_flag_tenant', columns: ['tenant_id'])]
final class UserFlagOverride
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 26)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $flagName;

    #[ORM\Column(type: 'string', length: 16)]
    private string $override;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $createdBy = null;

    public function __construct(
        string $id,
        string $tenantId,
        string $userId,
        string $flagName,
        FlagOverride $override,
        ?string $reason = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $createdBy = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->flagName = $flagName;
        $this->override = $override->value;
        $this->reason = $reason;
        $this->expiresAt = $expiresAt;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getFlagName(): string
    {
        return $this->flagName;
    }

    public function getOverride(): FlagOverride
    {
        return FlagOverride::from($this->override);
    }

    public function setOverride(FlagOverride $override): void
    {
        $this->override = $override->value;
        $this->touch();
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
        $this->touch();
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
        $this->touch();
    }

    /**
     * Check if this override has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if this override is still active (not expired).
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'flag_name' => $this->flagName,
            'override' => $this->override,
            'reason' => $this->reason,
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'is_active' => $this->isActive(),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'created_by' => $this->createdBy,
        ];
    }
}
