<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Feature Flags API Controller
 *
 * Endpoints for managing feature flags (CRUD operations).
 */
final class FeatureFlagController extends Controller
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly AuditLogManagerInterface $auditLogger
    ) {
    }

    /**
     * List all feature flags for the current tenant.
     *
     * GET /api/feature-flags
     */
    public function index(): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        $flags = FeatureFlag::query()
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id'); // Include global flags
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $flags,
        ]);
    }

    /**
     * Show a specific feature flag.
     *
     * GET /api/feature-flags/{name}
     */
    public function show(string $name): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        // Try tenant-specific first
        $flag = FeatureFlag::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        // Fall back to global
        if ($flag === null) {
            $flag = FeatureFlag::query()
                ->whereNull('tenant_id')
                ->where('name', $name)
                ->first();
        }

        if ($flag === null) {
            return response()->json([
                'error' => 'Feature flag not found',
            ], 404);
        }

        return response()->json([
            'data' => $flag,
        ]);
    }

    /**
     * Create a new feature flag.
     *
     * POST /api/feature-flags
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'regex:/^[a-z0-9_\.]{1,100}$/'],
            'enabled' => ['required', 'boolean'],
            'strategy' => ['required', 'string', 'in:system_wide,percentage_rollout,tenant_list,user_list,custom'],
            'value' => ['nullable'],
            'override' => ['nullable', 'string', 'in:force_on,force_off'],
            'metadata' => ['nullable', 'array'],
            'scope' => ['nullable', 'string', 'in:global,tenant'], // global or tenant-specific
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $tenantId = $data['scope'] === 'global' ? null : $this->tenantContext->getCurrentTenantId();

        // Check for duplicate
        $existing = FeatureFlag::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $data['name'])
            ->exists();

        if ($existing) {
            return response()->json([
                'error' => 'Feature flag already exists',
            ], 409);
        }

        $flag = FeatureFlag::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'enabled' => $data['enabled'],
            'strategy' => FlagStrategy::from($data['strategy']),
            'value' => $data['value'] ?? null,
            'override' => isset($data['override']) ? FlagOverride::from($data['override']) : null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->auditLogger->log(
            entityId: $flag->id,
            action: 'feature_flag.created',
            description: "Feature flag '{$flag->name}' created",
            metadata: [
                'name' => $flag->name,
                'enabled' => $flag->enabled,
                'strategy' => $flag->strategy->value,
                'scope' => $tenantId !== null ? 'tenant' : 'global',
            ]
        );

        return response()->json([
            'data' => $flag,
        ], 201);
    }

    /**
     * Update an existing feature flag.
     *
     * PUT /api/feature-flags/{name}
     */
    public function update(Request $request, string $name): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        // Find tenant-specific or global flag
        $flag = FeatureFlag::query()
            ->where('name', $name)
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->first();

        if ($flag === null) {
            return response()->json([
                'error' => 'Feature flag not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => ['sometimes', 'boolean'],
            'strategy' => ['sometimes', 'string', 'in:system_wide,percentage_rollout,tenant_list,user_list,custom'],
            'value' => ['nullable'],
            'override' => ['nullable', 'string', 'in:force_on,force_off'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $oldData = [
            'enabled' => $flag->enabled,
            'strategy' => $flag->strategy->value,
            'override' => $flag->override?->value,
        ];

        if (isset($data['enabled'])) {
            $flag->enabled = $data['enabled'];
        }

        if (isset($data['strategy'])) {
            $flag->strategy = FlagStrategy::from($data['strategy']);
        }

        if (array_key_exists('value', $data)) {
            $flag->value = $data['value'];
        }

        if (array_key_exists('override', $data)) {
            $flag->override = $data['override'] !== null ? FlagOverride::from($data['override']) : null;
        }

        if (array_key_exists('metadata', $data)) {
            $flag->metadata = $data['metadata'];
        }

        $flag->save();

        $this->auditLogger->log(
            entityId: $flag->id,
            action: 'feature_flag.updated',
            description: "Feature flag '{$flag->name}' updated",
            metadata: [
                'name' => $flag->name,
                'old' => $oldData,
                'new' => [
                    'enabled' => $flag->enabled,
                    'strategy' => $flag->strategy->value,
                    'override' => $flag->override?->value,
                ],
            ]
        );

        return response()->json([
            'data' => $flag,
        ]);
    }

    /**
     * Delete a feature flag.
     *
     * DELETE /api/feature-flags/{name}
     */
    public function destroy(string $name): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        $flag = FeatureFlag::query()
            ->where('name', $name)
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            })
            ->first();

        if ($flag === null) {
            return response()->json([
                'error' => 'Feature flag not found',
            ], 404);
        }

        $flagId = $flag->id;
        $flagName = $flag->name;

        $flag->delete();

        $this->auditLogger->log(
            entityId: $flagId,
            action: 'feature_flag.deleted',
            description: "Feature flag '{$flagName}' deleted"
        );

        return response()->json([
            'message' => 'Feature flag deleted successfully',
        ]);
    }
}
