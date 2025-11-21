<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;
use Nexus\Assets\Enums\DepreciationMethod;

/**
 * Asset Category Interface
 *
 * Defines groupings for assets with default depreciation rules.
 */
interface AssetCategoryInterface
{
    /**
     * Get unique identifier
     */
    public function getId(): string;

    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;

    /**
     * Get category code (unique within tenant)
     */
    public function getCode(): string;

    /**
     * Get category name
     */
    public function getName(): string;

    /**
     * Get category description
     */
    public function getDescription(): ?string;

    /**
     * Get default useful life in years
     */
    public function getDefaultUsefulLife(): float;

    /**
     * Get default depreciation method
     */
    public function getDefaultMethod(): DepreciationMethod;

    /**
     * Get default salvage value percentage (0-100)
     */
    public function getDefaultSalvagePercent(): float;

    /**
     * Check if this category is depreciable (e.g., Land is not)
     */
    public function isDepreciable(): bool;

    /**
     * Get GL account code for asset (Tier 3)
     */
    public function getAssetAccountCode(): ?string;

    /**
     * Get GL account code for accumulated depreciation (Tier 3)
     */
    public function getAccumulatedDepreciationAccountCode(): ?string;

    /**
     * Get GL account code for depreciation expense (Tier 3)
     */
    public function getDepreciationExpenseAccountCode(): ?string;

    /**
     * Check if category is active
     */
    public function isActive(): bool;

    /**
     * Get created timestamp
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Get updated timestamp
     */
    public function getUpdatedAt(): DateTimeInterface;
}
