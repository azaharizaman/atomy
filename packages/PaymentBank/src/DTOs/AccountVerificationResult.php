<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\PaymentBank\Enums\VerificationStatus;

final readonly class AccountVerificationResult
{
    /**
     * @param string $accountId Account being verified
     * @param VerificationStatus $status Current verification status
     * @param string|null $verificationId Verification process identifier (if applicable)
     * @param string|null $ownerName Account owner's name (if retrieved)
     * @param string|null $ownerAddress Account owner's address (if retrieved)
     * @param array<string, mixed> $metadata Additional verification metadata
     */
    public function __construct(
        private string $accountId,
        private VerificationStatus $status,
        private ?string $verificationId = null,
        private ?string $ownerName = null,
        private ?string $ownerAddress = null,
        private array $metadata = []
    ) {}

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getStatus(): VerificationStatus
    {
        return $this->status;
    }

    public function getVerificationId(): ?string
    {
        return $this->verificationId;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function getOwnerAddress(): ?string
    {
        return $this->ownerAddress;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isVerified(): bool
    {
        return $this->status === VerificationStatus::VERIFIED;
    }
}
