<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RecommendationController extends Controller
{
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    /**
     * GET /recommendations/:runId
     */
    public function show(Request $request, string $runId): JsonResponse
    {
        if (! $this->aiCapabilityAvailable('recommendation_ai_endpoint')) {
            return $this->aiUnavailableResponse('recommendation_ai_endpoint');
        }

        return response()->json([
            'data' => [
                'run_id' => $runId,
                'status' => 'completed',
                'recommendations' => [],
                'created_at' => null,
            ],
        ]);
    }

    /**
     * GET /recommendations/:runId/mcda
     */
    public function mcda(Request $request, string $runId): JsonResponse
    {
        if (! $this->aiCapabilityAvailable('recommendation_ai_endpoint')) {
            return $this->aiUnavailableResponse('recommendation_ai_endpoint');
        }

        return response()->json([
            'data' => [
                'run_id' => $runId,
                'mcda_scores' => [],
                'alternatives' => [],
                'criteria_weights' => [],
            ],
        ]);
    }

    /**
     * POST /recommendations/:runId/override
     */
    public function override(Request $request, string $runId): JsonResponse
    {
        if (! $this->aiCapabilityAvailable('recommendation_ai_endpoint')) {
            return $this->aiUnavailableResponse('recommendation_ai_endpoint');
        }

        return response()->json([
            'data' => [
                'run_id' => $runId,
                'override_applied' => true,
                'recommendations' => [],
            ],
        ]);
    }

    /**
     * POST /recommendations/:runId/rerun
     */
    public function rerun(Request $request, string $runId): JsonResponse
    {
        if (! $this->aiCapabilityAvailable('recommendation_ai_endpoint')) {
            return $this->aiUnavailableResponse('recommendation_ai_endpoint');
        }

        return response()->json([
            'data' => [
                'run_id' => 'stub-new-run-id',
                'status' => 'pending',
                'previous_run_id' => $runId,
            ],
        ], 201);
    }
}
