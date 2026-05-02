<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class AiProviderEndpointCheck
{
    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    public function __construct(
        public string $endpointGroup,
        public bool $configured,
        public bool $enabled,
        public ?string $endpointUri,
        public ?string $probeHealth,
        public ?int $latencyMs,
        public string $severity,
        public array $reasonCodes,
        public array $diagnostics = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'endpoint_group' => $this->endpointGroup,
            'configured' => $this->configured,
            'enabled' => $this->enabled,
            'endpoint_uri' => $this->endpointUri,
            'probe_health' => $this->probeHealth,
            'latency_ms' => $this->latencyMs,
            'severity' => $this->severity,
            'reason_codes' => $this->reasonCodes,
            'diagnostics' => $this->diagnostics,
        ];
    }
}
