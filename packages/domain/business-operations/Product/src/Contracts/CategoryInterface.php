<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

use DateTimeImmutable;

/**
 * Category Interface
 *
 * Represents a product classification category with hierarchical support.
 * Uses adjacency list pattern for unlimited nesting.
 */
interface CategoryInterface
{
    /**
     * Get unique identifier
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get tenant identifier
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Get category code (unique within tenant)
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get category name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get category description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get parent category ID (null for root categories)
     *
     * @return string|null
     */
    public function getParentId(): ?string;

    /**
     * Get display order within parent
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Check if category is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get creation timestamp
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get last update timestamp
     *
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
