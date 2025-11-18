<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class PreferenceController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get user's notification preferences.
     */
    public function index(string $userId): JsonResponse
    {
        try {
            $preferences = NotificationPreference::where('user_id', $userId)
                ->get()
                ->map(fn($pref) => [
                    'id' => $pref->id,
                    'category' => $pref->category,
                    'channels' => $pref->channels,
                    'enabled' => $pref->enabled,
                    'quiet_hours_start' => $pref->quiet_hours_start,
                    'quiet_hours_end' => $pref->quiet_hours_end,
                    'frequency_limit' => $pref->frequency_limit,
                    'created_at' => $pref->created_at->toIso8601String(),
                    'updated_at' => $pref->updated_at->toIso8601String(),
                ]);

            return response()->json([
                'success' => true,
                'preferences' => $preferences,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get notification preferences', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update notification preference.
     */
    public function store(string $userId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:system,marketing,transactional,security',
            'channels' => 'required|array',
            'channels.*' => 'string|in:email,sms,push,in_app',
            'enabled' => 'required|boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'frequency_limit' => 'nullable|integer|min:1',
        ]);

        try {
            $preference = NotificationPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'category' => $validated['category'],
                ],
                [
                    'channels' => $validated['channels'],
                    'enabled' => $validated['enabled'],
                    'quiet_hours_start' => $validated['quiet_hours_start'] ?? null,
                    'quiet_hours_end' => $validated['quiet_hours_end'] ?? null,
                    'frequency_limit' => $validated['frequency_limit'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'preference' => [
                    'id' => $preference->id,
                    'category' => $preference->category,
                    'channels' => $preference->channels,
                    'enabled' => $preference->enabled,
                    'quiet_hours_start' => $preference->quiet_hours_start,
                    'quiet_hours_end' => $preference->quiet_hours_end,
                    'frequency_limit' => $preference->frequency_limit,
                ],
                'message' => 'Preference saved successfully',
            ], 201);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to save notification preference', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete notification preference.
     */
    public function destroy(string $userId, string $preferenceId): JsonResponse
    {
        try {
            $deleted = NotificationPreference::where('user_id', $userId)
                ->where('id', $preferenceId)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preference deleted successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Preference not found',
            ], 404);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete notification preference', [
                'user_id' => $userId,
                'preference_id' => $preferenceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
