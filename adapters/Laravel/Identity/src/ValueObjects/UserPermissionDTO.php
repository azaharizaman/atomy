<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\ValueObjects;

use Nexus\Identity\Contracts\PermissionInterface;

final class UserPermissionDTO implements PermissionInterface
{
    private readonly string $resource;
    private readonly string $action;

    public function __construct(
        private readonly string $id,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->resource = $this->parseResource($id);
        $this->action = $this->parseAction($id);
    }

    private function parseResource(string $permissionId): string
    {
        $pos = strpos($permissionId, '.');

        return $pos !== false ? substr($permissionId, 0, $pos) : $permissionId;
    }

    private function parseAction(string $permissionId): string
    {
        $pos = strpos($permissionId, '.');

        return $pos !== false ? substr($permissionId, $pos + 1) : '*';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->id;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDescription(): ?string
    {
        return null;
    }

    public function isWildcard(): bool
    {
        return $this->action === '*';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        if ($this->createdAt === null) {
            throw new \RuntimeException('Permission created_at timestamp is not available');
        }

        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        if ($this->updatedAt === null) {
            throw new \RuntimeException('Permission updated_at timestamp is not available');
        }

        return $this->updatedAt;
    }

    public function matches(string $permissionName): bool
    {
        if ($this->id === $permissionName) {
            return true;
        }

        if ($this->isWildcard()) {
            $targetParts = explode('.', $permissionName, 2);

            return count($targetParts) === 2 && $targetParts[0] === $this->resource;
        }

        return false;
    }

    public static function fromDatabaseRow(object $row): self
    {
        return new self(
            id: (string) $row->permission_id,
            createdAt: isset($row->created_at) ? new \DateTimeImmutable($row->created_at) : null,
            updatedAt: isset($row->updated_at) ? new \DateTimeImmutable($row->updated_at) : null,
        );
    }
}
