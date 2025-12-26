<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Webhooks;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\ValueObjects\WebhookEvent;

/**
 * Stripe Webhook Handler.
 *
 * Handles Stripe webhook events.
 */
final class StripeWebhookHandler implements WebhookHandlerInterface
{
    public function handle(array $payload, array $headers = []): WebhookEvent
    {
        // In a real implementation, verify Stripe-Signature header here.
        // $signature = $headers['Stripe-Signature'] ?? '';
        
        $type = $payload['type'] ?? 'unknown';
        $data = $payload['data']['object'] ?? [];
        $id = $data['id'] ?? null;

        $status = match ($type) {
            'payment_intent.succeeded' => TransactionStatus::COMPLETED,
            'payment_intent.payment_failed' => TransactionStatus::FAILED,
            'payment_intent.canceled' => TransactionStatus::CANCELLED,
            'charge.refunded' => TransactionStatus::REFUNDED,
            default => TransactionStatus::PENDING,
        };

        return new WebhookEvent(
            transactionId: $id,
            status: $status,
            eventType: $type,
            rawPayload: $payload
        );
    }

    public function supports(string $provider): bool
    {
        return $provider === 'stripe';
    }
}
