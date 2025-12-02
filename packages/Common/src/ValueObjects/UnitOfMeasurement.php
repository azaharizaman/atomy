<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Enumable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Unit of measurement enumeration.
 * 
 * Represents standard measurement units with conversion factors.
 */
final readonly class UnitOfMeasurement implements Enumable, SerializableVO
{
    // Mass units
    public const KG = 'kg';
    public const G = 'g';
    public const LB = 'lb';
    public const OZ = 'oz';

    // Length units
    public const M = 'm';
    public const CM = 'cm';
    public const MM = 'mm';
    public const FT = 'ft';
    public const IN = 'in';

    // Volume units
    public const L = 'L';
    public const ML = 'mL';
    public const GAL = 'gal';

    // Quantity units
    public const PCS = 'pcs';
    public const BOX = 'box';
    public const DOZEN = 'dozen';
    public const PACK = 'pack';

    private const UNIT_LABELS = [
        self::KG => 'Kilogram',
        self::G => 'Gram',
        self::LB => 'Pound',
        self::OZ => 'Ounce',
        self::M => 'Meter',
        self::CM => 'Centimeter',
        self::MM => 'Millimeter',
        self::FT => 'Foot',
        self::IN => 'Inch',
        self::L => 'Liter',
        self::ML => 'Milliliter',
        self::GAL => 'Gallon',
        self::PCS => 'Pieces',
        self::BOX => 'Box',
        self::DOZEN => 'Dozen',
        self::PACK => 'Pack',
    ];

    private const UNIT_CATEGORIES = [
        'mass' => [self::KG, self::G, self::LB, self::OZ],
        'length' => [self::M, self::CM, self::MM, self::FT, self::IN],
        'volume' => [self::L, self::ML, self::GAL],
        'quantity' => [self::PCS, self::BOX, self::DOZEN, self::PACK],
    ];

    /**
     * @throws InvalidValueException
     */
    public function __construct(
        private string $value
    ) {
        if (!$this->isValid($value)) {
            throw new InvalidValueException("Invalid unit of measurement: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return self::UNIT_LABELS[$this->value];
    }

    public function getCategory(): string
    {
        foreach (self::UNIT_CATEGORIES as $category => $units) {
            if (in_array($this->value, $units, true)) {
                return $category;
            }
        }

        return 'unknown';
    }

    public function canConvertTo(UnitOfMeasurement $other): bool
    {
        return $this->getCategory() === $other->getCategory();
    }

    // Enumable implementation
    public static function values(): array
    {
        return array_keys(self::UNIT_LABELS);
    }

    public static function isValid(string $value): bool
    {
        return isset(self::UNIT_LABELS[$value]);
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
            'category' => $this->getCategory(),
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
