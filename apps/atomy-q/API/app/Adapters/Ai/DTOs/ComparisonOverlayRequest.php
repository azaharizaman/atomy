<?php

declare(strict_types=1);

namespace App\Adapters\Ai\DTOs;

use InvalidArgumentException;

final readonly class ComparisonOverlayRequest
{
    private const ALLOWED_MODES = ['preview', 'final'];
    public string $tenantId;
    public string $rfqId;
    public string $mode;
    /** @var array<string, mixed> */
    public array $comparison;
    /** @var array<string, mixed>|null */
    public ?array $snapshot;

    /**
     * @param array<string, mixed> $comparison
     * @param array<string, mixed>|null $snapshot
     */
    public function __construct(
        string $tenantId,
        string $rfqId,
        string $mode,
        array $comparison,
        ?array $snapshot = null,
    ) {
        $tenantId = trim($tenantId);
        $rfqId = trim($rfqId);
        $mode = strtolower(trim($mode));

        if ($tenantId === '') {
            throw new InvalidArgumentException('Comparison overlay request requires tenantId.');
        }

        if ($rfqId === '') {
            throw new InvalidArgumentException('Comparison overlay request requires rfqId.');
        }

        if ($mode === '') {
            throw new InvalidArgumentException('Comparison overlay request requires mode.');
        }

        if (! in_array($mode, self::ALLOWED_MODES, true)) {
            throw new InvalidArgumentException('Comparison overlay request mode must be one of: preview, final.');
        }

        $this->tenantId = $tenantId;
        $this->rfqId = $rfqId;
        $this->mode = $mode;
        $this->comparison = $comparison;
        $this->snapshot = $snapshot;
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
