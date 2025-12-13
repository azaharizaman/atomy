<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

/**
 * Vendor address data DTO.
 */
final readonly class VendorAddressData
{
    /**
     * @param string $addressType Type of address (billing, shipping, registered, etc.)
     * @param string $addressLine1 Primary address line
     * @param string|null $addressLine2 Secondary address line
     * @param string $city City
     * @param string $stateProvince State or province
     * @param string $postalCode Postal/ZIP code
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param bool $isPrimary Whether this is the primary address
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $addressType,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $city,
        public string $stateProvince,
        public string $postalCode,
        public string $countryCode,
        public bool $isPrimary = false,
        public array $metadata = [],
    ) {}

    /**
     * Create billing address.
     */
    public static function billing(
        string $addressLine1,
        string $city,
        string $stateProvince,
        string $postalCode,
        string $countryCode,
        ?string $addressLine2 = null,
    ): self {
        return new self(
            addressType: 'billing',
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            city: $city,
            stateProvince: $stateProvince,
            postalCode: $postalCode,
            countryCode: $countryCode,
            isPrimary: true,
        );
    }

    /**
     * Create shipping address.
     */
    public static function shipping(
        string $addressLine1,
        string $city,
        string $stateProvince,
        string $postalCode,
        string $countryCode,
        ?string $addressLine2 = null,
    ): self {
        return new self(
            addressType: 'shipping',
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            city: $city,
            stateProvince: $stateProvince,
            postalCode: $postalCode,
            countryCode: $countryCode,
            isPrimary: false,
        );
    }

    /**
     * Create registered address.
     */
    public static function registered(
        string $addressLine1,
        string $city,
        string $stateProvince,
        string $postalCode,
        string $countryCode,
        ?string $addressLine2 = null,
    ): self {
        return new self(
            addressType: 'registered',
            addressLine1: $addressLine1,
            addressLine2: $addressLine2,
            city: $city,
            stateProvince: $stateProvince,
            postalCode: $postalCode,
            countryCode: $countryCode,
            isPrimary: false,
        );
    }

    public function getFullAddress(): string
    {
        $parts = [$this->addressLine1];

        if ($this->addressLine2 !== null) {
            $parts[] = $this->addressLine2;
        }

        $parts[] = $this->city;
        $parts[] = $this->stateProvince;
        $parts[] = $this->postalCode;
        $parts[] = $this->countryCode;

        return implode(', ', $parts);
    }

    public function isSameCountry(string $countryCode): bool
    {
        return $this->countryCode === $countryCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'address_type' => $this->addressType,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'city' => $this->city,
            'state_province' => $this->stateProvince,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
            'is_primary' => $this->isPrimary,
            'metadata' => $this->metadata,
        ];
    }
}
