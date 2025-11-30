<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Permission entity interface
 * 
 * Represents a permission that can be assigned to roles or users
 */
interface PermissionInterface
{
    /**
     * Get the unique identifier for the permission (ULID)
     */
    public function getId(): string;

    /**
     * Get the permission name (e.g., "users.create", "invoices.*")
     */
    public function getName(): string;

    /**
     * Get the resource this permission applies to (e.g., "users", "invoices")
     */
    public function getResource(): string;

    /**
     * Get the action this permission allows (e.g., "create", "update", "delete", "*")
     */
    public function getAction(): string;

    /**
     * Get the permission description
     */
    public function getDescription(): ?string;

    /**
     * Check if this is a wildcard permission (e.g., "users.*")
     */
    public function isWildcard(): bool;

    /**
     * Get when the permission was created
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get when the permission was last updated
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Check if this permission matches a given permission name
     * 
     * @param string $permissionName The permission to check against
     * @return bool True if this permission matches (considering wildcards)
     */
    public function matches(string $permissionName): bool;
}
