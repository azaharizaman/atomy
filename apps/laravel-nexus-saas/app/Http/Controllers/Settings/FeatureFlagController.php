<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Repositories\FeatureFlagRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Enums\FlagStrategy;

/**
 * Controller for managing feature flags in settings.
 */
class FeatureFlagController extends Controller
{
    public function __construct(
        private readonly FeatureFlagRepository $repository,
        private readonly FeatureFlagManagerInterface $manager
    ) {}

    /**
     * Display the feature flags settings page.
     */
    public function index(): Response
    {
        $flags = $this->repository->all();

        return Inertia::render('settings/FeatureFlags', [
            'flags' => array_map(fn($flag) => $flag->toApiArray(), $flags),
            'strategies' => array_map(fn($s) => [
                'value' => $s->value,
                'label' => ucwords(str_replace('_', ' ', $s->value)),
            ], FlagStrategy::cases()),
        ]);
    }

    /**
     * Get all feature flags.
     */
    public function list(): JsonResponse
    {
        $flags = $this->repository->all();

        return response()->json([
            'data' => array_map(fn($flag) => $flag->toApiArray(), $flags),
        ]);
    }

    /**
     * Create a new feature flag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z][a-z0-9_]*$/',
            'description' => 'nullable|string|max:255',
            'enabled' => 'boolean',
            'strategy' => 'required|string|in:' . implode(',', array_column(FlagStrategy::cases(), 'value')),
            'value' => 'nullable|array',
            'override' => 'nullable|string|in:force_on,force_off',
            'metadata' => 'nullable|array',
        ]);

        // Check if flag with same name already exists
        if ($this->repository->exists($validated['name'])) {
            return response()->json([
                'message' => 'A feature flag with this name already exists.',
                'errors' => ['name' => ['A feature flag with this name already exists.']],
            ], 422);
        }

        $flag = $this->repository->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'enabled' => $validated['enabled'] ?? false,
            'strategy' => $validated['strategy'],
            'value' => $validated['value'] ?? null,
            'override' => $validated['override'] ?? null,
            'metadata' => $validated['metadata'] ?? [],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Feature flag created successfully.',
            'data' => $flag->toApiArray(),
        ], 201);
    }

    /**
     * Get a specific feature flag.
     */
    public function show(string $id): JsonResponse
    {
        $flag = $this->repository->findById($id);

        if ($flag === null) {
            return response()->json([
                'message' => 'Feature flag not found.',
            ], 404);
        }

        return response()->json([
            'data' => $flag->toApiArray(),
        ]);
    }

    /**
     * Update a feature flag.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $flag = $this->repository->findById($id);

        if ($flag === null) {
            return response()->json([
                'message' => 'Feature flag not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|regex:/^[a-z][a-z0-9_]*$/',
            'description' => 'nullable|string|max:255',
            'enabled' => 'boolean',
            'strategy' => 'sometimes|string|in:' . implode(',', array_column(FlagStrategy::cases(), 'value')),
            'value' => 'nullable|array',
            'override' => 'nullable|string|in:force_on,force_off',
            'metadata' => 'nullable|array',
        ]);

        // Check for name uniqueness if name is being changed
        if (isset($validated['name']) && $validated['name'] !== $flag->getName()) {
            if ($this->repository->exists($validated['name'])) {
                return response()->json([
                    'message' => 'A feature flag with this name already exists.',
                    'errors' => ['name' => ['A feature flag with this name already exists.']],
                ], 422);
            }
        }

        $validated['updated_by'] = $request->user()?->id;

        $updatedFlag = $this->repository->update($id, $validated);

        return response()->json([
            'message' => 'Feature flag updated successfully.',
            'data' => $updatedFlag->toApiArray(),
        ]);
    }

    /**
     * Delete a feature flag.
     */
    public function destroy(string $id): JsonResponse
    {
        $flag = $this->repository->findById($id);

        if ($flag === null) {
            return response()->json([
                'message' => 'Feature flag not found.',
            ], 404);
        }

        $this->repository->deleteById($id);

        return response()->json([
            'message' => 'Feature flag deleted successfully.',
        ]);
    }

    /**
     * Toggle a feature flag's enabled state.
     */
    public function toggle(string $id): JsonResponse
    {
        $flag = $this->repository->toggle($id);

        if ($flag === null) {
            return response()->json([
                'message' => 'Feature flag not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Feature flag toggled successfully.',
            'data' => $flag->toApiArray(),
        ]);
    }

    /**
     * Check if a feature flag is enabled.
     */
    public function check(Request $request, string $name): JsonResponse
    {
        $context = [];

        // Build context from request
        if ($request->user()) {
            $context['user_id'] = (string) $request->user()->id;
        }

        // Add any additional context from query params
        if ($request->has('context')) {
            $additionalContext = $request->input('context');
            if (is_array($additionalContext)) {
                $context = array_merge($context, $additionalContext);
            }
        }

        $isEnabled = $this->manager->isEnabled($name, $context);

        return response()->json([
            'name' => $name,
            'enabled' => $isEnabled,
        ]);
    }

    /**
     * Get all enabled flags for the current user/context.
     */
    public function enabledFlags(Request $request): JsonResponse
    {
        $context = [];

        if ($request->user()) {
            $context['user_id'] = (string) $request->user()->id;
        }

        $enabledFlags = $this->repository->getEnabledFlags();
        $result = [];

        foreach ($enabledFlags as $flag) {
            $result[$flag->getName()] = $this->manager->isEnabled($flag->getName(), $context);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}
