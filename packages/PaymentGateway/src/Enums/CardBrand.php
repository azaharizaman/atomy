<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Supported card brands/networks.
 */
enum CardBrand: string
{
    case VISA = 'visa';
    case MASTERCARD = 'mastercard';
    case AMEX = 'amex';
    case DISCOVER = 'discover';
    case DINERS = 'diners';
    case JCB = 'jcb';
    case UNIONPAY = 'unionpay';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::VISA => 'Visa',
            self::MASTERCARD => 'Mastercard',
            self::AMEX => 'American Express',
            self::DISCOVER => 'Discover',
            self::DINERS => 'Diners Club',
            self::JCB => 'JCB',
            self::UNIONPAY => 'UnionPay',
            self::UNKNOWN => 'Unknown',
        };
    }

    /**
     * Get CVV length for the card brand.
     */
    public function cvvLength(): int
    {
        return match ($this) {
            self::AMEX => 4,
            default => 3,
        };
    }

    /**
     * Create from string value (case-insensitive).
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'visa' => self::VISA,
            'mastercard', 'mc' => self::MASTERCARD,
            'amex', 'american_express', 'american express' => self::AMEX,
            'discover' => self::DISCOVER,
            'diners', 'diners_club', 'diners club' => self::DINERS,
            'jcb' => self::JCB,
            'unionpay', 'union_pay', 'cup' => self::UNIONPAY,
            default => self::UNKNOWN,
        };
    }
}
