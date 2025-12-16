<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Contracts;

/**
 * Party interface for AML risk assessment
 * 
 * This interface defines the party data contract required by the AmlCompliance package.
 * The orchestrator layer (or consuming application) must provide an adapter that
 * implements this interface, bridging to the actual Nexus\Party package.
 * 
 * This ensures atomicity - AmlCompliance doesn't directly depend on Nexus\Party.
 */
interface PartyInterface
{
    /**
     * Get the unique party identifier
     */
    public function getId(): string;

    /**
     * Get party name (individual or organization)
     */
    public function getName(): string;

    /**
     * Get party type (individual, organization, etc.)
     */
    public function getType(): string;

    /**
     * Get country code (ISO 3166-1 alpha-2)
     * Used for jurisdiction risk assessment
     */
    public function getCountryCode(): string;

    /**
     * Get list of all associated country codes
     * Includes registration, operation, and beneficial owner jurisdictions
     * 
     * @return array<string>
     */
    public function getAssociatedCountryCodes(): array;

    /**
     * Get industry/business type code
     * Used for business type risk assessment
     * 
     * @return string|null Industry code (NAICS, SIC, or custom)
     */
    public function getIndustryCode(): ?string;

    /**
     * Check if party is a Politically Exposed Person (PEP)
     */
    public function isPep(): bool;

    /**
     * Get PEP level if applicable
     * 
     * @return int|null PEP level (1 = head of state, 2 = senior official, etc.)
     */
    public function getPepLevel(): ?int;

    /**
     * Get party creation/onboarding date
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get date of birth (for individuals) or incorporation date (for organizations)
     */
    public function getDateOfBirthOrIncorporation(): ?\DateTimeImmutable;

    /**
     * Get list of beneficial owners (for organizations)
     * 
     * @return array<array{name: string, ownership_percentage: float, country_code: string}>
     */
    public function getBeneficialOwners(): array;

    /**
     * Get additional identifiers (tax ID, registration number, passport, etc.)
     * 
     * @return array<string, string> Key-value pairs of identifier type and value
     */
    public function getIdentifiers(): array;

    /**
     * Get party metadata for risk assessment
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if party account is active
     */
    public function isActive(): bool;

    /**
     * Get last activity date
     */
    public function getLastActivityDate(): ?\DateTimeImmutable;
}
