<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Domain\Identity\Contracts\RoleInterface;

#[ORM\Entity(repositoryClass: 'App\\Repository\\RoleRepository')]
#[ORM\Table(name: 'roles')]
#[ORM\UniqueConstraint(name: 'uniq_role_name_tenant', columns: ['name', 'tenant_id'])]
class Role implements RoleInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $tenantId = null;

    #[ORM\Column(type: 'boolean')]
    private bool $systemRole = false;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $parentRoleId = null;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresMfa = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Permission> */
    #[ORM\ManyToMany(targetEntity: Permission::class)]
    #[ORM\JoinTable(name: 'role_permissions')]
    private Collection $permissions;

    public function __construct(
        string $id,
        string $name,
        ?string $description = null,
        ?string $tenantId = null,
        bool $systemRole = false,
        ?string $parentRoleId = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->tenantId = $tenantId;
        $this->systemRole = $systemRole;
        $this->parentRoleId = $parentRoleId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getTenantId(): ?string { return $this->tenantId; }
    public function isSystemRole(): bool { return $this->systemRole; }
    public function isSuperAdmin(): bool { return $this->name === 'SUPER_ADMIN'; }
    public function getParentRoleId(): ?string { return $this->parentRoleId; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function requiresMfa(): bool { return $this->requiresMfa; }

    public function setName(string $name): void { $this->name = $name; $this->touch(); }
    public function setDescription(?string $desc): void { $this->description = $desc; $this->touch(); }
    public function setParentRoleId(?string $id): void { $this->parentRoleId = $id; $this->touch(); }
    public function setRequiresMfa(bool $v): void { $this->requiresMfa = $v; $this->touch(); }

    /** @return Collection<int, Permission> */
    public function getPermissions(): Collection { return $this->permissions; }

    public function addPermission(Permission $perm): void
    {
        if (!$this->permissions->contains($perm)) {
            $this->permissions->add($perm);
            $this->touch();
        }
    }

    public function removePermission(Permission $perm): void
    {
        $this->permissions->removeElement($perm);
        $this->touch();
    }

    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
