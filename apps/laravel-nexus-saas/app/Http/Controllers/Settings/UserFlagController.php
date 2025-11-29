<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Repositories\UserFlagOverrideRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing user-level feature flag overrides.
 */
class UserFlagController extends Controller
{
    public function __construct(
        private readonly UserFlagOverrideRepository $repository
    ) {}

    /**
     * Get all flag overrides for a specific user.
     */
    public function listForUser(string $userId): JsonResponse
    {
        $overrides = $this->repository->getAllForUser($userId);

        return response()->json([
            'data' => array_map(fn($o) => $o->toApiArray(), $overrides),
        ]);
    }

    /**
     * Get all active (non-expired) overrides for a user.
     */
    public function activeForUser(string $userId): JsonResponse
    {
        $overrides = $this->repository->getActiveForUser($userId);

        return response()->json([
            'data' => array_map(fn($o) => $o->toApiArray(), $overrides),
        ]);
    }

    /**
     * Get all overrides for a specific flag.
     */
    public function listForFlag(string $flagName): JsonResponse
    {
        $overrides = $this->repository->getAllForFlag($flagName);

        return response()->json([
            'data' => array_map(fn($o) => $o->toApiArray(), $overrides),
        ]);
    }

    /**
     * Get a specific override by ID.
     */
    public function show(string $id): JsonResponse
    {
        $override = $this->repository->findById($id);

        if ($override === null) {
            return response()->json([
                'message' => 'User flag override not found.',
            ], 404);
        }

        return response()->json([
            'data' => $override->toApiArray(),
        ]);
    }

    /**
     * Create or update a user flag override.
     */
    public function upsert(Request $request, string $userId, string $flagName): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'value' => 'nullable|array',
            'reason' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
        ]);

        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        $override = $this->repository->upsert($userId, $flagName, $validated);

        return response()->json([
            'message' => 'User flag override saved successfully.',
            'data' => $override->toApiArray(),
        ], 201);
    }

    /**
     * Update a user flag override.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $override = $this->repository->findById($id);

        if ($override === null) {
            return response()->json([
                'message' => 'User flag override not found.',
            ], 404);
        }

        $validated = $request->validate([
            'enabled' => 'boolean',
            'value' => 'nullable|array',
            'reason' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
        ]);

        $validated['updated_by'] = $request->user()?->id;

        $updatedOverride = $this->repository->update($id, $validated);

        return response()->json([
            'message' => 'User flag override updated successfully.',
            'data' => $updatedOverride->toApiArray(),
        ]);
    }

    /**
     * Delete a user flag override.
     */
    public function destroy(string $id): JsonResponse
    {
        $override = $this->repository->findById($id);

        if ($override === null) {
            return response()->json([
                'message' => 'User flag override not found.',
            ], 404);
        }

        $this->repository->deleteById($id);

        return response()->json([
            'message' => 'User flag override deleted successfully.',
        ]);
    }

    /**
     * Delete all overrides for a user.
     */
    public function deleteAllForUser(string $userId): JsonResponse
    {
        $count = $this->repository->deleteAllForUser($userId);

        return response()->json([
            'message' => "Deleted {$count} user flag override(s).",
            'deleted_count' => $count,
        ]);
    }

    /**
     * Delete a specific override by user and flag.
     */
    public function deleteByUserAndFlag(string $userId, string $flagName): JsonResponse
    {
        $deleted = $this->repository->deleteByUserAndFlag($userId, $flagName);

        if (!$deleted) {
            return response()->json([
                'message' => 'User flag override not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'User flag override deleted successfully.',
        ]);
    }

    /**
     * Delete all expired overrides.
     */
    public function deleteExpired(): JsonResponse
    {
        $count = $this->repository->deleteExpired();

        return response()->json([
            'message' => "Deleted {$count} expired user flag override(s).",
            'deleted_count' => $count,
        ]);
    }

    /**
     * Check if a flag is enabled for a specific user.
     */
    public function checkForUser(string $userId, string $flagName): JsonResponse
    {
        $isEnabled = $this->repository->isEnabledForUser($userId, $flagName);

        return response()->json([
            'user_id' => $userId,
            'flag_name' => $flagName,
            'enabled' => $isEnabled,
            'has_override' => $isEnabled !== null,
        ]);
    }

    /**
     * Get the current user's flag overrides.
     */
    public function myOverrides(Request $request): JsonResponse
    {
        $userId = (string) $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $overrides = $this->repository->getActiveForUser($userId);

        return response()->json([
            'data' => array_map(fn($o) => $o->toApiArray(), $overrides),
        ]);
    }
}
