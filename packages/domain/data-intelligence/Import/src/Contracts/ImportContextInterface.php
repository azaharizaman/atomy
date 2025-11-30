<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

/**
 * Import execution context contract
 * 
 * Provides execution context for import operations.
 */
interface ImportContextInterface
{
    /**
     * Get current user identifier
     */
    public function getUserId(): string;

    /**
     * Get current tenant identifier
     */
    public function getTenantId(): ?string;

    /**
     * Get execution timestamp
     */
    public function getExecutionTime(): \DateTimeImmutable;

    /**
     * Get additional context metadata
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if context is for system import (no user)
     */
    public function isSystemContext(): bool;
}
