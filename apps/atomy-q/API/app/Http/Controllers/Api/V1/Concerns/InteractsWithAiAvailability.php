<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Concerns;

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
        $this->cachedAiStatusSnapshot = $statusRuntime->snapshot();

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

    protected function aiUnavailableResponse(string $featureKey, int $statusCode = 503): JsonResponse
    {
        $capabilityStatus = $this->aiCapabilityStatus($featureKey);
        $statusSnapshot = $this->aiStatusSnapshot();

        return response()->json([
            'message' => 'AI capability unavailable',
            'data' => [
                'feature_key' => $featureKey,
                'mode' => $statusSnapshot->mode,
                'status' => $capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                'available' => false,
                'fallback_ui_mode' => $capabilityStatus?->fallbackUiMode
                    ?? AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                'message_key' => $capabilityStatus?->messageKey ?? 'ai.capability.unavailable',
                'operator_critical' => $capabilityStatus?->operatorCritical ?? false,
                'reason_codes' => $capabilityStatus?->reasonCodes ?? $statusSnapshot->reasonCodes,
                'diagnostics' => $capabilityStatus?->diagnostics ?? [
                    'mode' => $statusSnapshot->mode,
                ],
            ],
        ], $statusCode);
    }
}
