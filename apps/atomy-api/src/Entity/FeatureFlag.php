<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;

/**
 * Application-level feature flag entity.
 *
 * Implements the Nexus FlagDefinitionInterface for integration
 * with the FeatureFlagManager service.
 */
#[ORM\Entity(repositoryClass: 'App\\Repository\\FeatureFlagRepository')]
#[ORM\Table(name: 'feature_flags')]
#[ORM\UniqueConstraint(name: 'unique_tenant_flag', columns: ['tenant_id', 'name'])]
#[ORM\Index(name: 'idx_feature_flags_tenant', columns: ['tenant_id'])]
#[ORM\Index(name: 'idx_feature_flags_enabled', columns: ['enabled'])]
final class FeatureFlag implements FlagDefinitionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    #[ORM\Column(type: 'string', length: 32)]
    private string $strategy = 'system_wide';

    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $value = null;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $override = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $createdBy = null;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $updatedBy = null;

    public function __construct(
        string $id,
        string $tenantId,
        string $name,
        bool $enabled = false,
        FlagStrategy $strategy = FlagStrategy::SYSTEM_WIDE,
        mixed $value = null,
        ?string $description = null,
        ?FlagOverride $override = null,
        array $metadata = [],
        ?string $createdBy = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->strategy = $strategy->value;
        $this->value = $value;
        $this->description = $description;
        $this->override = $override?->value;
        $this->metadata = $metadata;
        $this->createdBy = $createdBy;
        $this->updatedBy = $createdBy;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
        $this->touch();
    }

    public function getStrategy(): FlagStrategy
    {
        return FlagStrategy::from($this->strategy);
    }

    public function setStrategy(FlagStrategy $strategy): void
    {
        $this->strategy = $strategy->value;
        $this->touch();
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
        $this->touch();
    }

    public function getOverride(): ?FlagOverride
    {
        return $this->override !== null ? FlagOverride::from($this->override) : null;
    }

    public function setOverride(?FlagOverride $override): void
    {
        $this->override = $override?->value;
        $this->touch();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->touch();
    }

    public function getChecksum(): string
    {
        return hash('sha256', json_encode([
            'enabled' => $this->enabled,
            'strategy' => $this->strategy,
            'value' => $this->value,
            'override' => $this->override,
        ]));
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

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
        $this->touch();
    }

    /**
     * Update the timestamp on modifications.
     */
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
            'name' => $this->name,
            'description' => $this->description,
            'enabled' => $this->enabled,
            'strategy' => $this->strategy,
            'value' => $this->value,
            'override' => $this->override,
            'metadata' => $this->metadata,
            'checksum' => $this->getChecksum(),
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
        ];
    }
}
