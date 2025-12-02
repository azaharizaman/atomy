<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable phone number value object with E.164 format validation.
 */
final readonly class PhoneNumber implements Comparable, SerializableVO
{
    private string $value;

    /**
     * @param string $value Phone number (preferably in E.164 format: +60123456789)
     * @throws InvalidValueException
     */
    public function __construct(string $value)
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $value);

        if (empty($cleaned)) {
            throw new InvalidValueException("Invalid phone number: {$value}");
        }

        // Basic validation: must start with + for international or be at least 7 digits
        if (!str_starts_with($cleaned, '+') && strlen($cleaned) < 7) {
            throw new InvalidValueException("Phone number must be at least 7 digits: {$value}");
        }

        $this->value = $cleaned;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCountryCode(): ?string
    {
        if (!str_starts_with($this->value, '+')) {
            return null;
        }

        // Extract country code (1-3 digits after +)
        preg_match('/^\+(\d{1,3})/', $this->value, $matches);
        return $matches[1] ?? null;
    }

    public function format(): string
    {
        // Simple formatting: +60 12-345 6789
        if (str_starts_with($this->value, '+')) {
            $withoutPlus = substr($this->value, 1);
            $countryCode = $this->getCountryCode();
            $remaining = substr($withoutPlus, strlen($countryCode));

            if (strlen($remaining) >= 7) {
                return '+' . $countryCode . ' ' . substr($remaining, 0, 2) . '-' . 
                       substr($remaining, 2, 3) . ' ' . substr($remaining, 5);
            }
        }

        return $this->value;
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another PhoneNumber');
        }

        return strcmp($this->value, $other->value);
    }

    public function equals(Comparable $other): bool
    {
        return $this->compareTo($other) === 0;
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
            'value' => $this->value,
            'formatted' => $this->format(),
            'country_code' => $this->getCountryCode(),
        ];
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromArray(array $data): static
    {
        return new self($data['value']);
    }
}
