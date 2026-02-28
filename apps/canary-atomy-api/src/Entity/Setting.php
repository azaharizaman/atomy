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
use App\Repository\SettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Setting Entity.
 * 
 * Represents a key-value configuration setting in the system.
 */
#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\Table(name: 'settings')]
#[ORM\UniqueConstraint(name: 'unique_setting_per_tenant', columns: ['setting_key', 'tenant_id'])]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['setting:read']]),
        new Get(normalizationContext: ['groups' => ['setting:read']]),
        new Post(denormalizationContext: ['groups' => ['setting:write']], security: 'is_granted("ROLE_ADMIN")'),
        new Patch(denormalizationContext: ['groups' => ['setting:write']], security: 'is_granted("ROLE_ADMIN")'),
        new Delete(security: 'is_granted("ROLE_ADMIN")'),
    ],
    normalizationContext: ['groups' => ['setting:read']],
    denormalizationContext: ['groups' => ['setting:write']],
    shortName: 'Setting'
)]
class Setting
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    #[ApiProperty(identifier: false)]
    #[Groups(['setting:read'])]
    private string $id;

    #[ORM\Column(name: 'setting_key', type: 'string', length: 100)]
    #[ApiProperty(identifier: true)]
    #[Assert\NotBlank]
    #[Groups(['setting:read', 'setting:write'])]
    private string $key;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['setting:read', 'setting:write'])]
    private mixed $value = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['setting:read', 'setting:write'])]
    private string $type = 'string';

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['setting:read', 'setting:write'])]
    private string $scope = 'tenant';

    #[ORM\Column(type: 'boolean')]
    #[Groups(['setting:read', 'setting:write'])]
    private bool $isEncrypted = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['setting:read', 'setting:write'])]
    private bool $isReadOnly = false;

    #[ORM\Column(name: 'tenant_id', type: 'string', length: 26, nullable: true)]
    #[Groups(['setting:read', 'setting:write'])]
    private ?string $tenantId = null;

    public function __construct(string $key, mixed $value = null)
    {
        $this->id = (new Ulid())->toBase32();
        $this->key = $key;
        $this->value = $value;
        $this->type = gettype($value);
    }

    public function getId(): string { return $this->id; }
    public function getKey(): string { return $this->key; }
    public function getValue(): mixed { return $this->value; }
    public function getType(): string { return $this->type; }
    public function getScope(): string { return $this->scope; }
    public function isEncrypted(): bool { return $this->isEncrypted; }
    public function isReadOnly(): bool { return $this->isReadOnly; }
    public function getTenantId(): ?string { return $this->tenantId; }

    public function setValue(mixed $value): self { $this->value = $value; $this->type = gettype($value); return $this; }
    public function setScope(string $scope): self { $this->scope = $scope; return $this; }
    public function setEncrypted(bool $isEncrypted): self { $this->isEncrypted = $isEncrypted; return $this; }
    public function setReadOnly(bool $isReadOnly): self { $this->isReadOnly = $isReadOnly; return $this; }
    public function setTenantId(?string $tenantId): self { $this->tenantId = $tenantId; return $this; }
}
