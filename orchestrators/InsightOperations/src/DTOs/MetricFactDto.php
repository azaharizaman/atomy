<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class MetricFactDto
{
    public MetricStatus $status;

    public function __construct(
        public string $key,
        public mixed $value,
        string|MetricStatus $status = MetricStatus::AVAILABLE,
        public ?string $reasonCode = null,
    ) {
        $this->status = is_string($status)
            ? MetricStatus::from($status)
            : $status;

        if (
            $this->status === MetricStatus::AVAILABLE &&
            $this->reasonCode !== null
        ) {
            throw new \InvalidArgumentException(
                "Reason code must be null when status is available.",
            );
        }
    }

    /**
     * @return array{key: string, value: mixed, status: string, reason_code: string|null}
     */
    public function toArray(): array
    {
        return [
            "key" => $this->key,
            "value" => $this->value,
            "status" => $this->status->value,
            "reason_code" => $this->reasonCode,
        ];
    }
}
