<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Payment\Enums\AllocationMethod;

/**
 * Thrown when payment allocation fails.
 */
final class AllocationException extends PaymentException
{
    public function __construct(
        string $message,
        ?AllocationMethod $method = null,
        ?string $paymentId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            400,
            $previous,
            array_filter([
                'allocation_method' => $method?->value,
                'payment_id' => $paymentId,
            ]),
        );
    }

    /**
     * Create for no allocatable documents.
     */
    public static function noDocuments(string $paymentId): self
    {
        return new self(
            'No allocatable documents found for payment',
            null,
            $paymentId,
        );
    }

    /**
     * Create for currency mismatch.
     */
    public static function currencyMismatch(string $paymentCurrency, string $documentCurrency, ?string $paymentId = null): self
    {
        return new self(
            sprintf(
                'Currency mismatch: payment is in %s but document is in %s',
                $paymentCurrency,
                $documentCurrency,
            ),
            null,
            $paymentId,
        );
    }

    /**
     * Create for manual allocation with missing specifications.
     */
    public static function manualAllocationRequired(): self
    {
        return new self(
            'Manual allocation method requires explicit allocation specifications',
            AllocationMethod::MANUAL,
        );
    }
}
