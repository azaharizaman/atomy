<?php

declare(strict_types=1);

namespace Nexus\CRM\Contracts;

use Nexus\CRM\Enums\OpportunityStage;
use Nexus\CRM\ValueObjects\ForecastProbability;

/**
 * Opportunity Interface
 * 
 * Represents a sales opportunity in the CRM system.
 * An opportunity is a qualified lead with potential for conversion to a deal.
 * 
 * @package Nexus\CRM\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface OpportunityInterface
{
    /**
     * Get unique opportunity identifier
     */
    public function getId(): string;

    /**
     * Get tenant identifier for multi-tenancy
     */
    public function getTenantId(): string;

    /**
     * Get pipeline this opportunity belongs to
     */
    public function getPipelineId(): string;

    /**
     * Get opportunity title/name
     */
    public function getTitle(): string;

    /**
     * Get opportunity description
     */
    public function getDescription(): ?string;

    /**
     * Get current stage in the pipeline
     */
    public function getStage(): OpportunityStage;

    /**
     * Get deal value (in smallest currency unit)
     */
    public function getValue(): int;

    /**
     * Get currency code (ISO 4217)
     */
    public function getCurrency(): string;

    /**
     * Get expected close date
     */
    public function getExpectedCloseDate(): \DateTimeImmutable;

    /**
     * Get actual close date (if closed)
     */
    public function getActualCloseDate(): ?\DateTimeImmutable;

    /**
     * Get forecast probability based on stage
     */
    public function getForecastProbability(): ForecastProbability;

    /**
     * Get weighted value (value * probability)
     */
    public function getWeightedValue(): int;

    /**
     * Get ID of the lead this opportunity was converted from
     */
    public function getSourceLeadId(): ?string;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last modification timestamp
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Check if opportunity is open (not closed won/lost)
     */
    public function isOpen(): bool;

    /**
     * Check if opportunity is closed won
     */
    public function isWon(): bool;

    /**
     * Check if opportunity is closed lost
     */
    public function isLost(): bool;

    /**
     * Get number of days in current stage
     */
    public function getDaysInCurrentStage(): int;

    /**
     * Get total age of opportunity in days
     */
    public function getAgeInDays(): int;
}
