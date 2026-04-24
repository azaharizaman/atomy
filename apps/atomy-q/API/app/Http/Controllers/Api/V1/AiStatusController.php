<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Illuminate\Http\JsonResponse;
use Nexus\IntelligenceOperations\Contracts\AiStatusCoordinatorInterface;
use Nexus\IntelligenceOperations\DTOs\AiEndpointHealthSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;

final class AiStatusController extends Controller
{
    use InteractsWithAiAvailability;

    public function show(): JsonResponse
    {
        try {
            $data = $this->aiStatusSnapshot()->toArray();
            $data['provider_name'] = $this->aiProviderName();

            return response()->json([
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'data' => $this->fallbackPayload(),
            ]);
        }
    }

    private function fallbackPayload(): array
    {
        $mode = config('atomy.ai.mode');
        $normalizedMode = is_string($mode) && in_array($mode, AiStatusSchema::modes(), true)
            ? $mode
            : AiStatusSchema::MODE_PROVIDER;
        $checkedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $endpointGroupHealthSnapshots = [];
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

        foreach (AiStatusSchema::endpointGroups() as $endpointGroup) {
            $endpointGroupHealthSnapshots[] = new AiEndpointHealthSnapshot(
                endpointGroup: $endpointGroup,
                health: $health,
                checkedAt: $checkedAt,
                reasonCodes: $reasonCodes,
                diagnostics: [
                    'mode' => $normalizedMode,
                    'provider_name' => $this->fallbackProviderName(),
                ],
            );
        }

        try {
            $snapshot = app(AiStatusCoordinatorInterface::class)->snapshot(
                $normalizedMode,
                $endpointGroupHealthSnapshots,
                $checkedAt,
            );
            $payload = $snapshot->toArray();
        } catch (Throwable $inner) {
            report($inner);

            $payload = [
                'mode' => $normalizedMode,
                'global_health' => $health,
                'reason_codes' => $reasonCodes,
                'generated_at' => $checkedAt->format(DATE_ATOM),
                'capability_definitions' => [],
                'capability_statuses' => [],
                'endpoint_groups' => [],
            ];
        }

        $payload['provider_name'] = $this->fallbackProviderName();

        return $payload;
    }

    private function aiProviderName(): string
    {
        /** @var AiRuntimeStatusInterface $runtimeStatus */
        $runtimeStatus = app(AiRuntimeStatusInterface::class);

        try {
            return $runtimeStatus->providerName();
        } catch (Throwable) {
            return $this->fallbackProviderName();
        }
    }

    private function fallbackProviderName(): string
    {
        $provider = config('atomy.ai.provider');
        if (! is_array($provider)) {
            return 'openrouter';
        }

        $providerName = trim((string) ($provider['name'] ?? ''));
        if ($providerName !== '') {
            return $providerName;
        }

        $providerKey = $provider['key'] ?? null;
        if ($providerKey === null) {
            return 'openrouter';
        }

        $normalizedProviderKey = strtolower(trim((string) $providerKey));

        return $normalizedProviderKey !== '' ? $normalizedProviderKey : 'openrouter';
    }
}
