<?php

declare(strict_types=1);

namespace Nexus\Product\ValueObjects;

final readonly class Quantity
{
    public function __construct(
        public float $value,
        public string $unitCode,
    ) {}

    /** @return array{value:float,unit:string} */
    public function toArray(): array
    {
        return ['value' => $this->value, 'unit' => $this->unitCode];
    }

    /** @param array{value:float,unit:string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['value'], $data['unit']);
    }
}
