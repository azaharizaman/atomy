<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\DTOs;

final readonly class ModelHealthSnapshot
{
    public function __construct(
        public float $accuracy,
        public float $latencyMs,
        public float $driftScore,
        public string $status,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'accuracy' => $this->accuracy,
            'latency_ms' => $this->latencyMs,
            'drift_score' => $this->driftScore,
            'status' => $this->status,
        ];
    }
}
