<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Types of wire transfers.
 *
 * Wire transfers can be domestic (within a country) or international
 * (cross-border via SWIFT network).
 */
enum WireType: string
{
    /**
     * Domestic wire transfer within the same country.
     * Uses local clearing system (e.g., Fedwire in US).
     */
    case DOMESTIC = 'domestic';

    /**
     * International wire transfer via SWIFT network.
     * Uses SWIFT MT messages for cross-border payments.
     */
    case INTERNATIONAL = 'international';

    /**
     * Book transfer within same financial institution.
     * No external clearing required - internal ledger movement.
     */
    case BOOK_TRANSFER = 'book_transfer';

    /**
     * Drawdown wire - Receiver-initiated.
     * The receiving bank initiates the wire on behalf of the receiver.
     */
    case DRAWDOWN = 'drawdown';

    /**
     * Get a human-readable label for the wire type.
     */
    public function label(): string
    {
        return match ($this) {
            self::DOMESTIC => 'Domestic Wire',
            self::INTERNATIONAL => 'International Wire (SWIFT)',
            self::BOOK_TRANSFER => 'Book Transfer',
            self::DRAWDOWN => 'Drawdown Wire',
        };
    }

    /**
     * Check if this wire type requires SWIFT/BIC code.
     */
    public function requiresSwiftCode(): bool
    {
        return match ($this) {
            self::INTERNATIONAL => true,
            self::DOMESTIC,
            self::BOOK_TRANSFER,
            self::DRAWDOWN => false,
        };
    }

    /**
     * Check if this wire type requires IBAN.
     */
    public function mayRequireIban(): bool
    {
        return match ($this) {
            self::INTERNATIONAL => true,
            self::DOMESTIC,
            self::BOOK_TRANSFER,
            self::DRAWDOWN => false,
        };
    }

    /**
     * Check if this wire type requires intermediary bank.
     */
    public function mayRequireIntermediaryBank(): bool
    {
        return match ($this) {
            self::INTERNATIONAL => true,
            self::DOMESTIC,
            self::BOOK_TRANSFER,
            self::DRAWDOWN => false,
        };
    }

    /**
     * Check if this wire type is typically same-day settlement.
     */
    public function isSameDay(): bool
    {
        return match ($this) {
            self::DOMESTIC,
            self::BOOK_TRANSFER,
            self::DRAWDOWN => true,
            self::INTERNATIONAL => false,
        };
    }

    /**
     * Get typical processing time description.
     */
    public function processingTime(): string
    {
        return match ($this) {
            self::DOMESTIC => 'Same day (before cutoff)',
            self::INTERNATIONAL => '1-5 business days',
            self::BOOK_TRANSFER => 'Immediate',
            self::DRAWDOWN => 'Same day (before cutoff)',
        };
    }

    /**
     * Get the SWIFT message type used for this wire type.
     */
    public function swiftMessageType(): ?string
    {
        return match ($this) {
            self::INTERNATIONAL => 'MT103',
            self::DOMESTIC,
            self::BOOK_TRANSFER,
            self::DRAWDOWN => null,
        };
    }
}
