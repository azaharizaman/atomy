<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Throwable;

final class AiStatusController extends Controller
{
    use InteractsWithAiAvailability;

    public function show(): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->aiStatusSnapshot()->toArray(),
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

        return [
            'mode' => $normalizedMode,
            'global_health' => AiStatusSchema::HEALTH_UNAVAILABLE,
            'reason_codes' => ['provider_unavailable'],
            'generated_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            'capability_definitions' => [],
            'capability_statuses' => [],
            'endpoint_groups' => [],
        ];
    }
}
