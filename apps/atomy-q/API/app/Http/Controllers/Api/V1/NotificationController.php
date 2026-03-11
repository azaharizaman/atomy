<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification API controller (Section 25).
 *
 * Handles notifications, read status, and clearing.
 */
final class NotificationController extends Controller
{
    use ExtractsAuthContext;

    /**
     * List notifications.
     *
     * GET /notifications
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * Get unread notification count.
     *
     * GET /notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'count' => 0,
            ],
        ]);
    }

    /**
     * Mark notification as read.
     *
     * PATCH /notifications/:id/read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'read_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * POST /notifications/mark-all-read
     */
    public function markAllRead(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'affected' => 0,
            ],
        ]);
    }

    /**
     * Clear read notifications.
     *
     * DELETE /notifications/clear-read
     */
    public function clearRead(Request $request): JsonResponse
    {
        return response()->json([], 204);
    }
}
