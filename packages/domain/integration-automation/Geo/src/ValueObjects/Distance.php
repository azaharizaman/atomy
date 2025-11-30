<?php

declare(strict_types=1);

namespace Nexus\Geo\ValueObjects;

/**
 * Immutable value object representing a distance measurement
 * 
 * Internally stored in meters for consistency
 */
final readonly class Distance implements \JsonSerializable
{
    private const METERS_TO_KM = 0.001;
    private const METERS_TO_MILES = 0.000621371;

    public function __construct(
        public float $meters
    ) {
    }

    /**
     * Create from kilometers
     */
    public static function fromKilometers(float $kilometers): self
    {
        return new self($kilometers / self::METERS_TO_KM);
    }

    /**
     * Create from miles
     */
    public static function fromMiles(float $miles): self
    {
        return new self($miles / self::METERS_TO_MILES);
    }

    /**
     * Get distance in kilometers
     */
    public function toKilometers(): float
    {
        return $this->meters * self::METERS_TO_KM;
    }

    /**
     * Get distance in miles
     */
    public function toMiles(): float
    {
        return $this->meters * self::METERS_TO_MILES;
    }

    /**
     * Add another distance
     */
    public function add(self $other): self
    {
        return new self($this->meters + $other->meters);
    }

    /**
     * Format distance with appropriate unit
     */
    public function format(string $unit = 'km', int $decimals = 2): string
    {
        return match ($unit) {
            'km' => number_format($this->toKilometers(), $decimals) . ' km',
            'mi' => number_format($this->toMiles(), $decimals) . ' mi',
            'm' => number_format($this->meters, $decimals) . ' m',
            default => throw new \InvalidArgumentException("Invalid unit: {$unit}")
        };
    }

    public function toArray(): array
    {
        return [
            'meters' => $this->meters,
            'kilometers' => $this->toKilometers(),
            'miles' => $this->toMiles(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->format('km');
    }

    /**
     * Create from array representation
     */
    public static function fromArray(array $data): self
    {
        return new self($data['meters']);
    }
}
