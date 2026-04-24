<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

trait BuildsAiArtifactEnvelope
{
    /**
     * @param array<string, mixed> $payload
     * @param ?array<string, mixed> $provenance
     * @return array<string, mixed>
     */
    protected function artifactEnvelope(string $featureKey, array $payload, ?array $provenance = null): array
    {
        return [
            'feature_key' => $featureKey,
            'capability_group' => $this->aiArtifactCapabilityGroup(),
            'available' => true,
            'status' => AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            'manual_continuity' => 'available',
            'payload' => $payload,
            'provenance' => $provenance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function unavailableArtifactEnvelope(string $featureKey): array
    {
        $capabilityStatus = $this->aiCapabilityStatus($featureKey);
        $statusSnapshot = $this->aiStatusSnapshot();

        return [
            'feature_key' => $featureKey,
            'capability_group' => $this->aiArtifactCapabilityGroup(),
            'available' => false,
            'status' => $capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            'manual_continuity' => 'available',
            'fallback_ui_mode' => $capabilityStatus?->fallbackUiMode ?? AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
            'message_key' => $capabilityStatus?->messageKey ?? 'ai.capability.unavailable',
            'reason_codes' => $capabilityStatus?->reasonCodes ?? $statusSnapshot->reasonCodes,
            'diagnostics' => $capabilityStatus?->diagnostics ?? ['mode' => $statusSnapshot->mode],
            'payload' => null,
            'provenance' => [
                'source' => 'deterministic',
                'endpoint_group' => $this->aiArtifactEndpointGroup(),
                'provider_name' => $this->providerName(),
                'generated_at' => $this->clock->now()->format(DATE_ATOM),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param string $endpointGroup
     * @return array<string, mixed>
     */
    protected function providerArtifactProvenance(array $payload, string $endpointGroup): array
    {
        $provenance = is_array($payload['provenance'] ?? null) ? $payload['provenance'] : [];

        return array_replace($provenance, [
            'source' => 'provider',
            'provider_name' => $this->providerName(),
            'endpoint_group' => $endpointGroup,
            'generated_at' => $provenance['generated_at'] ?? $this->clock->now()->format(DATE_ATOM),
        ]);
    }

    protected function providerName(): ?string
    {
        $providerName = config('atomy.ai.provider.name');
        if (is_string($providerName)) {
            $providerName = trim($providerName);
            if ($providerName !== '') {
                return $providerName;
            }
        }

        $providerKey = config('atomy.ai.provider.key');
        if (! is_string($providerKey)) {
            return null;
        }

        $providerKey = trim($providerKey);

        return $providerKey === '' ? null : strtolower($providerKey);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function unwrapProviderPayload(array $payload): array
    {
        $content = is_array($payload['payload'] ?? null) ? $payload['payload'] : $payload;

        return is_array($content) ? $content : [];
    }

    abstract protected function aiArtifactCapabilityGroup(): string;

    abstract protected function aiArtifactEndpointGroup(): string;
}
