<?php

declare(strict_types=1);

namespace Nexus\Treasury\Models;

use Nexus\Treasury\Contracts\TreasuryPolicyInterface;
use Nexus\Treasury\Enums\TreasuryStatus;

final readonly class TreasuryPolicy implements TreasuryPolicyInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $name,
        private ?string $description,
        private TreasuryStatus $status,
        private float $minimumCashBalance,
        private string $minimumCashBalanceCurrency,
        private float $maximumSingleTransaction,
        private string $maximumSingleTransactionCurrency,
        private bool $approvalRequired,
        private float $approvalThreshold,
        private string $approvalThresholdCurrency,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): TreasuryStatus
    {
        return $this->status;
    }

    public function getMinimumCashBalance(): float
    {
        return $this->minimumCashBalance;
    }

    public function getMinimumCashBalanceCurrency(): string
    {
        return $this->minimumCashBalanceCurrency;
    }

    public function getMaximumSingleTransaction(): float
    {
        return $this->maximumSingleTransaction;
    }

    public function getMaximumSingleTransactionCurrency(): string
    {
        return $this->maximumSingleTransactionCurrency;
    }

    public function isApprovalRequired(): bool
    {
        return $this->approvalRequired;
    }

    public function getApprovalThreshold(): float
    {
        return $this->approvalThreshold;
    }

    public function getApprovalThresholdCurrency(): string
    {
        return $this->approvalThresholdCurrency;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withStatus(TreasuryStatus $status): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->name,
            $this->description,
            $status,
            $this->minimumCashBalance,
            $this->minimumCashBalanceCurrency,
            $this->maximumSingleTransaction,
            $this->maximumSingleTransactionCurrency,
            $this->approvalRequired,
            $this->approvalThreshold,
            $this->approvalThresholdCurrency,
            $this->createdAt,
            new \DateTimeImmutable(),
        );
    }
}
