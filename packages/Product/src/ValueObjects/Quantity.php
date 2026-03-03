<?php

declare(strict_types=1);

namespace Nexus\Product\ValueObjects;

final readonly class Quantity
{
    public function __construct(
        public float $value,
        public string $unitCode,
    ) {
        if (!is_finite($value)) {
            throw new \InvalidArgumentException('Quantity value must be a finite number.');
        }

        if (trim($unitCode) === '') {
            throw new \InvalidArgumentException('Quantity unitCode must be a non-empty string.');
        }
    }

    /** @return array{value:float,unit:string} */
    public function toArray(): array
    {
        return ['value' => $this->value, 'unit' => $this->unitCode];
    }

    /** @param array{value:float,unit:string} $data */
    public static function fromArray(array $data): self
    {
        if (!isset($data['value']) || !isset($data['unit']) || !is_numeric($data['value']) || !is_string($data['unit']) || trim($data['unit']) === '') {
            $payload = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
            throw new \InvalidArgumentException(
                'Invalid quantity payload: expected numeric "value" and non-empty string "unit". Received: ' . ($payload === false ? '[unencodable payload]' : $payload)
            );
        }

        return new self(floatval($data['value']), trim($data['unit']));
    }
}
