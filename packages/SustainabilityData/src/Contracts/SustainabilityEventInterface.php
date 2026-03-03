<?php

declare(strict_types=1);

namespace Nexus\SustainabilityData\Contracts;

/**
 * Interface for raw sustainability events.
 * 
 * Captures atomic data points from IoT sensors, utility bills, or manual logs.
 */
interface SustainabilityEventInterface
{
    /**
     * Get the high-resolution timestamp of when the event occurred.
     */
    public function getOccurredAt(): \DateTimeImmutable;

    /**
     * Get the identifier of the data source (e.g., 'METER-001').
     */
    public function getSourceId(): string;

    /**
     * Get the numerical value of the reading.
     */
    public function getValue(): float;

    /**
     * Get the original unit of measure (e.g., 'kWh', 'm3').
     */
    public function getUnit(): string;

    /**
     * Get additional metadata (e.g., sensor health, signal strength).
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
