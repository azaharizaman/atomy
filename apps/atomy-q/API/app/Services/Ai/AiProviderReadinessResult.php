<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class AiProviderReadinessResult
{
    /**
     * @param list<AiProviderEndpointCheck> $endpointGroups
     * @param list<AiProviderCheckFinding> $operatorFindings
     * @param list<array<string, mixed>> $publishedAlerts
     */
    public function __construct(
        public string $checkedAt,
        public string $mode,
        public string $provider,
        public bool $deep,
        public array $endpointGroups,
        public array $operatorFindings,
        public array $publishedAlerts = [],
    ) {
    }

    public function exitSeverity(): string
    {
        $severity = AiProviderCheckSeverity::OK;

        foreach ($this->endpointGroups as $endpointGroup) {
            $severity = AiProviderCheckSeverity::worse($severity, $endpointGroup->severity);
        }

        foreach ($this->operatorFindings as $finding) {
            $severity = AiProviderCheckSeverity::worse($severity, $finding->severity);
        }

        return $severity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $exitSeverity = $this->exitSeverity();

        return [
            'checked_at' => $this->checkedAt,
            'mode' => $this->mode,
            'provider' => $this->provider,
            'global_status' => $exitSeverity,
            'deep' => $this->deep,
            'endpoint_groups' => array_map(
                static fn (AiProviderEndpointCheck $endpointGroup): array => $endpointGroup->toArray(),
                $this->endpointGroups,
            ),
            'operator_findings' => array_map(
                static fn (AiProviderCheckFinding $finding): array => $finding->toArray(),
                $this->operatorFindings,
            ),
            'published_alerts' => $this->publishedAlerts,
            'exit_severity' => $exitSeverity,
        ];
    }
}
