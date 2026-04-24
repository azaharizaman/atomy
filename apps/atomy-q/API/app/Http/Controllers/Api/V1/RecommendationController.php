<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
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
        return $this->aiUnavailableResponse('recommendation_ai_endpoint');
    }

    /**
     * GET /recommendations/:runId/mcda
     */
    public function mcda(Request $request, string $runId): JsonResponse
    {
        return $this->aiUnavailableResponse('recommendation_ai_endpoint');
    }

    /**
     * POST /recommendations/:runId/override
     */
    public function override(Request $request, string $runId): JsonResponse
    {
        return $this->aiUnavailableResponse('recommendation_ai_endpoint');
    }

    /**
     * POST /recommendations/:runId/rerun
     */
    public function rerun(Request $request, string $runId): JsonResponse
    {
        return $this->aiUnavailableResponse('recommendation_ai_endpoint');
    }
}
