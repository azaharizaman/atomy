<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

/**
 * Represents the verification result of an address.
 * 
 * @immutable
 */
final readonly class AddressVerification
{
    /**
     * @param array<string> $matchedFields
     * @param array<string> $mismatchedFields
     */
    public function __construct(
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public ?string $state,
        public string $postalCode,
        public string $country,
        public bool $isVerified,
        public float $confidenceScore,
        public \DateTimeImmutable $verifiedAt,
        public ?string $verificationSource = null,
        public array $matchedFields = [],
        public array $mismatchedFields = [],
        public ?string $standardizedAddress = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /**
     * Get full address as string
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->addressLine1,
            $this->addressLine2,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if address is within a country
     */
    public function isInCountry(string $countryCode): bool
    {
        return strtoupper($this->country) === strtoupper($countryCode);
    }

    /**
     * Check if geocoordinates are available
     */
    public function hasGeoCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get match percentage based on matched vs mismatched fields
     */
    public function getMatchPercentage(): float
    {
        $totalFields = count($this->matchedFields) + count($this->mismatchedFields);
        if ($totalFields === 0) {
            return $this->isVerified ? 100.0 : 0.0;
        }

        return (count($this->matchedFields) / $totalFields) * 100;
    }

    /**
     * Check if meets confidence threshold
     */
    public function meetsConfidenceThreshold(float $threshold = 0.85): bool
    {
        return $this->confidenceScore >= $threshold;
    }

    /**
     * Create verified address
     */
    public static function verified(
        string $addressLine1,
        ?string $addressLine2,
        string $city,
        ?string $state,
        string $postalCode,
        string $country,
        float $confidenceScore = 1.0,
        ?string $standardizedAddress = null,
    ): self {
        return new self(
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            city: $city,
            state: $state,
            postalCode: $postalCode,
            country: $country,
            isVerified: true,
            confidenceScore: $confidenceScore,
            verifiedAt: new \DateTimeImmutable(),
            standardizedAddress: $standardizedAddress,
        );
    }

    /**
     * Create unverified address
     * 
     * @param array<string> $mismatchedFields
     */
    public static function unverified(
        string $addressLine1,
        ?string $addressLine2,
        string $city,
        ?string $state,
        string $postalCode,
        string $country,
        array $mismatchedFields = [],
    ): self {
        return new self(
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            city: $city,
            state: $state,
            postalCode: $postalCode,
            country: $country,
            isVerified: false,
            confidenceScore: 0.0,
            verifiedAt: new \DateTimeImmutable(),
            mismatchedFields: $mismatchedFields,
        );
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'is_verified' => $this->isVerified,
            'confidence_score' => $this->confidenceScore,
            'verified_at' => $this->verifiedAt->format('c'),
            'verification_source' => $this->verificationSource,
            'matched_fields' => $this->matchedFields,
            'mismatched_fields' => $this->mismatchedFields,
            'standardized_address' => $this->standardizedAddress,
            'full_address' => $this->getFullAddress(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
