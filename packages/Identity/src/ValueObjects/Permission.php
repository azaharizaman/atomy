<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * Permission value object
 * 
 * Represents a permission with resource and action
 */
final readonly class Permission
{
    /**
     * Create new permission
     */
    public function __construct(
        public string $name,
        public string $resource,
        public string $action
    ) {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Permission name cannot be empty');
        }

        if (empty($this->resource)) {
            throw new \InvalidArgumentException('Resource cannot be empty');
        }

        if (empty($this->action)) {
            throw new \InvalidArgumentException('Action cannot be empty');
        }
    }

    /**
     * Create from name (e.g., "users.create")
     */
    public static function fromName(string $name): self
    {
        $parts = explode('.', $name, 2);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid permission format: {$name}. Expected format: resource.action");
        }

        return new self(
            name: $name,
            resource: $parts[0],
            action: $parts[1]
        );
    }

    /**
     * Check if this is a wildcard permission
     */
    public function isWildcard(): bool
    {
        return $this->action === '*';
    }

    /**
     * Check if this permission matches another permission name
     */
    public function matches(string $permissionName): bool
    {
        if ($this->name === $permissionName) {
            return true;
        }

        if ($this->isWildcard()) {
            $targetParts = explode('.', $permissionName, 2);
            return count($targetParts) === 2 && $targetParts[0] === $this->resource;
        }

        return false;
    }

    /**
     * Convert to string
     */
    public function toString(): string
    {
        return $this->name;
    }

    /**
     * Convert to array
     * 
     * @return array{name: string, resource: string, action: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'resource' => $this->resource,
            'action' => $this->action,
        ];
    }
}
