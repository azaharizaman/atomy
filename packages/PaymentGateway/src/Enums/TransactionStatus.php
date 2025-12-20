<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Status of a gateway transaction.
 */
enum TransactionStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case PARTIALLY_CAPTURED = 'partially_captured';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case FAILED = 'failed';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::AUTHORIZED => 'Authorized',
            self::CAPTURED => 'Captured',
            self::PARTIALLY_CAPTURED => 'Partially Captured',
            self::VOIDED => 'Voided',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
            self::FAILED => 'Failed',
            self::DECLINED => 'Declined',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Check if transaction is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::CAPTURED,
            self::VOIDED,
            self::REFUNDED,
            self::FAILED,
            self::DECLINED,
            self::EXPIRED,
        ], true);
    }

    /**
     * Check if transaction can be captured.
     */
    public function canCapture(): bool
    {
        return $this === self::AUTHORIZED;
    }

    /**
     * Check if transaction can be refunded.
     */
    public function canRefund(): bool
    {
        return in_array($this, [
            self::CAPTURED,
            self::PARTIALLY_CAPTURED,
            self::PARTIALLY_REFUNDED,
        ], true);
    }

    /**
     * Check if transaction can be voided.
     */
    public function canVoid(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::AUTHORIZED,
        ], true);
    }
}
