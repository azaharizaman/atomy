<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class MetricFactDto
{
    public function __construct(
        public string $key,
        public mixed $value,
        public string $status = 'available',
        public ?string $reasonCode = null,
    ) {}

    /**
     * @return array{key: string, value: mixed, status: string, reason_code: string|null}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'status' => $this->status,
            'reason_code' => $this->reasonCode,
        ];
    }
}
