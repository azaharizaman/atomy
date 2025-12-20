<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookEventType;

/**
 * Parsed webhook payload from a gateway.
 */
final class WebhookPayload
{
    /**
     * @param string $eventId Unique event identifier
     * @param WebhookEventType $eventType Type of event
     * @param GatewayProvider $provider Gateway that sent the webhook
     * @param string $resourceId Associated resource ID (payment, refund, etc.)
     * @param string|null $resourceType Type of resource
     * @param array<string, mixed> $data Event data
     * @param \DateTimeImmutable $receivedAt When webhook was received
     * @param string|null $signature Original webhook signature
     * @param bool $verified Whether signature was verified
     */
    public function __construct(
        public readonly string $eventId,
        public readonly WebhookEventType $eventType,
        public readonly GatewayProvider $provider,
        public readonly string $resourceId,
        public readonly ?string $resourceType = null,
        public readonly array $data = [],
        public readonly \DateTimeImmutable $receivedAt = new \DateTimeImmutable(),
        public readonly ?string $signature = null,
        public readonly bool $verified = false,
    ) {}

    /**
     * Create from Stripe webhook event.
     *
     * @param array<string, mixed> $event Stripe event data
     */
    public static function fromStripe(array $event, ?string $signature = null, bool $verified = false): self
    {
        $type = self::mapStripeEventType($event['type'] ?? '');
        $object = $event['data']['object'] ?? [];

        return new self(
            eventId: $event['id'] ?? '',
            eventType: $type,
            provider: GatewayProvider::STRIPE,
            resourceId: $object['id'] ?? '',
            resourceType: $object['object'] ?? null,
            data: $object,
            signature: $signature,
            verified: $verified,
        );
    }

    /**
     * Create from PayPal webhook event.
     *
     * @param array<string, mixed> $event PayPal event data
     */
    public static function fromPayPal(array $event, ?string $signature = null, bool $verified = false): self
    {
        $type = self::mapPayPalEventType($event['event_type'] ?? '');
        $resource = $event['resource'] ?? [];

        return new self(
            eventId: $event['id'] ?? '',
            eventType: $type,
            provider: GatewayProvider::PAYPAL,
            resourceId: $resource['id'] ?? '',
            resourceType: $event['resource_type'] ?? null,
            data: $resource,
            signature: $signature,
            verified: $verified,
        );
    }

    /**
     * Map Stripe event type to internal enum.
     */
    private static function mapStripeEventType(string $stripeType): WebhookEventType
    {
        return match ($stripeType) {
            'payment_intent.created' => WebhookEventType::PAYMENT_CREATED,
            'payment_intent.succeeded', 'charge.captured' => WebhookEventType::PAYMENT_CAPTURED,
            'payment_intent.payment_failed' => WebhookEventType::PAYMENT_FAILED,
            'payment_intent.canceled' => WebhookEventType::PAYMENT_CANCELED,
            'charge.refunded' => WebhookEventType::REFUND_COMPLETED,
            'charge.refund.updated' => WebhookEventType::REFUND_COMPLETED,
            'charge.dispute.created' => WebhookEventType::DISPUTE_CREATED,
            'charge.dispute.won' => WebhookEventType::DISPUTE_WON,
            'charge.dispute.lost' => WebhookEventType::DISPUTE_LOST,
            'charge.dispute.closed' => WebhookEventType::DISPUTE_CLOSED,
            'customer.created' => WebhookEventType::CUSTOMER_CREATED,
            'customer.updated' => WebhookEventType::CUSTOMER_UPDATED,
            'customer.deleted' => WebhookEventType::CUSTOMER_DELETED,
            'payout.created' => WebhookEventType::PAYOUT_CREATED,
            'payout.paid' => WebhookEventType::PAYOUT_COMPLETED,
            'payout.failed' => WebhookEventType::PAYOUT_FAILED,
            default => WebhookEventType::PAYMENT_CREATED,
        };
    }

    /**
     * Map PayPal event type to internal enum.
     */
    private static function mapPayPalEventType(string $paypalType): WebhookEventType
    {
        return match ($paypalType) {
            'CHECKOUT.ORDER.APPROVED' => WebhookEventType::PAYMENT_AUTHORIZED,
            'PAYMENT.CAPTURE.COMPLETED' => WebhookEventType::PAYMENT_CAPTURED,
            'PAYMENT.CAPTURE.DENIED' => WebhookEventType::PAYMENT_FAILED,
            'PAYMENT.CAPTURE.REFUNDED' => WebhookEventType::REFUND_COMPLETED,
            'CUSTOMER.DISPUTE.CREATED' => WebhookEventType::DISPUTE_CREATED,
            'CUSTOMER.DISPUTE.RESOLVED' => WebhookEventType::DISPUTE_CLOSED,
            default => WebhookEventType::PAYMENT_CREATED,
        };
    }

    /**
     * Get a value from the data array.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if this is a payment event.
     */
    public function isPaymentEvent(): bool
    {
        return $this->eventType->isPaymentEvent();
    }

    /**
     * Check if this is a refund event.
     */
    public function isRefundEvent(): bool
    {
        return $this->eventType->isRefundEvent();
    }

    /**
     * Check if this is a dispute event.
     */
    public function isDisputeEvent(): bool
    {
        return $this->eventType->isDisputeEvent();
    }
}
