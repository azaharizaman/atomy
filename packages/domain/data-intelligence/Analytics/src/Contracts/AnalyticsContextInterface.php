<?php

declare(strict_types=1);

namespace Nexus\Analytics\Contracts;

/**
 * Interface for managing analytics context (user, tenant, etc.)
 */
interface AnalyticsContextInterface
{
    /**
     * Get current user ID
     */
    public function getUserId(): ?string;

    /**
     * Get current tenant ID
     */
    public function getTenantId(): ?string;

    /**
     * Get current user's roles
     *
     * @return array<int, string>
     */
    public function getUserRoles(): array;

    /**
     * Get additional context data
     *
     * @return array<string, mixed>
     */
    public function getContextData(): array;

    /**
     * Get the IP address of the requester
     */
    public function getIpAddress(): ?string;

    /**
     * Get the user agent
     */
    public function getUserAgent(): ?string;
}
