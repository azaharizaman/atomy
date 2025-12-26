<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\DTOs;

use Nexus\PaymentBank\Enums\VerificationStatus;

final readonly class AccountVerificationResult
{
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
