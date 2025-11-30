<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Nexus\Domain\Identity\Contracts\PermissionInterface;

/**
 * Permission Value Object implementing Nexus PermissionInterface
 *
 * Maps Laravel permission model/data to domain contract
 */
final readonly class PermissionVO implements PermissionInterface
{
    public function __construct(
        private string $id,
        private string $name,
        private string $resource,
        private string $action,
        private ?string $description,
        private \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
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
        return $this->description;
    }

    public function isWildcard(): bool
    {
        return str_contains($this->name, '*') || $this->action === '*';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function matches(string $permissionName): bool
    {
        // Exact match
        if ($this->name === $permissionName) {
            return true;
        }

        // Wildcard matching
        if ($this->isWildcard()) {
            $pattern = str_replace('*', '.*', preg_quote($this->name, '/'));
            return (bool) preg_match('/^' . $pattern . '$/', $permissionName);
        }

        return false;
    }

    /**
     * Factory method to create PermissionVO from array data
     */
    public static function fromArray(array $data): self
    {
        // Parse resource and action from permission name (e.g., "users.create")
        $parts = explode('.', $data['name'], 2);
        $resource = $parts[0] ?? '';
        $action = $parts[1] ?? '*';

        return new self(
            id: $data['id'],
            name: $data['name'],
            resource: $data['resource'] ?? $resource,
            action: $data['action'] ?? $action,
            description: $data['description'] ?? null,
            createdAt: $data['created_at'] instanceof \DateTimeInterface 
                ? $data['created_at'] 
                : new \DateTimeImmutable($data['created_at'] ?? 'now'),
            updatedAt: $data['updated_at'] instanceof \DateTimeInterface 
                ? $data['updated_at'] 
                : new \DateTimeImmutable($data['updated_at'] ?? 'now'),
        );
    }
}
