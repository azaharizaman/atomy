<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle SendGrid webhook for email delivery status.
     */
    public function sendgrid(Request $request): JsonResponse
    {
        try {
            $events = $request->json()->all();

            foreach ($events as $event) {
                $this->processSendGridEvent($event);
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to process SendGrid webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle Twilio webhook for SMS delivery status.
     */
    public function twilio(Request $request): JsonResponse
    {
        try {
            $status = $request->input('MessageStatus');
            $messageSid = $request->input('MessageSid');

            $this->updateDeliveryStatus(
                externalId: $messageSid,
                status: $this->mapTwilioStatus($status),
                channel: 'sms'
            );

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to process Twilio webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle FCM webhook for push notification delivery status.
     */
    public function fcm(Request $request): JsonResponse
    {
        try {
            $event = $request->json()->all();
            $messageId = $event['message_id'] ?? null;
            $status = $event['status'] ?? null;

            if ($messageId && $status) {
                $this->updateDeliveryStatus(
                    externalId: $messageId,
                    status: $this->mapFcmStatus($status),
                    channel: 'push'
                );
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to process FCM webhook', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Process a single SendGrid event.
     */
    private function processSendGridEvent(array $event): void
    {
        $eventType = $event['event'] ?? null;
        $messageId = $event['sg_message_id'] ?? null;

        if (!$messageId || !$eventType) {
            return;
        }

        $status = match ($eventType) {
            'delivered' => DeliveryStatus::Delivered,
            'bounce' => DeliveryStatus::Bounced,
            'dropped' => DeliveryStatus::Failed,
            'deferred' => DeliveryStatus::Pending,
            'open' => DeliveryStatus::Read,
            default => null,
        };

        if ($status) {
            $this->updateDeliveryStatus(
                externalId: $messageId,
                status: $status,
                channel: 'email'
            );
        }
    }

    /**
     * Map Twilio status to DeliveryStatus.
     */
    private function mapTwilioStatus(string $status): DeliveryStatus
    {
        return match ($status) {
            'delivered' => DeliveryStatus::Delivered,
            'sent' => DeliveryStatus::Sent,
            'failed', 'undelivered' => DeliveryStatus::Failed,
            default => DeliveryStatus::Pending,
        };
    }

    /**
     * Map FCM status to DeliveryStatus.
     */
    private function mapFcmStatus(string $status): DeliveryStatus
    {
        return match ($status) {
            'delivered' => DeliveryStatus::Delivered,
            'sent' => DeliveryStatus::Sent,
            'failed' => DeliveryStatus::Failed,
            default => DeliveryStatus::Pending,
        };
    }

    /**
     * Update delivery status in notification history.
     */
    private function updateDeliveryStatus(
        string $externalId,
        DeliveryStatus $status,
        string $channel
    ): void {
        $record = NotificationHistory::where('external_id', $externalId)
            ->where('channel', $channel)
            ->first();

        if (!$record) {
            $this->logger->warning('Notification history not found for external ID', [
                'external_id' => $externalId,
                'channel' => $channel,
            ]);
            return;
        }

        $updateData = ['status' => $status->value];

        if ($status === DeliveryStatus::Delivered && !$record->delivered_at) {
            $updateData['delivered_at'] = now();
        }

        if ($status === DeliveryStatus::Read && !$record->read_at) {
            $updateData['read_at'] = now();
        }

        $record->update($updateData);

        $this->logger->info('Delivery status updated', [
            'external_id' => $externalId,
            'channel' => $channel,
            'status' => $status->value,
        ]);
    }
}
