<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

final readonly class Distance
{
    public function __construct(public float $meters) {}

    public function add(self $other): self
    {
        return new self($this->meters + $other->meters);
    }

    public function toMeters(): float
    {
        return $this->meters;
    }

    /** @return array{meters: float} */
    public function toArray(): array
    {
        return ['meters' => (float) $this->meters];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['meters']) || !is_numeric($data['meters'])) {
            $payload = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
            throw new \InvalidArgumentException(
                'Invalid distance payload: expected numeric "meters". Received: ' . ($payload === false ? '[unencodable payload]' : $payload)
            );
        }

        return new self((float) $data['meters']);
    }
}
