<?php

declare(strict_types=1);

namespace Nexus\Product\Contracts;

use DateTimeImmutable;

/**
 * Attribute Set Interface
 *
 * Represents a configurable attribute (e.g., Color, Size, Material)
 * used for generating product variants.
 */
interface AttributeSetInterface
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
     * Get attribute code (unique within tenant)
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get attribute name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get attribute description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get possible values for this attribute
     * e.g., ['Red', 'Blue', 'Green'] for COLOR attribute
     *
     * @return array<string>
     */
    public function getValues(): array;

    /**
     * Get display order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Check if attribute is active
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
