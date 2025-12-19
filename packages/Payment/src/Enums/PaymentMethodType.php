<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Payment method type classification.
 *
 * Categorizes the type of payment method/instrument used for a transaction.
 * Extension packages may add additional method types via adapters.
 */
enum PaymentMethodType: string
{
    /**
     * Credit or debit card payment.
     */
    case CARD = 'card';

    /**
     * Bank account (ACH, direct debit, wire transfer).
     */
    case BANK_ACCOUNT = 'bank_account';

    /**
     * Digital wallet (Apple Pay, Google Pay, PayPal, GrabPay, etc.).
     */
    case WALLET = 'wallet';

    /**
     * Physical cash payment.
     */
    case CASH = 'cash';

    /**
     * Physical check/cheque payment.
     */
    case CHECK = 'check';

    /**
     * Wire transfer.
     */
    case WIRE = 'wire';

    /**
     * Virtual card payment.
     */
    case VIRTUAL_CARD = 'virtual_card';

    /**
     * Buy Now Pay Later (BNPL) payment.
     */
    case BNPL = 'bnpl';

    /**
     * Cryptocurrency payment.
     */
    case CRYPTO = 'crypto';

    /**
     * Other/custom payment method.
     */
    case OTHER = 'other';

    /**
     * Check if this method type is electronic (not physical).
     */
    public function isElectronic(): bool
    {
        return match ($this) {
            self::CARD, self::BANK_ACCOUNT, self::WALLET, 
            self::WIRE, self::VIRTUAL_CARD, self::BNPL, self::CRYPTO => true,
            self::CASH, self::CHECK, self::OTHER => false,
        };
    }

    /**
     * Check if this method type requires tokenization.
     */
    public function requiresTokenization(): bool
    {
        return match ($this) {
            self::CARD, self::WALLET, self::VIRTUAL_CARD => true,
            default => false,
        };
    }

    /**
     * Check if this method type supports refunds.
     */
    public function supportsRefund(): bool
    {
        return match ($this) {
            self::CARD, self::BANK_ACCOUNT, self::WALLET, 
            self::WIRE, self::VIRTUAL_CARD, self::BNPL => true,
            self::CASH, self::CHECK, self::CRYPTO, self::OTHER => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Card',
            self::BANK_ACCOUNT => 'Bank Account',
            self::WALLET => 'Digital Wallet',
            self::CASH => 'Cash',
            self::CHECK => 'Check',
            self::WIRE => 'Wire Transfer',
            self::VIRTUAL_CARD => 'Virtual Card',
            self::BNPL => 'Buy Now Pay Later',
            self::CRYPTO => 'Cryptocurrency',
            self::OTHER => 'Other',
        };
    }
}
