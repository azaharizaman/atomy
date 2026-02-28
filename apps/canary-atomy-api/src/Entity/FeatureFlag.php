<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\FeatureFlagRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Feature Flag Entity.
 * 
 * Represents a feature flag definition in the system.
 */
#[ORM\Entity(repositoryClass: FeatureFlagRepository::class)]
#[ORM\Table(name: 'feature_flags')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['feature_flag:read']]),
        new Get(normalizationContext: ['groups' => ['feature_flag:read']]),
        new Post(denormalizationContext: ['groups' => ['feature_flag:write']], security: 'is_granted("ROLE_ADMIN")'),
        new Patch(denormalizationContext: ['groups' => ['feature_flag:write']], security: 'is_granted("ROLE_ADMIN")'),
        new Delete(security: 'is_granted("ROLE_ADMIN")'),
    ],
    normalizationContext: ['groups' => ['feature_flag:read']],
    denormalizationContext: ['groups' => ['feature_flag:write']],
    shortName: 'FeatureFlag'
)]
class FeatureFlag implements FlagDefinitionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    #[ApiProperty(identifier: false)]
    #[Groups(['feature_flag:read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    #[ORM\Id]
    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9_\.]{1,100}$/')]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private bool $enabled = false;

    #[ORM\Column(type: 'string', length: 20, enumType: FlagStrategy::class)]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private FlagStrategy $strategy = FlagStrategy::SYSTEM_WIDE;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private mixed $value = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: FlagOverride::class)]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private ?FlagOverride $override = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private array $metadata = [];

    #[ORM\Column(type: 'string', length: 64)]
    #[Groups(['feature_flag:read'])]
    private string $checksum = '';

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    #[Groups(['feature_flag:read', 'feature_flag:write'])]
    private ?string $tenantId = null;

    public function __construct(string $name)
    {
        $this->id = (new Ulid())->toBase32();
        $this->name = $name;
        $this->updateChecksum();
    }

    public function getName(): string { return $this->name; }
    public function isEnabled(): bool { return $this->enabled; }
    public function getStrategy(): FlagStrategy { return $this->strategy; }
    public function getValue(): mixed { return $this->value; }
    public function getOverride(): ?FlagOverride { return $this->override; }
    public function getMetadata(): array { return $this->metadata; }
    public function getChecksum(): string { return $this->checksum; }
    public function getTenantId(): ?string { return $this->tenantId; }

    public function setEnabled(bool $enabled): self { $this->enabled = $enabled; return $this; }
    public function setStrategy(FlagStrategy $strategy): self { $this->strategy = $strategy; return $this; }
    public function setValue(mixed $value): self { $this->value = $value; return $this; }
    public function setOverride(?FlagOverride $override): self { $this->override = $override; return $this; }
    public function setMetadata(array $metadata): self { $this->metadata = $metadata; return $this; }
    public function setTenantId(?string $tenantId): self { $this->tenantId = $tenantId; return $this; }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateChecksum(): void
    {
        $state = [
            'enabled' => $this->enabled,
            'strategy' => $this->strategy->value,
            'value' => $this->value,
            'override' => $this->override?->value,
        ];
        $this->checksum = hash('sha256', json_encode($state));
    }
}
