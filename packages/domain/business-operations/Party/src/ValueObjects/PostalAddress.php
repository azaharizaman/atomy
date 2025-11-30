<?php

declare(strict_types=1);

namespace Nexus\Party\ValueObjects;

use Nexus\Geo\ValueObjects\Coordinates;

/**
 * Postal Address value object.
 * 
 * Represents a physical address with optional geolocation coordinates.
 * Immutable to ensure data integrity.
 */
final readonly class PostalAddress
{
    /**
     * Create a new postal address.
     *
     * @param string $streetLine1 Primary street address line
     * @param string $city City or locality
     * @param string $postalCode Postal/ZIP code
     * @param string $country ISO 3166-1 alpha-3 country code (e.g., 'MYS', 'SGP', 'USA')
     * @param string|null $streetLine2 Secondary street address line (apartment, suite, etc.)
     * @param string|null $streetLine3 Additional address line
     * @param string|null $state State, province, or region
     * @param string|null $district District or county
     * @param Coordinates|null $coordinates Optional geolocation coordinates for routing/mapping
     * 
     * @throws \InvalidArgumentException If validation fails
     */
    public function __construct(
        public string $streetLine1,
        public string $city,
        public string $postalCode,
        public string $country,
        public ?string $streetLine2 = null,
        public ?string $streetLine3 = null,
        public ?string $state = null,
        public ?string $district = null,
        public ?Coordinates $coordinates = null
    ) {
        $this->validateStreetLine1($streetLine1);
        $this->validateCity($city);
        $this->validatePostalCode($postalCode, $country);
        $this->validateCountry($country);
    }
    
    /**
     * Validate street line 1 is not empty.
     */
    private function validateStreetLine1(string $streetLine1): void
    {
        $trimmed = trim($streetLine1);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Street address line 1 cannot be empty');
        }
        
        if (strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('Street address line 1 cannot exceed 255 characters');
        }
    }
    
    /**
     * Validate city is not empty.
     */
    private function validateCity(string $city): void
    {
        $trimmed = trim($city);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('City cannot be empty');
        }
        
        if (strlen($trimmed) > 100) {
            throw new \InvalidArgumentException('City cannot exceed 100 characters');
        }
    }
    
    /**
     * Validate postal code format based on country.
     */
    private function validatePostalCode(string $postalCode, string $country): void
    {
        $trimmed = trim($postalCode);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Postal code cannot be empty');
        }
        
        // Country-specific postal code validation
        $pattern = $this->getPostalCodePattern($country);
        if ($pattern && !preg_match($pattern, $trimmed)) {
            throw new \InvalidArgumentException(
                "Invalid postal code format for country {$country}: {$trimmed}"
            );
        }
    }
    
    /**
     * Get postal code validation pattern for a country.
     */
    private function getPostalCodePattern(string $country): ?string
    {
        return match($country) {
            'MYS' => '/^\d{5}$/',                        // Malaysia: 50000
            'SGP' => '/^\d{6}$/',                        // Singapore: 123456
            'USA' => '/^\d{5}(-\d{4})?$/',               // USA: 12345 or 12345-6789
            'GBR' => '/^[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}$/i', // UK: SW1A 1AA
            'CAN' => '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i',   // Canada: K1A 0B1
            'AUS' => '/^\d{4}$/',                        // Australia: 2000
            'IND' => '/^\d{6}$/',                        // India: 110001
            'CHN' => '/^\d{6}$/',                        // China: 100000
            'JPN' => '/^\d{3}-\d{4}$/',                  // Japan: 100-0001
            default => null,                             // No validation for unknown countries
        };
    }
    
    /**
     * Validate country code format.
     */
    private function validateCountry(string $country): void
    {
        if (!preg_match('/^[A-Z]{3}$/', $country)) {
            throw new \InvalidArgumentException(
                "Invalid country code format. Expected ISO 3166-1 alpha-3 (e.g., 'MYS'), got: {$country}"
            );
        }
    }
    
    /**
     * Get full street address (all lines combined).
     */
    public function getFullStreet(): string
    {
        $lines = array_filter([
            $this->streetLine1,
            $this->streetLine2,
            $this->streetLine3,
        ]);
        
        return implode(', ', $lines);
    }
    
    /**
     * Get formatted single-line address.
     */
    public function formatOneLine(): string
    {
        $parts = [
            $this->getFullStreet(),
            $this->city,
        ];
        
        if ($this->state) {
            $parts[] = $this->state;
        }
        
        $parts[] = $this->postalCode;
        $parts[] = $this->country;
        
        return implode(', ', $parts);
    }
    
    /**
     * Get formatted multi-line address.
     * 
     * @return array<string>
     */
    public function formatMultiLine(): array
    {
        $lines = [];
        
        if ($this->streetLine1) {
            $lines[] = $this->streetLine1;
        }
        if ($this->streetLine2) {
            $lines[] = $this->streetLine2;
        }
        if ($this->streetLine3) {
            $lines[] = $this->streetLine3;
        }
        
        $cityLine = $this->city;
        if ($this->district) {
            $cityLine .= ', ' . $this->district;
        }
        $lines[] = $cityLine;
        
        if ($this->state) {
            $lines[] = $this->state . ' ' . $this->postalCode;
        } else {
            $lines[] = $this->postalCode;
        }
        
        $lines[] = $this->country;
        
        return $lines;
    }
    
    /**
     * Check if address has geolocation coordinates
     */
    public function hasCoordinates(): bool
    {
        return $this->coordinates !== null;
    }

    /**
     * Create a new instance with coordinates
     */
    public function withCoordinates(Coordinates $coordinates): self
    {
        return new self(
            streetLine1: $this->streetLine1,
            city: $this->city,
            postalCode: $this->postalCode,
            country: $this->country,
            streetLine2: $this->streetLine2,
            streetLine3: $this->streetLine3,
            state: $this->state,
            district: $this->district,
            coordinates: $coordinates
        );
    }

    /**
     * Convert to array representation.
     * 
     * @return array<string, string|array|null>
     */
    public function toArray(): array
    {
        return [
            'street_line_1' => $this->streetLine1,
            'street_line_2' => $this->streetLine2,
            'street_line_3' => $this->streetLine3,
            'city' => $this->city,
            'district' => $this->district,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'coordinates' => $this->coordinates?->toArray(),
        ];
    }
}
