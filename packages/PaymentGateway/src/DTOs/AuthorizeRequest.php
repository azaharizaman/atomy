<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\AuthorizationType;

/**
 * Request to authorize a payment.
 */
final readonly class AuthorizeRequest
{
    /**
     * @param Money $amount Amount to authorize
     * @param string $paymentMethodToken Token representing payment method
     * @param string|null $customerId Gateway customer ID (for saved cards)
     * @param AuthorizationType $authorizationType Type of authorization
     * @param string|null $description Payment description
     * @param string|null $statementDescriptor Statement descriptor (what appears on statement)
     * @param string|null $orderId Order/invoice reference
     * @param string|null $customerEmail Customer email for receipts
     * @param string|null $customerIp Customer IP address (for fraud prevention)
     * @param bool $capture Whether to auto-capture (auth+capture)
     * @param array<string, string> $billingAddress Billing address
     * @param array<string, string> $shippingAddress Shipping address
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $idempotencyKey Idempotency key for safe retries
     */
    public function __construct(
        public Money $amount,
        public string $paymentMethodToken,
        public ?string $customerId = null,
        public AuthorizationType $authorizationType = AuthorizationType::PREAUTH,
        public ?string $description = null,
        public ?string $statementDescriptor = null,
        public ?string $orderId = null,
        public ?string $customerEmail = null,
        public ?string $customerIp = null,
        public bool $capture = false,
        public array $billingAddress = [],
        public array $shippingAddress = [],
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Create for immediate capture (auth+capture).
     */
    public static function forCapture(
        Money $amount,
        string $paymentMethodToken,
        ?string $customerId = null,
        ?string $description = null,
        array $metadata = [],
    ): self {
        return new self(
            amount: $amount,
            paymentMethodToken: $paymentMethodToken,
            customerId: $customerId,
            authorizationType: AuthorizationType::AUTH_CAPTURE,
            description: $description,
            capture: true,
            metadata: $metadata,
        );
    }

    /**
     * Create for pre-authorization only.
     */
    public static function forPreAuth(
        Money $amount,
        string $paymentMethodToken,
        ?string $customerId = null,
        ?string $description = null,
        array $metadata = [],
    ): self {
        return new self(
            amount: $amount,
            paymentMethodToken: $paymentMethodToken,
            customerId: $customerId,
            authorizationType: AuthorizationType::PREAUTH,
            description: $description,
            capture: false,
            metadata: $metadata,
        );
    }

    /**
     * Check if this is an auto-capture request.
     */
    public function isAutoCapture(): bool
    {
        return $this->capture || $this->authorizationType->isAutoCapture();
    }

    /**
     * Get amount in smallest currency unit.
     */
    public function getAmountInMinorUnits(): int
    {
        return $this->amount->getAmountInMinorUnits();
    }

    /**
     * Get currency code.
     */
    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }
}
