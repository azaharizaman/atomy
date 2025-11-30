<?php

declare(strict_types=1);

namespace Nexus\Payable\Enums;

/**
 * Payment terms enum.
 */
enum PaymentTerm: string
{
    case NET_15 = 'net_15';
    case NET_30 = 'net_30';
    case NET_45 = 'net_45';
    case NET_60 = 'net_60';
    case NET_90 = 'net_90';
    case DUE_ON_RECEIPT = 'due_on_receipt';
    case DISCOUNT_2_10_NET_30 = '2/10_net_30'; // 2% discount if paid within 10 days, else net 30
    case DISCOUNT_1_10_NET_30 = '1/10_net_30';
    case CUSTOM = 'custom';

    /**
     * Get due date based on bill date.
     */
    public function calculateDueDate(\DateTimeInterface $billDate): \DateTimeInterface
    {
        $dueDate = \DateTime::createFromInterface($billDate);

        return match($this) {
            self::NET_15 => $dueDate->modify('+15 days'),
            self::NET_30 => $dueDate->modify('+30 days'),
            self::NET_45 => $dueDate->modify('+45 days'),
            self::NET_60 => $dueDate->modify('+60 days'),
            self::NET_90 => $dueDate->modify('+90 days'),
            self::DUE_ON_RECEIPT => $dueDate,
            self::DISCOUNT_2_10_NET_30, self::DISCOUNT_1_10_NET_30 => $dueDate->modify('+30 days'),
            self::CUSTOM => $dueDate, // Caller must override
        };
    }

    /**
     * Get early payment discount information.
     *
     * @return array{percent: float, days: int}|null
     */
    public function getEarlyPaymentDiscount(): ?array
    {
        return match($this) {
            self::DISCOUNT_2_10_NET_30 => ['percent' => 2.0, 'days' => 10],
            self::DISCOUNT_1_10_NET_30 => ['percent' => 1.0, 'days' => 10],
            default => null,
        };
    }
}
