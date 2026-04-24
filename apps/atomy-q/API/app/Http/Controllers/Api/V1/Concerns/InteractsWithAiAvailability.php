<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Nexus\IntelligenceOperations\Contracts\AiStatusCoordinatorInterface;
use Nexus\IntelligenceOperations\DTOs\AiEndpointHealthSnapshot;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use Illuminate\Http\JsonResponse;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;

trait InteractsWithAiAvailability
{
    private ?AiStatusSnapshot $cachedAiStatusSnapshot = null;

    protected function aiStatusSnapshot(): AiStatusSnapshot
    {
        if ($this->cachedAiStatusSnapshot instanceof AiStatusSnapshot) {
            return $this->cachedAiStatusSnapshot;
        }

        /** @var AiRuntimeStatusInterface $statusRuntime */
        $statusRuntime = app(AiRuntimeStatusInterface::class);
        try {
            $this->cachedAiStatusSnapshot = $statusRuntime->snapshot();
        } catch (Throwable $exception) {
            report($exception);
            $this->cachedAiStatusSnapshot = $this->fallbackAiStatusSnapshot();
        }

        return $this->cachedAiStatusSnapshot;
    }

    protected function aiCapabilityStatus(string $featureKey): ?AiCapabilityStatus
    {
        return $this->aiStatusSnapshot()->capabilityStatuses[$featureKey] ?? null;
    }

    protected function aiCapabilityAvailable(string $featureKey): bool
    {
        return $this->aiCapabilityStatus($featureKey)?->available === true;
    }

    /**
     * @param list<string> $reasonCodes
     * @param array<string, scalar|null> $diagnostics
     */
    protected function aiUnavailableResponse(
        string $featureKey,
        int $statusCode = 503,
        array $reasonCodes = [],
        array $diagnostics = [],
    ): JsonResponse
    {
        $capabilityStatus = $this->aiCapabilityStatus($featureKey);
        $statusSnapshot = $this->aiStatusSnapshot();
        $resolvedReasonCodes = $reasonCodes !== []
            ? array_values(array_unique(array_filter(array_map(
                static fn (mixed $reasonCode): string => is_string($reasonCode) ? trim($reasonCode) : '',
                $reasonCodes,
            ))))
            : ($capabilityStatus?->reasonCodes ?? $statusSnapshot->reasonCodes);
        $resolvedDiagnostics = ($diagnostics !== [] || $reasonCodes !== [])
            ? ['mode' => $statusSnapshot->mode]
            : ($capabilityStatus?->diagnostics ?? ['mode' => $statusSnapshot->mode]);

        foreach ($diagnostics as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }

            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            $resolvedDiagnostics[$key] = $value;
        }

        return response()->json([
            'message' => 'AI capability unavailable',
            'data' => [
                'feature_key' => $featureKey,
                'mode' => $statusSnapshot->mode,
                'status' => $capabilityStatus?->available === true
                    ? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE
                    : ($capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE),
                'available' => false,
                'fallback_ui_mode' => $capabilityStatus?->fallbackUiMode
                    ?? AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                'message_key' => $capabilityStatus?->messageKey ?? 'ai.capability.unavailable',
                'operator_critical' => $capabilityStatus?->operatorCritical ?? false,
                'reason_codes' => $resolvedReasonCodes,
                'diagnostics' => $resolvedDiagnostics,
            ],
        ], $statusCode);
    }

    private function fallbackAiStatusSnapshot(): AiStatusSnapshot
    {
        $mode = config('atomy.ai.mode');
        $normalizedMode = is_string($mode) && in_array($mode, AiStatusSchema::modes(), true)
            ? $mode
            : AiStatusSchema::MODE_PROVIDER;
        $checkedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $health = match ($normalizedMode) {
            AiStatusSchema::MODE_OFF,
            AiStatusSchema::MODE_DETERMINISTIC => AiStatusSchema::HEALTH_DISABLED,
            default => AiStatusSchema::HEALTH_UNAVAILABLE,
        };
        $reasonCodes = match ($normalizedMode) {
            AiStatusSchema::MODE_OFF => ['ai_disabled_by_config', 'endpoint_disabled_by_config'],
            AiStatusSchema::MODE_DETERMINISTIC => ['deterministic_fallback_mode', 'endpoint_disabled_by_config'],
            default => ['provider_unavailable', 'endpoint_group_unavailable'],
        };
        $endpointGroupHealthSnapshots = [];

        foreach (AiStatusSchema::endpointGroups() as $endpointGroup) {
            $endpointGroupHealthSnapshots[] = new AiEndpointHealthSnapshot(
                endpointGroup: $endpointGroup,
                health: $health,
                checkedAt: $checkedAt,
                reasonCodes: $reasonCodes,
                diagnostics: [
                    'mode' => $normalizedMode,
                ],
            );
        }

        try {
            return app(AiStatusCoordinatorInterface::class)->snapshot(
                $normalizedMode,
                $endpointGroupHealthSnapshots,
                $checkedAt,
            );
        } catch (Throwable $exception) {
            report($exception);

            return new AiStatusSnapshot(
                mode: $normalizedMode,
                globalHealth: $health,
                capabilityDefinitions: [],
                capabilityStatuses: [],
                endpointGroupHealthSnapshots: [],
                reasonCodes: $reasonCodes,
                generatedAt: $checkedAt,
            );
        }
    }
}
