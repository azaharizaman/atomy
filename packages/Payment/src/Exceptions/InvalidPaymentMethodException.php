<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Payment\Enums\PaymentMethodType;

/**
 * Thrown when a payment method is invalid or unsupported.
 */
final class InvalidPaymentMethodException extends PaymentException
{
    public function __construct(
        string $message,
        ?PaymentMethodType $methodType = null,
        ?string $methodId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            400,
            $previous,
            array_filter([
                'method_type' => $methodType?->value,
                'method_id' => $methodId,
            ]),
        );
    }

    /**
     * Create for an unsupported payment method type.
     */
    public static function unsupportedType(PaymentMethodType $type): self
    {
        return new self(
            sprintf('Payment method type "%s" is not supported', $type->value),
            $type,
        );
    }

    /**
     * Create for a disabled payment method.
     */
    public static function disabled(string $methodId, PaymentMethodType $type): self
    {
        return new self(
            sprintf('Payment method "%s" of type "%s" is disabled', $methodId, $type->value),
            $type,
            $methodId,
        );
    }

    /**
     * Create for an expired payment method.
     */
    public static function expired(string $methodId): self
    {
        return new self(
            sprintf('Payment method "%s" has expired', $methodId),
            null,
            $methodId,
        );
    }

    /**
     * Create for failed verification.
     */
    public static function verificationFailed(string $methodId, string $reason): self
    {
        return new self(
            sprintf('Payment method "%s" verification failed: %s', $methodId, $reason),
            null,
            $methodId,
        );
    }
}
