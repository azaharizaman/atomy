<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Webhook event types that can be received from gateways.
 */
enum WebhookEventType: string
{
    // Payment lifecycle events
    case PAYMENT_CREATED = 'payment.created';
    case PAYMENT_AUTHORIZED = 'payment.authorized';
    case PAYMENT_CAPTURED = 'payment.captured';
    case PAYMENT_FAILED = 'payment.failed';
    case PAYMENT_CANCELED = 'payment.canceled';

    // Refund events
    case REFUND_CREATED = 'refund.created';
    case REFUND_COMPLETED = 'refund.completed';
    case REFUND_FAILED = 'refund.failed';

    // Dispute/Chargeback events
    case DISPUTE_CREATED = 'dispute.created';
    case DISPUTE_WON = 'dispute.won';
    case DISPUTE_LOST = 'dispute.lost';
    case DISPUTE_CLOSED = 'dispute.closed';

    // Token events
    case TOKEN_CREATED = 'token.created';
    case TOKEN_EXPIRED = 'token.expired';
    case TOKEN_DELETED = 'token.deleted';

    // Customer events
    case CUSTOMER_CREATED = 'customer.created';
    case CUSTOMER_UPDATED = 'customer.updated';
    case CUSTOMER_DELETED = 'customer.deleted';

    // Payout events
    case PAYOUT_CREATED = 'payout.created';
    case PAYOUT_COMPLETED = 'payout.completed';
    case PAYOUT_FAILED = 'payout.failed';

    // Unknown event
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PAYMENT_CREATED => 'Payment Created',
            self::PAYMENT_AUTHORIZED => 'Payment Authorized',
            self::PAYMENT_CAPTURED => 'Payment Captured',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::PAYMENT_CANCELED => 'Payment Canceled',
            self::REFUND_CREATED => 'Refund Created',
            self::REFUND_COMPLETED => 'Refund Completed',
            self::REFUND_FAILED => 'Refund Failed',
            self::DISPUTE_CREATED => 'Dispute Created',
            self::DISPUTE_WON => 'Dispute Won',
            self::DISPUTE_LOST => 'Dispute Lost',
            self::DISPUTE_CLOSED => 'Dispute Closed',
            self::TOKEN_CREATED => 'Token Created',
            self::TOKEN_EXPIRED => 'Token Expired',
            self::TOKEN_DELETED => 'Token Deleted',
            self::CUSTOMER_CREATED => 'Customer Created',
            self::CUSTOMER_UPDATED => 'Customer Updated',
            self::CUSTOMER_DELETED => 'Customer Deleted',
            self::PAYOUT_CREATED => 'Payout Created',
            self::PAYOUT_COMPLETED => 'Payout Completed',
            self::PAYOUT_FAILED => 'Payout Failed',
            self::UNKNOWN => 'Unknown Event',
        };
    }

    /**
     * Check if event is payment-related.
     */
    public function isPaymentEvent(): bool
    {
        return in_array($this, [
            self::PAYMENT_CREATED,
            self::PAYMENT_AUTHORIZED,
            self::PAYMENT_CAPTURED,
            self::PAYMENT_FAILED,
            self::PAYMENT_CANCELED,
        ], true);
    }

    /**
     * Check if event is refund-related.
     */
    public function isRefundEvent(): bool
    {
        return in_array($this, [
            self::REFUND_CREATED,
            self::REFUND_COMPLETED,
            self::REFUND_FAILED,
        ], true);
    }

    /**
     * Check if event is dispute-related.
     */
    public function isDisputeEvent(): bool
    {
        return in_array($this, [
            self::DISPUTE_CREATED,
            self::DISPUTE_WON,
            self::DISPUTE_LOST,
            self::DISPUTE_CLOSED,
        ], true);
    }
}
