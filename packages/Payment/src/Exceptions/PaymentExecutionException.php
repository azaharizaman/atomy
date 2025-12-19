<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when payment execution fails.
 */
final class PaymentExecutionException extends PaymentException
{
    public function __construct(
        string $message,
        ?string $paymentId = null,
        ?string $providerCode = null,
        ?string $providerMessage = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            500,
            $previous,
            array_filter([
                'payment_id' => $paymentId,
                'provider_code' => $providerCode,
                'provider_message' => $providerMessage,
            ]),
        );
    }

    /**
     * Create for a timeout error.
     */
    public static function timeout(string $paymentId, int $timeoutSeconds): self
    {
        return new self(
            sprintf('Payment execution timed out after %d seconds', $timeoutSeconds),
            $paymentId,
        );
    }

    /**
     * Create for a provider error.
     */
    public static function providerError(
        string $paymentId,
        string $providerCode,
        string $providerMessage,
    ): self {
        return new self(
            sprintf('Payment provider error [%s]: %s', $providerCode, $providerMessage),
            $paymentId,
            $providerCode,
            $providerMessage,
        );
    }

    /**
     * Create for a network error.
     */
    public static function networkError(string $paymentId, string $message): self
    {
        return new self(
            sprintf('Network error during payment execution: %s', $message),
            $paymentId,
        );
    }
}
