<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Role entity interface
 * 
 * Represents a role that can be assigned to users
 */
interface RoleInterface
{
    /**
     * Get the unique identifier for the role (ULID)
     */
    public function getId(): string;

    /**
     * Get the role's name
     */
    public function getName(): string;

    /**
     * Get the role's description
     */
    public function getDescription(): ?string;

    /**
     * Get the role's tenant identifier
     */
    public function getTenantId(): ?string;

    /**
     * Check if this is a system-defined role (cannot be deleted)
     */
    public function isSystemRole(): bool;

    /**
     * Check if this is the super admin role
     */
    public function isSuperAdmin(): bool;

    /**
     * Get the parent role ID (for hierarchical roles)
     */
    public function getParentRoleId(): ?string;

    /**
     * Get when the role was created
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get when the role was last updated
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Check if MFA is required for this role
     */
    public function requiresMfa(): bool;
}
