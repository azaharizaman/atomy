<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;
use Nexus\Assets\Enums\MaintenanceType;

/**
 * Maintenance Record Interface (Tier 2+)
 *
 * Tracks service records, repair costs, and downtime for TCO analysis.
 */
interface MaintenanceRecordInterface
{
    /**
     * Get unique identifier
     */
    public function getId(): string;

    /**
     * Get asset identifier
     */
    public function getAssetId(): string;

    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;

    /**
     * Get maintenance type
     */
    public function getType(): MaintenanceType;

    /**
     * Get maintenance description
     */
    public function getDescription(): string;

    /**
     * Get service cost
     */
    public function getCost(): float;

    /**
     * Get currency code
     */
    public function getCurrency(): string;

    /**
     * Get maintenance start time
     */
    public function getStartTime(): DateTimeInterface;

    /**
     * Get maintenance end time
     */
    public function getEndTime(): ?DateTimeInterface;

    /**
     * Get service provider/vendor ID
     */
    public function getServiceProviderId(): ?string;

    /**
     * Get service provider name
     */
    public function getServiceProviderName(): ?string;

    /**
     * Get performed by user ID
     */
    public function getPerformedBy(): ?string;

    /**
     * Get notes
     */
    public function getNotes(): ?string;

    /**
     * Get created timestamp
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Calculate downtime in hours
     */
    public function getDowntimeHours(): float;

    /**
     * Check if maintenance is completed
     */
    public function isCompleted(): bool;
}
