<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Tenant Entity Interface
 *
 * Defines the data structure and behavior contract for a tenant entity.
 * This interface must be implemented by the application layer's Eloquent model.
 *
 * @package Nexus\Tenant\Contracts
 */
interface TenantInterface
{
    /**
     * Get the tenant's unique identifier (ULID).
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the tenant's unique code (e.g., "ACME", "CORP01").
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the tenant's display name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the tenant's primary email address.
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Get the tenant's current status (pending, active, suspended, archived, trial).
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Get the tenant's primary domain (e.g., "acme.example.com").
     *
     * @return string|null
     */
    public function getDomain(): ?string;

    /**
     * Get the tenant's subdomain (e.g., "acme" from "acme.myapp.com").
     *
     * @return string|null
     */
    public function getSubdomain(): ?string;

    /**
     * Get the database name for this tenant (if using multi-database strategy).
     *
     * @return string|null
     */
    public function getDatabaseName(): ?string;

    /**
     * Get the tenant's timezone (e.g., "Asia/Kuala_Lumpur").
     *
     * @return string
     */
    public function getTimezone(): string;

    /**
     * Get the tenant's locale (e.g., "en_MY").
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Get the tenant's currency code (e.g., "MYR").
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Get the tenant's date format (e.g., "d/m/Y").
     *
     * @return string
     */
    public function getDateFormat(): string;

    /**
     * Get the tenant's time format (e.g., "H:i").
     *
     * @return string
     */
    public function getTimeFormat(): string;

    /**
     * Get the tenant's parent tenant ID (for parent-child relationships).
     *
     * @return string|null
     */
    public function getParentId(): ?string;

    /**
     * Get the tenant's metadata as associative array.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get a specific metadata value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed;

    /**
     * Check if tenant is active.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Check if tenant is suspended.
     *
     * @return bool
     */
    public function isSuspended(): bool;

    /**
     * Check if tenant is in trial period.
     *
     * @return bool
     */
    public function isTrial(): bool;

    /**
     * Check if tenant is archived.
     *
     * @return bool
     */
    public function isArchived(): bool;

    /**
     * Get the trial end date.
     *
     * @return \DateTimeInterface|null
     */
    public function getTrialEndsAt(): ?\DateTimeInterface;

    /**
     * Check if trial has expired.
     *
     * @return bool
     */
    public function isTrialExpired(): bool;

    /**
     * Get storage quota in bytes (null = unlimited).
     *
     * @return int|null
     */
    public function getStorageQuota(): ?int;

    /**
     * Get current storage used in bytes.
     *
     * @return int
     */
    public function getStorageUsed(): int;

    /**
     * Get max users allowed (null = unlimited).
     *
     * @return int|null
     */
    public function getMaxUsers(): ?int;

    /**
     * Get API rate limit per minute (null = no limit).
     *
     * @return int|null
     */
    public function getRateLimit(): ?int;

    /**
     * Check if read-only mode is enabled.
     *
     * @return bool
     */
    public function isReadOnly(): bool;

    /**
     * Get billing cycle start date.
     *
     * @return \DateTimeInterface|null
     */
    public function getBillingCycleStartDate(): ?\DateTimeInterface;

    /**
     * Get onboarding completion percentage (0-100).
     *
     * @return int
     */
    public function getOnboardingProgress(): int;

    /**
     * Get tenant creation timestamp.
     *
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get tenant last update timestamp.
     *
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Get tenant soft delete timestamp (if archived).
     *
     * @return \DateTimeInterface|null
     */
    public function getDeletedAt(): ?\DateTimeInterface;
}
