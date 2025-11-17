<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * User entity interface
 * 
 * Represents a user in the system with authentication and authorization capabilities
 */
interface UserInterface
{
    /**
     * Get the unique identifier for the user (ULID)
     */
    public function getId(): string;

    /**
     * Get the user's email address
     */
    public function getEmail(): string;

    /**
     * Get the user's password hash
     */
    public function getPasswordHash(): string;

    /**
     * Get the user's status
     * 
     * @return string One of: active, inactive, suspended, locked, pending_activation
     */
    public function getStatus(): string;

    /**
     * Get the user's name
     */
    public function getName(): ?string;

    /**
     * Get when the user was created
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get when the user was last updated
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Get when the user's email was verified
     */
    public function getEmailVerifiedAt(): ?\DateTimeInterface;

    /**
     * Check if the user is active
     */
    public function isActive(): bool;

    /**
     * Check if the user is locked
     */
    public function isLocked(): bool;

    /**
     * Check if the user's email is verified
     */
    public function isEmailVerified(): bool;

    /**
     * Get the user's tenant identifier
     */
    public function getTenantId(): ?string;

    /**
     * Get when the password was last changed
     */
    public function getPasswordChangedAt(): ?\DateTimeInterface;

    /**
     * Check if the user has MFA enabled
     */
    public function hasMfaEnabled(): bool;

    /**
     * Get the user's metadata
     * 
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array;
}
