<?php

declare(strict_types=1);

namespace Nexus\Sales\Services\Traits;

use Nexus\Sales\Enums\PaymentTerm;
use ValueError;

/**
 * Trait for resolving payment terms from various input formats.
 */
trait ResolvesPaymentTerm
{
    /**
     * Resolve payment term from various input formats.
     *
     * @param mixed $paymentTerm Payment term value (PaymentTerm enum, string, or null)
     * @return PaymentTerm
     * @throws \InvalidArgumentException If payment term value is invalid
     */
    private function resolvePaymentTerm(mixed $paymentTerm): PaymentTerm
    {
        if ($paymentTerm instanceof PaymentTerm) {
            return $paymentTerm;
        }

        if (is_string($paymentTerm)) {
            try {
                return PaymentTerm::from($paymentTerm);
            } catch (ValueError $e) {
                throw new \InvalidArgumentException(
                    "Invalid payment term value: '{$paymentTerm}'. Valid values are: " .
                    implode(', ', array_map(fn($case) => $case->value, PaymentTerm::cases()))
                );
            }
        }

        return PaymentTerm::NET_30;
    }
}
