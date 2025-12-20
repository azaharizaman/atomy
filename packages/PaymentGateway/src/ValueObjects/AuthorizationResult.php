<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\TransactionStatus;

/**
 * Result of a payment authorization request.
 */
final class AuthorizationResult
{
    /**
     * @param bool $success Whether authorization was successful
     * @param string|null $authorizationId Gateway authorization ID
     * @param string|null $transactionId Gateway transaction reference
     * @param TransactionStatus $status Authorization status
     * @param Money|null $authorizedAmount Amount that was authorized
     * @param \DateTimeImmutable|null $expiresAt When authorization expires
     * @param GatewayError|null $error Error details (if failed)
     * @param string|null $avsResult Address Verification Service result
     * @param string|null $cvvResult CVV verification result
     * @param bool $requires3ds Whether 3DS authentication is required
     * @param string|null $threeDsUrl URL for 3DS authentication (if required)
     * @param array<string, mixed> $rawResponse Raw gateway response
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $authorizationId = null,
        public readonly ?string $transactionId = null,
        public readonly TransactionStatus $status = TransactionStatus::PENDING,
        public readonly ?Money $authorizedAmount = null,
        public readonly ?\DateTimeImmutable $expiresAt = null,
        public readonly ?GatewayError $error = null,
        public readonly ?string $avsResult = null,
        public readonly ?string $cvvResult = null,
        public readonly bool $requires3ds = false,
        public readonly ?string $threeDsUrl = null,
        public readonly array $rawResponse = [],
    ) {}

    /**
     * Create successful authorization result.
     */
    public static function success(
        string $authorizationId,
        Money $amount,
        ?string $transactionId = null,
        ?\DateTimeImmutable $expiresAt = null,
        array $rawResponse = [],
    ): self {
        return new self(
            success: true,
            authorizationId: $authorizationId,
            transactionId: $transactionId,
            status: TransactionStatus::AUTHORIZED,
            authorizedAmount: $amount,
            expiresAt: $expiresAt,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create failed authorization result.
     */
    public static function failed(
        GatewayError $error,
        ?string $transactionId = null,
        array $rawResponse = [],
    ): self {
        return new self(
            success: false,
            transactionId: $transactionId,
            status: TransactionStatus::FAILED,
            error: $error,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create result requiring 3DS authentication.
     */
    public static function requires3dsAuthentication(
        string $authorizationId,
        string $threeDsUrl,
        array $rawResponse = [],
    ): self {
        return new self(
            success: false,
            authorizationId: $authorizationId,
            status: TransactionStatus::PENDING,
            requires3ds: true,
            threeDsUrl: $threeDsUrl,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Check if authorization is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if authorization can be captured.
     */
    public function canCapture(): bool
    {
        return $this->success
            && $this->status->canCapture()
            && !$this->isExpired();
    }
}
