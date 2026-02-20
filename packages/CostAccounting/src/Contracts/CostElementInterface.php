<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Enums\CostElementType;

/**
 * Cost Element Interface
 * 
 * Defines operations for cost element management.
 */
interface CostElementInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the element code
     */
    public function getCode(): string;

    /**
     * Get the element name
     */
    public function getName(): string;

    /**
     * Get the element type
     */
    public function getType(): CostElementType;

    /**
     * Get the cost center ID
     */
    public function getCostCenterId(): string;

    /**
     * Get the GL account ID
     */
    public function getGlAccountId(): ?string;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;

    /**
     * Check if element is active
     */
    public function isActive(): bool;

    /**
     * Check if this is a material element
     */
    public function isMaterial(): bool;

    /**
     * Check if this is a labor element
     */
    public function isLabor(): bool;

    /**
     * Check if this is an overhead element
     */
    public function isOverhead(): bool;
}
