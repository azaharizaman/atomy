<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when payment is declined.
 */
class PaymentDeclinedException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $declineCode,
        public readonly ?string $declineReason = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function create(string $declineCode, ?string $reason = null): self
    {
        $message = "Payment declined: {$declineCode}";
        
        if ($reason !== null) {
            $message .= " - {$reason}";
        }

        return new self(
            message: $message,
            declineCode: $declineCode,
            declineReason: $reason
        );
    }

    /**
     * Create exception for declined payment.
     */
    public static function declined(string $reason, string $code = 'DECLINED'): self
    {
        return new self(
            message: "Payment declined: {$reason}",
            declineCode: $code,
            declineReason: $reason
        );
    }
}
