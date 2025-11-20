<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

use DateTimeImmutable;

/**
 * Product Template Interface
 *
 * Represents the conceptual product (e.g., "T-Shirt Model X").
 * Holds shared attributes across all variants.
 * Optional - simple products can be standalone variants.
 */
interface ProductTemplateInterface
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
     * Get template code (unique within tenant)
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get template name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get template description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get category code
     *
     * @return string|null
     */
    public function getCategoryCode(): ?string;

    /**
     * Check if template is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get additional metadata
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

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
