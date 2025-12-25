<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

/**
 * Represents an error from a payment gateway.
 */
final class GatewayError
{
    /**
     * @param string $code Error code (gateway-specific or normalized)
     * @param string $message Human-readable error message
     * @param string|null $declineCode Card decline code (if applicable)
     * @param bool $retryable Whether the operation can be retried
     * @param string|null $type Error type/category
     * @param string|null $param Parameter that caused the error (if applicable)
     * @param array<string, mixed> $details Additional error details
     */
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $declineCode = null,
        public readonly bool $retryable = false,
        public readonly ?string $type = null,
        public readonly ?string $param = null,
        public readonly array $details = [],
    ) {}

    /**
     * Create from gateway response.
     *
     * @param array<string, mixed> $response Gateway error response
     */
    public static function fromArray(array $response): self
    {
        return new self(
            code: (string) ($response['code'] ?? $response['error_code'] ?? 'unknown_error'),
            message: (string) ($response['message'] ?? $response['error_message'] ?? 'An unknown error occurred'),
            declineCode: $response['decline_code'] ?? $response['declineCode'] ?? null,
            retryable: (bool) ($response['retryable'] ?? false),
            type: $response['type'] ?? $response['error_type'] ?? null,
            param: $response['param'] ?? null,
            details: $response['details'] ?? $response,
        );
    }

    /**
     * Create a card declined error.
     */
    public static function cardDeclined(?string $declineCode = null, ?string $message = null): self
    {
        return new self(
            code: 'card_declined',
            message: $message ?? 'Card was declined',
            declineCode: $declineCode,
            retryable: false,
            type: 'card_error',
        );
    }

    /**
     * Create an expired card error.
     */
    public static function expiredCard(): self
    {
        return new self(
            code: 'expired_card',
            message: 'Card has expired',
            declineCode: 'expired_card',
            retryable: false,
            type: 'card_error',
        );
    }

    /**
     * Create an insufficient funds error.
     */
    public static function insufficientFunds(): self
    {
        return new self(
            code: 'insufficient_funds',
            message: 'Insufficient funds available',
            declineCode: 'insufficient_funds',
            retryable: false,
            type: 'card_error',
        );
    }

    /**
     * Create a network error (retryable).
     */
    public static function networkError(string $message = 'Network error communicating with gateway'): self
    {
        return new self(
            code: 'network_error',
            message: $message,
            retryable: true,
            type: 'api_error',
        );
    }

    /**
     * Create an authentication error.
     */
    public static function authenticationError(string $message = 'Invalid API credentials'): self
    {
        return new self(
            code: 'authentication_error',
            message: $message,
            retryable: false,
            type: 'authentication_error',
        );
    }

    /**
     * Check if error is related to card decline.
     */
    public function isCardError(): bool
    {
        return $this->type === 'card_error' || $this->declineCode !== null;
    }

    /**
     * Check if error is related to invalid parameters.
     */
    public function isValidationError(): bool
    {
        return $this->type === 'validation_error' || $this->param !== null;
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        if ($this->declineCode !== null) {
            return match ($this->declineCode) {
                'insufficient_funds' => 'Your card has insufficient funds.',
                'expired_card' => 'Your card has expired.',
                'incorrect_cvc' => 'The security code is incorrect.',
                'card_declined' => 'Your card was declined.',
                'lost_card', 'stolen_card' => 'This card cannot be used.',
                default => 'Your payment was declined. Please try a different payment method.',
            };
        }

        return 'An error occurred processing your payment. Please try again.';
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'decline_code' => $this->declineCode,
            'retryable' => $this->retryable,
            'type' => $this->type,
            'param' => $this->param,
            'details' => $this->details,
        ];
    }
}
