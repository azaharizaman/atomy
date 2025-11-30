<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Domain\Identity\Contracts\PermissionInterface;

#[ORM\Entity(repositoryClass: 'App\\Repository\\PermissionRepository')]
#[ORM\Table(name: 'permissions')]
class Permission implements PermissionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100)]
    private string $resource;

    #[ORM\Column(type: 'string', length: 100)]
    private string $action;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $name,
        string $resource,
        string $action,
        ?string $description = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->resource = $resource;
        $this->action = $action;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getResource(): string { return $this->resource; }
    public function getAction(): string { return $this->action; }
    public function getDescription(): ?string { return $this->description; }
    public function isWildcard(): bool { return str_ends_with($this->name, '.*') || $this->action === '*'; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function matches(string $permissionName): bool
    {
        if ($this->name === $permissionName) {
            return true;
        }

        // Handle wildcard matching (e.g., "users.*" matches "users.create")
        if ($this->isWildcard()) {
            $prefix = rtrim($this->name, '.*');
            return str_starts_with($permissionName, $prefix);
        }

        return false;
    }

    public function setName(string $name): void { $this->name = $name; $this->touch(); }
    public function setResource(string $resource): void { $this->resource = $resource; $this->touch(); }
    public function setAction(string $action): void { $this->action = $action; $this->touch(); }
    public function setDescription(?string $desc): void { $this->description = $desc; $this->touch(); }

    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
