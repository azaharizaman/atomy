<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable address value object.
 * 
 * Represents physical address for customers, vendors, warehouses, etc.
 */
final readonly class Address implements Comparable, SerializableVO
{
    /**
     * @param string $street Street address
     * @param string $city City name
     * @param string $state State/province/region
     * @param string $postalCode Postal/ZIP code
     * @param string $country ISO 3166-1 alpha-2 country code (e.g., 'MY', 'US', 'GB')
     * @param string|null $street2 Optional second address line
     * @throws InvalidValueException
     */
    public function __construct(
        private string $street,
        private string $city,
        private string $state,
        private string $postalCode,
        private string $country,
        private ?string $street2 = null
    ) {
        if (empty(trim($street))) {
            throw new InvalidValueException('Street address is required');
        }

        if (empty(trim($city))) {
            throw new InvalidValueException('City is required');
        }

        if (strlen($country) !== 2) {
            throw new InvalidValueException('Country must be ISO 3166-1 alpha-2 code (2 characters)');
        }
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return strtoupper($this->country);
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->street2,
            $this->city,
            $this->state . ' ' . $this->postalCode,
            strtoupper($this->country),
        ]);

        return implode(', ', $parts);
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Address');
        }

        // Compare by full address string
        return strcmp($this->getFullAddress(), $other->getFullAddress());
    }

    public function equals(Comparable $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->street === $other->street
            && $this->street2 === $other->street2
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && strtoupper($this->country) === strtoupper($other->country);
    }

    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'street2' => $this->street2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->getCountry(),
        ];
    }

    public function toString(): string
    {
        return $this->getFullAddress();
    }

    public static function fromArray(array $data): static
    {
        return new self(
            street: $data['street'],
            city: $data['city'],
            state: $data['state'],
            postalCode: $data['postal_code'],
            country: $data['country'],
            street2: $data['street2'] ?? null
        );
    }
}
