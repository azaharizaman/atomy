<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * Installed Module Entity.
 *
 * Represents a module that has been installed for a tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'installed_modules')]
#[UniqueEntity(fields: ['moduleId'], message: 'Module is already installed')]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/modules/{moduleId}/install',
            denormalizationContext: ['groups' => ['module:write']],
            security: 'is_granted("ROLE_ADMIN")'
        ),
        new Delete(
            uriTemplate: '/modules/{id}',
            security: 'is_granted("ROLE_ADMIN")'
        ),
    ],
    normalizationContext: ['groups' => ['module:read']],
    denormalizationContext: ['groups' => ['module:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['moduleId' => 'exact'])]
final class InstalledModule
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['module:read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['module:read', 'module:write'])]
    private string $moduleId;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['module:read'])]
    private \DateTimeImmutable $installedAt;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['module:read'])]
    private string $installedBy;

    #[ORM\Column(type: 'json')]
    #[Groups(['module:read', 'module:write'])]
    private array $metadata = [];

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $tenantId = null;

    public function __construct(
        string $moduleId,
        string $installedBy,
        array $metadata = []
    ) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->moduleId = $moduleId;
        $this->installedBy = $installedBy;
        $this->metadata = $metadata;
        $this->installedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getModuleId(): string
    {
        return $this->moduleId;
    }

    public function setModuleId(string $moduleId): void
    {
        $this->moduleId = $moduleId;
    }

    public function getInstalledAt(): \DateTimeImmutable
    {
        return $this->installedAt;
    }

    public function getInstalledBy(): string
    {
        return $this->installedBy;
    }

    public function setInstalledBy(string $installedBy): void
    {
        $this->installedBy = $installedBy;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(?string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }
}
