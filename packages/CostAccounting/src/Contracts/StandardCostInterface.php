<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

/**
 * Standard Cost Interface
 * 
 * Defines operations for standard cost management.
 */
interface StandardCostInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the product ID
     */
    public function getProductId(): string;

    /**
     * Get the cost center ID
     */
    public function getCostCenterId(): string;

    /**
     * Get the period ID
     */
    public function getPeriodId(): string;

    /**
     * Get the standard material cost
     */
    public function getStandardMaterialCost(): float;

    /**
     * Get the standard labor cost
     */
    public function getStandardLaborCost(): float;

    /**
     * Get the standard overhead cost
     */
    public function getStandardOverheadCost(): float;

    /**
     * Get the total standard cost
     */
    public function getTotalStandardCost(): float;

    /**
     * Get the standard quantity
     */
    public function getStandardQuantity(): float;

    /**
     * Get the standard unit cost
     */
    public function getStandardUnitCost(): float;

    /**
     * Get the currency
     */
    public function getCurrency(): string;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;

    /**
     * Check if this is a current (active) standard cost
     */
    public function isCurrent(): bool;

    /**
     * Get the effective date
     */
    public function getEffectiveDate(): \DateTimeImmutable;

    /**
     * Get the expiration date
     */
    public function getExpirationDate(): ?\DateTimeImmutable;
}
