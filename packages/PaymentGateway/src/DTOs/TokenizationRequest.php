<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\DTOs;

/**
 * Request to tokenize a payment method.
 */
final readonly class TokenizationRequest
{
    /**
     * @param string $cardNumber Full card number (for server-side tokenization only)
     * @param int $expiryMonth Card expiry month (1-12)
     * @param int $expiryYear Card expiry year (4-digit)
     * @param string $cvv Card security code
     * @param string|null $cardholderName Name on card
     * @param string|null $customerId Gateway customer ID to attach token to
     * @param array<string, string> $billingAddress Billing address
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $cardNumber,
        public int $expiryMonth,
        public int $expiryYear,
        public string $cvv,
        public ?string $cardholderName = null,
        public ?string $customerId = null,
        public array $billingAddress = [],
        public array $metadata = [],
    ) {}

    /**
     * Create from card details.
     *
     * SECURITY: This should only be used for server-side tokenization
     * in PCI DSS compliant environments. Prefer client-side tokenization
     * using gateway SDKs (Stripe.js, PayPal SDK, etc.).
     */
    public static function fromCard(
        string $cardNumber,
        int $expiryMonth,
        int $expiryYear,
        string $cvv,
        ?string $cardholderName = null,
        ?string $customerId = null,
    ): self {
        return new self(
            cardNumber: preg_replace('/\s+/', '', $cardNumber) ?? $cardNumber,
            expiryMonth: $expiryMonth,
            expiryYear: $expiryYear,
            cvv: $cvv,
            cardholderName: $cardholderName,
            customerId: $customerId,
        );
    }

    /**
     * Get last 4 digits of card number.
     */
    public function getLastFour(): string
    {
        return substr($this->cardNumber, -4);
    }

    /**
     * Get masked card number.
     */
    public function getMaskedNumber(): string
    {
        $length = strlen($this->cardNumber);
        if ($length < 8) {
            return str_repeat('*', $length);
        }

        return substr($this->cardNumber, 0, 4)
            . str_repeat('*', $length - 8)
            . substr($this->cardNumber, -4);
    }

    /**
     * Check if card is expired.
     */
    public function isExpired(): bool
    {
        $now = new \DateTimeImmutable();
        $currentYear = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        if ($this->expiryYear < $currentYear) {
            return true;
        }

        if ($this->expiryYear === $currentYear && $this->expiryMonth < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Check if this should be attached to a customer.
     */
    public function shouldAttachToCustomer(): bool
    {
        return $this->customerId !== null;
    }
}
