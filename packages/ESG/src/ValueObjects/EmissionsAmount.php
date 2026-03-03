<?php

declare(strict_types=1);

namespace Nexus\ESG\ValueObjects;

use Nexus\Common\Contracts\SerializableVO;

/**
 * Immutable value object representing a carbon emissions amount.
 */
final readonly class EmissionsAmount implements SerializableVO
{
    private const UNIT_MAP = [
        'kg' => 0.001,
        'tonnes' => 1.0,
        'tco2e' => 1.0,
        'g' => 0.000001,
    ];

    /**
     * @param float $amount The raw numerical amount
     * @param string $unit The unit of measure (e.g., 'kg', 'tonnes')
     */
    public function __construct(
        public float $amount,
        public string $unit = 'tonnes'
    ) {
        if (!isset(self::UNIT_MAP[strtolower($unit)])) {
            throw new \InvalidArgumentException("Unsupported emissions unit: {$unit}");
        }
        if ($amount < 0) {
            throw new \InvalidArgumentException('Emissions amount cannot be negative');
        }
    }

    /**
     * Get the amount normalized to tonnes CO2e.
     */
    public function toTonnes(): float
    {
        return $this->amount * self::UNIT_MAP[strtolower($this->unit)];
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'unit' => $this->unit,
            'tonnes_co2e' => $this->toTonnes(),
        ];
    }

    public function toString(): string
    {
        return sprintf('%.4f %s', $this->amount, $this->unit);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromArray(array $data): static
    {
        return new self($data['amount'], $data['unit'] ?? 'tonnes');
    }
}
