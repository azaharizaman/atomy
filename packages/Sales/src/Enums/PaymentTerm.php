<?php

declare(strict_types=1);

namespace Nexus\Sales\Enums;

use DateTimeInterface;
use DateTime;

/**
 * Payment terms enum.
 * 
 * Defines standard payment terms and due date calculation.
 */
enum PaymentTerm: string
{
    case NET_15 = 'net_15';
    case NET_30 = 'net_30';
    case NET_45 = 'net_45';
    case NET_60 = 'net_60';
    case NET_90 = 'net_90';
    case DUE_ON_RECEIPT = 'due_on_receipt';
    case CUSTOM = 'custom';

    /**
     * Calculate due date based on order date.
     */
    public function calculateDueDate(DateTimeInterface $orderDate, ?int $customDays = null): DateTimeInterface
    {
        $dueDate = DateTime::createFromInterface($orderDate);

        return match ($this) {
            self::NET_15 => $dueDate->modify('+15 days'),
            self::NET_30 => $dueDate->modify('+30 days'),
            self::NET_45 => $dueDate->modify('+45 days'),
            self::NET_60 => $dueDate->modify('+60 days'),
            self::NET_90 => $dueDate->modify('+90 days'),
            self::DUE_ON_RECEIPT => $dueDate,
            self::CUSTOM => $customDays !== null 
                ? $dueDate->modify("+{$customDays} days") 
                : $dueDate,
        };
    }

    /**
     * Get the number of days for this payment term.
     */
    public function getDays(): ?int
    {
        return match ($this) {
            self::NET_15 => 15,
            self::NET_30 => 30,
            self::NET_45 => 45,
            self::NET_60 => 60,
            self::NET_90 => 90,
            self::DUE_ON_RECEIPT => 0,
            self::CUSTOM => null,
        };
    }
}
