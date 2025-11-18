<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

final class HistoryController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get notification history for a user.
     */
    public function index(string $userId, Request $request): JsonResponse
    {
        try {
            $query = NotificationHistory::where('recipient_id', $userId);

            if ($request->has('channel')) {
                $query->where('channel', $request->input('channel'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('from_date')) {
                $query->where('sent_at', '>=', $request->input('from_date'));
            }

            if ($request->has('to_date')) {
                $query->where('sent_at', '<=', $request->input('to_date'));
            }

            $history = $query->orderBy('sent_at', 'desc')
                ->paginate(50)
                ->through(fn($record) => [
                    'id' => $record->id,
                    'notification_id' => $record->notification_id,
                    'channel' => $record->channel,
                    'status' => $record->status,
                    'content' => $record->content,
                    'external_id' => $record->external_id,
                    'sent_at' => $record->sent_at?->toIso8601String(),
                    'delivered_at' => $record->delivered_at?->toIso8601String(),
                    'read_at' => $record->read_at?->toIso8601String(),
                    'error_message' => $record->error_message,
                ]);

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get notification history', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single notification history record.
     */
    public function show(string $userId, string $historyId): JsonResponse
    {
        try {
            $record = NotificationHistory::where('recipient_id', $userId)
                ->where('id', $historyId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'history' => [
                    'id' => $record->id,
                    'notification_id' => $record->notification_id,
                    'channel' => $record->channel,
                    'status' => $record->status,
                    'content' => $record->content,
                    'external_id' => $record->external_id,
                    'sent_at' => $record->sent_at?->toIso8601String(),
                    'delivered_at' => $record->delivered_at?->toIso8601String(),
                    'read_at' => $record->read_at?->toIso8601String(),
                    'error_message' => $record->error_message,
                    'created_at' => $record->created_at->toIso8601String(),
                    'updated_at' => $record->updated_at->toIso8601String(),
                ],
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get notification history', [
                'user_id' => $userId,
                'history_id' => $historyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'History record not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
