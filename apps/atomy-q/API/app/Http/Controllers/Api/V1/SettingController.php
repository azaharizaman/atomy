<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Setting API controller (Section 24).
 *
 * Handles tenant settings, workflow, compliance, and feature flags.
 */
final class SettingController extends Controller
{
    use ExtractsAuthContext;

    /**
     * List all settings.
     *
     * GET /settings
     */
    public function index(Request $request): JsonResponse
    {
        return $this->notImplemented('Listing tenant settings', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * Update general settings.
     *
     * PUT /settings/general
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        return $this->notImplemented('Updating general settings', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * Update workflow settings.
     *
     * PUT /settings/workflow
     */
    public function updateWorkflow(Request $request): JsonResponse
    {
        return $this->notImplemented('Updating workflow settings', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * Update compliance settings.
     *
     * PUT /settings/compliance
     */
    public function updateCompliance(Request $request): JsonResponse
    {
        return $this->notImplemented('Updating compliance settings', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * List feature flags.
     *
     * GET /feature-flags
     */
    public function featureFlags(Request $request): JsonResponse
    {
        return $this->notImplemented('Listing feature flags', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * Update feature flag.
     *
     * PUT /feature-flags/:id
     */
    public function updateFeatureFlag(Request $request, string $id): JsonResponse
    {
        return $this->notImplemented('Updating feature flag', [
            'tenant_id' => $this->tenantId($request),
            'feature_flag_id' => $id,
        ]);
    }

    /**
     * @param array<string, string> $context
     */
    private function notImplemented(string $operation, array $context = []): JsonResponse
    {
        return response()->json([
            'error' => 'Not implemented',
            'message' => $operation.' is not implemented yet.',
            'context' => $context,
        ], 501);
    }
}
