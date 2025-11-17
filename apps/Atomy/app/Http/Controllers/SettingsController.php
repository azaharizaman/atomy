<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SettingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Setting\Exceptions\ProtectedSettingException;
use Nexus\Setting\Exceptions\ReadOnlySettingException;
use Nexus\Setting\Exceptions\SettingValidationException;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Setting\Services\SettingsValidationService;
use Nexus\Setting\ValueObjects\SettingLayer;

/**
 * Settings controller for managing application, tenant, and user settings.
 */
class SettingsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly SettingsManager $manager,
        private readonly SettingsValidationService $validator,
    ) {
    }

    /**
     * Get all settings for current scope.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $tenantId = $request->user()?->tenant_id;

        $userSettings = $userId ? $this->manager->getAllUserSettings($userId) : [];
        $tenantSettings = $tenantId ? $this->manager->getAllTenantSettings($tenantId) : [];

        return response()->json([
            'user' => $userSettings,
            'tenant' => $tenantSettings,
        ]);
    }

    /**
     * Get a setting with hierarchical resolution.
     */
    public function get(Request $request, string $key): JsonResponse
    {
        $userId = $request->user()?->id;
        $tenantId = $request->user()?->tenant_id;
        $default = $request->query('default');

        $value = $this->manager->get($key, $default, $userId, $tenantId);

        return response()->json([
            'key' => $key,
            'value' => $value,
            'origin' => $this->manager->getOrigin($key, $userId, $tenantId),
        ]);
    }

    /**
     * Get settings by prefix.
     */
    public function getByPrefix(Request $request, string $prefix): JsonResponse
    {
        $userId = $request->user()?->id;
        $tenantId = $request->user()?->tenant_id;

        $settings = $this->manager->getByPrefix($prefix, $userId, $tenantId);

        return response()->json([
            'prefix' => $prefix,
            'settings' => $settings,
        ]);
    }

    /**
     * Get setting metadata.
     */
    public function getMetadata(string $key): JsonResponse
    {
        $metadata = $this->manager->getMetadata($key);

        if ($metadata === null) {
            return response()->json([
                'error' => 'Metadata not found for setting: ' . $key,
            ], 404);
        }

        return response()->json($metadata);
    }

    /**
     * Get setting origin (which layer it came from).
     */
    public function getOrigin(Request $request, string $key): JsonResponse
    {
        $userId = $request->user()?->id;
        $tenantId = $request->user()?->tenant_id;

        $origin = $this->manager->getOrigin($key, $userId, $tenantId);

        return response()->json([
            'key' => $key,
            'origin' => $origin,
        ]);
    }

    /**
     * Set a user-scoped setting.
     */
    public function setUserSetting(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'present',
        ]);

        $userId = $request->user()->id;
        $key = $request->input('key');
        $value = $request->input('value');

        try {
            // Validate value
            $this->validator->validate($key, $value);

            // Set setting
            $this->manager->setUserSetting($userId, $key, $value);

            return response()->json([
                'success' => true,
                'message' => 'User setting updated successfully',
                'key' => $key,
                'value' => $value,
            ]);
        } catch (SettingValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ReadOnlySettingException | ProtectedSettingException $e) {
            return response()->json([
                'error' => 'Cannot modify setting',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Set a tenant-scoped setting (admin only).
     */
    public function setTenantSetting(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'present',
        ]);

        $tenantId = $request->user()->tenant_id;
        $key = $request->input('key');
        $value = $request->input('value');

        try {
            // Validate value
            $this->validator->validate($key, $value);

            // Set setting
            $this->manager->setTenantSetting($tenantId, $key, $value);

            return response()->json([
                'success' => true,
                'message' => 'Tenant setting updated successfully',
                'key' => $key,
                'value' => $value,
            ]);
        } catch (SettingValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ReadOnlySettingException | ProtectedSettingException $e) {
            return response()->json([
                'error' => 'Cannot modify setting',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Bulk update multiple settings.
     */
    public function bulkSet(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'scope' => 'required|in:user,tenant',
        ]);

        $settings = $request->input('settings');
        $scope = $request->input('scope');

        $layer = match ($scope) {
            'user' => SettingLayer::USER,
            'tenant' => SettingLayer::TENANT,
        };

        $scopeId = match ($scope) {
            'user' => $request->user()->id,
            'tenant' => $request->user()->tenant_id,
        };

        try {
            // Validate all settings
            foreach ($settings as $key => $value) {
                $this->validator->validate($key, $value);
            }

            // Bulk set
            $this->manager->bulkSet($settings, $layer, $scopeId);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'count' => count($settings),
            ]);
        } catch (SettingValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ReadOnlySettingException | ProtectedSettingException $e) {
            return response()->json([
                'error' => 'Cannot modify setting',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Delete a user-scoped setting.
     */
    public function deleteUserSetting(Request $request, string $key): JsonResponse
    {
        $userId = $request->user()->id;

        try {
            $this->manager->deleteUserSetting($userId, $key);

            return response()->json([
                'success' => true,
                'message' => 'User setting deleted successfully',
                'key' => $key,
            ]);
        } catch (ReadOnlySettingException $e) {
            return response()->json([
                'error' => 'Cannot delete setting',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Delete a tenant-scoped setting (admin only).
     */
    public function deleteTenantSetting(Request $request, string $key): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        try {
            $this->manager->deleteTenantSetting($tenantId, $key);

            return response()->json([
                'success' => true,
                'message' => 'Tenant setting deleted successfully',
                'key' => $key,
            ]);
        } catch (ReadOnlySettingException $e) {
            return response()->json([
                'error' => 'Cannot delete setting',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Export tenant settings.
     */
    public function exportTenant(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $data = $this->manager->export($tenantId);

        return response()->json([
            'tenant_id' => $tenantId,
            'exported_at' => now()->toIso8601String(),
            'count' => count($data),
            'data' => $data,
        ]);
    }

    /**
     * Import tenant settings.
     */
    public function importTenant(Request $request): JsonResponse
    {
        $request->validate([
            'data' => 'required|array',
        ]);

        $tenantId = $request->user()->tenant_id;
        $data = $request->input('data');

        try {
            $this->manager->import($data, $tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Settings imported successfully',
                'count' => count($data),
            ]);
        } catch (ReadOnlySettingException | ProtectedSettingException $e) {
            return response()->json([
                'error' => 'Cannot import setting',
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get setting change history.
     */
    public function history(Request $request, string $key): JsonResponse
    {
        $history = SettingHistory::byKey($key)
            ->orderBy('changed_at', 'desc')
            ->paginate(50);

        return response()->json($history);
    }

    /**
     * Flush entire settings cache.
     */
    public function flushCache(): JsonResponse
    {
        $this->manager->getCacheManager()->flush();

        return response()->json([
            'success' => true,
            'message' => 'Settings cache flushed successfully',
        ]);
    }

    /**
     * Forget specific cache key.
     */
    public function forgetCache(string $key): JsonResponse
    {
        $this->manager->getCacheManager()->forget($key);

        return response()->json([
            'success' => true,
            'message' => 'Cache key forgotten successfully',
            'key' => $key,
        ]);
    }
}
