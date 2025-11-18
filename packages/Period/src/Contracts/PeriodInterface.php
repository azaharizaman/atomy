<?php

declare(strict_types=1);

namespace Nexus\Period\Contracts;

use DateTimeImmutable;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;

/**
 * Period Entity Interface
 * 
 * Represents a fiscal period for a specific business process type.
 */
interface PeriodInterface
{
    /**
     * Get the unique identifier for this period
     */
    public function getId(): string;

    /**
     * Get the period type (Accounting, Inventory, etc.)
     */
    public function getType(): PeriodType;

    /**
     * Get the current status of the period
     */
    public function getStatus(): PeriodStatus;

    /**
     * Get the period start date
     */
    public function getStartDate(): DateTimeImmutable;

    /**
     * Get the period end date
     */
    public function getEndDate(): DateTimeImmutable;

    /**
     * Get the fiscal year this period belongs to
     */
    public function getFiscalYear(): string;

    /**
     * Get the period name (e.g., "JAN-2024", "2024-Q1")
     */
    public function getName(): string;

    /**
     * Get the period description
     */
    public function getDescription(): ?string;

    /**
     * Check if a specific date falls within this period
     */
    public function containsDate(DateTimeImmutable $date): bool;

    /**
     * Check if posting is allowed to this period
     */
    public function isPostingAllowed(): bool;

    /**
     * Get when this period was created
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get when this period was last updated
     */
    public function getUpdatedAt(): DateTimeImmutable;
}
