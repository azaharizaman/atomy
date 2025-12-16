<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Contracts;

/**
 * Interface defining party data required for sanctions screening.
 * 
 * This interface defines the minimum data contract that the Sanctions package
 * needs from a Party entity. Consuming applications/orchestrators must provide
 * concrete implementation using their Party package (e.g., Nexus\Party).
 * 
 * This follows the atomic architecture principle:
 * - Sanctions package defines what it needs (this interface)
 * - Orchestrator/consumer provides concrete implementation
 * - No circular dependencies between atomic packages
 * - Sanctions package remains independently testable
 * 
 * Required Data for Screening:
 * - ID: Unique identifier
 * - Name: Full legal name or First + Last name
 * - Type: INDIVIDUAL or ORGANIZATION
 * - Optional: DOB, nationality, identification documents
 * 
 * @package Nexus\Sanctions\Contracts
 */
interface PartyInterface
{
    /**
     * Get unique party identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get party full name.
     *
     * For individuals: Full legal name or First + Last name
     * For organizations: Legal entity name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get party type.
     *
     * @return string 'INDIVIDUAL' or 'ORGANIZATION'
     */
    public function getType(): string;

    /**
     * Get date of birth (individuals only).
     *
     * Improves match accuracy by 40% according to sanctions screening studies.
     * Recommended for individual screening.
     *
     * @return \DateTimeImmutable|null
     */
    public function getDateOfBirth(): ?\DateTimeImmutable;

    /**
     * Get nationality/country of citizenship (individuals only).
     *
     * Enables jurisdiction-specific screening.
     * Multiple nationalities can be returned as comma-separated string.
     *
     * @return string|null ISO 3166-1 alpha-2 country code(s)
     */
    public function getNationality(): ?string;

    /**
     * Get country of incorporation/registration (organizations only).
     *
     * Enables jurisdiction-specific screening for organizations.
     *
     * @return string|null ISO 3166-1 alpha-2 country code
     */
    public function getCountryOfIncorporation(): ?string;

    /**
     * Get passport number (individuals only).
     *
     * Provides additional matching data point.
     * Format: Country code + passport number (e.g., "USA123456789")
     *
     * @return string|null
     */
    public function getPassportNumber(): ?string;

    /**
     * Get national ID number (individuals only).
     *
     * Provides additional matching data point.
     * Format: Country code + ID number (e.g., "MYS850101-01-1234")
     *
     * @return string|null
     */
    public function getNationalId(): ?string;

    /**
     * Get party address.
     *
     * Useful for additional matching and jurisdiction determination.
     *
     * @return string|null Full address
     */
    public function getAddress(): ?string;

    /**
     * Get party email address.
     *
     * May be used for notification purposes by screening systems.
     *
     * @return string|null
     */
    public function getEmailAddress(): ?string;

    /**
     * Get alternative names/aliases.
     *
     * Previous names, trade names, aliases for enhanced matching.
     * Important for detecting matches across name changes.
     *
     * @return array<string>
     */
    public function getAliases(): array;

    /**
     * Get risk rating if already assessed.
     *
     * Can be used to determine screening frequency.
     * Values: 'HIGH', 'MEDIUM', 'LOW'
     *
     * @return string|null
     */
    public function getRiskRating(): ?string;

    /**
     * Check if party is an individual.
     *
     * @return bool
     */
    public function isIndividual(): bool;

    /**
     * Check if party is an organization.
     *
     * @return bool
     */
    public function isOrganization(): bool;
}
