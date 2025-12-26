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

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'authorizationId' => $this->authorizationId,
            'transactionId' => $this->transactionId,
            'status' => $this->status->value,
            'authorizedAmount' => $this->authorizedAmount?->toArray(),
            'expiresAt' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
            'error' => $this->error?->toArray(),
            'avsResult' => $this->avsResult,
            'cvvResult' => $this->cvvResult,
            'requires3ds' => $this->requires3ds,
            'threeDsUrl' => $this->threeDsUrl,
            'rawResponse' => $this->rawResponse,
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            authorizationId: $data['authorizationId'] ?? $data['authorization_id'] ?? null,
            transactionId: $data['transactionId'] ?? $data['transaction_id'] ?? null,
            status: TransactionStatus::tryFrom($data['status'] ?? '') ?? TransactionStatus::PENDING,
            authorizedAmount: isset($data['authorizedAmount']) ? Money::fromArray($data['authorizedAmount']) : (isset($data['authorized_amount']) ? Money::fromArray($data['authorized_amount']) : null),
            expiresAt: isset($data['expiresAt']) ? new \DateTimeImmutable($data['expiresAt']) : (isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null),
            error: isset($data['error']) ? GatewayError::fromArray($data['error']) : null,
            avsResult: $data['avsResult'] ?? $data['avs_result'] ?? null,
            cvvResult: $data['cvvResult'] ?? $data['cvv_result'] ?? null,
            requires3ds: (bool) ($data['requires3ds'] ?? $data['requires_3ds'] ?? false),
            threeDsUrl: $data['threeDsUrl'] ?? $data['three_ds_url'] ?? null,
            rawResponse: $data['rawResponse'] ?? $data['raw_response'] ?? [],
        );
    }
}
