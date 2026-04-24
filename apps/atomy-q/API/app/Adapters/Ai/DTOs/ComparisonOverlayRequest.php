<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class ComparisonOverlayRequest
{
    /**
     * @param array<string, mixed> $comparison
     * @param array<string, mixed>|null $snapshot
     */
    public function __construct(
        public string $tenantId,
        public string $rfqId,
        public string $mode,
        public array $comparison,
        public ?array $snapshot = null,
    ) {
        if (trim($tenantId) === '') {
            throw new InvalidArgumentException('Comparison overlay request requires tenantId.');
        }

        if (trim($rfqId) === '') {
            throw new InvalidArgumentException('Comparison overlay request requires rfqId.');
        }

        if (trim($mode) === '') {
            throw new InvalidArgumentException('Comparison overlay request requires mode.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'rfq_id' => $this->rfqId,
            'mode' => $this->mode,
            'comparison' => $this->comparison,
            'snapshot' => $this->snapshot,
        ];
    }
}
