<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts\Providers;

use Nexus\KycVerification\ValueObjects\AddressVerification;

/**
 * Interface for address verification provider.
 * 
 * This interface abstracts address verification services.
 * The orchestrator layer must implement this using address verification APIs
 * or Nexus\Geo integration.
 */
interface AddressVerificationProviderInterface
{
    /**
     * Verify an address
     * 
     * @param array<string, mixed> $addressData Address components
     */
    public function verify(array $addressData): AddressVerification;

    /**
     * Standardize an address format
     * 
     * @param array<string, mixed> $addressData Address components
     * @return array<string, mixed> Standardized address
     */
    public function standardize(array $addressData): array;

    /**
     * Geocode an address
     * 
     * @param array<string, mixed> $addressData Address components
     * @return array{latitude: float, longitude: float, accuracy: string}|null
     */
    public function geocode(array $addressData): ?array;

    /**
     * Validate postal code for country
     */
    public function validatePostalCode(string $postalCode, string $countryCode): bool;

    /**
     * Check if address is a known high-risk address type
     * 
     * @param array<string, mixed> $addressData Address components
     * @return array{is_high_risk: bool, risk_type: ?string, details: ?string}
     */
    public function checkHighRiskAddress(array $addressData): array;
}
