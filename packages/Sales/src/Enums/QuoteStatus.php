<?php

declare(strict_types=1);

namespace Nexus\Sales\Enums;

/**
 * Quote status enum.
 * 
 * Tracks the lifecycle of a sales quotation from draft to conversion.
 */
enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CONVERTED_TO_ORDER = 'converted_to_order';

    /**
     * Check if quote can be sent to customer.
     */
    public function canBeSent(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if quote can be converted to order.
     */
    public function canBeConverted(): bool
    {
        return $this === self::ACCEPTED;
    }

    /**
     * Check if quote is in a final state.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::REJECTED, self::EXPIRED, self::CONVERTED_TO_ORDER => true,
            default => false,
        };
    }

    /**
     * Check if status transition is valid.
     */
    public function canTransitionTo(QuoteStatus $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::SENT, self::REJECTED], true),
            self::SENT => in_array($newStatus, [self::ACCEPTED, self::REJECTED, self::EXPIRED], true),
            self::ACCEPTED => $newStatus === self::CONVERTED_TO_ORDER,
            self::REJECTED, self::EXPIRED, self::CONVERTED_TO_ORDER => false,
        };
    }
}
