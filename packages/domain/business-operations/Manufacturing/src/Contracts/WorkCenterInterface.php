<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Work Center entity.
 *
 * Represents a production resource where operations are performed.
 */
interface WorkCenterInterface
{
    /**
     * Get the unique identifier for this work center.
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the work center code.
     */
    public function getCode(): string;

    /**
     * Get the work center name.
     */
    public function getName(): string;

    /**
     * Get the work center description.
     */
    public function getDescription(): ?string;

    /**
     * Get the cost center ID for costing.
     */
    public function getCostCenterId(): ?string;

    /**
     * Get the standard hourly rate.
     */
    public function getHourlyRate(): float;

    /**
     * Get the overhead rate per hour.
     */
    public function getOverheadRate(): float;

    /**
     * Get the efficiency percentage (0-100+).
     */
    public function getEfficiency(): float;

    /**
     * Get the number of available machines/resources.
     */
    public function getCapacityUnits(): int;

    /**
     * Get available hours per day per capacity unit.
     */
    public function getHoursPerDay(): float;

    /**
     * Get the queue time default in minutes.
     */
    public function getDefaultQueueTime(): float;

    /**
     * Get the move time default in minutes.
     */
    public function getDefaultMoveTime(): float;

    /**
     * Check if work center is active.
     */
    public function isActive(): bool;

    /**
     * Check if work center supports finite scheduling.
     */
    public function isFiniteCapacity(): bool;

    /**
     * Get optional alternate work center ID.
     */
    public function getAlternateWorkCenterId(): ?string;
}
