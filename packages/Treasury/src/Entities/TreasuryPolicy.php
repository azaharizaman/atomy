<?php

declare(strict_types=1);

namespace Nexus\Treasury\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\TreasuryPolicyInterface;
use Nexus\Treasury\Enums\TreasuryStatus;

final readonly class TreasuryPolicy implements TreasuryPolicyInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $name,
        private Money $minimumCashBalance,
        private Money $maximumSingleTransaction,
        private Money $approvalThreshold,
        private bool $approvalRequired,
        private TreasuryStatus $status,
        private DateTimeImmutable $effectiveFrom,
        private ?DateTimeImmutable $effectiveTo,
        private ?string $description,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

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

    public function getMinimumCashBalance(): Money
    {
        return $this->minimumCashBalance;
    }

    public function getMaximumSingleTransaction(): Money
    {
        return $this->maximumSingleTransaction;
    }

    public function getApprovalThreshold(): Money
    {
        return $this->approvalThreshold;
    }

    public function isApprovalRequired(): bool
    {
        return $this->approvalRequired;
    }

    public function getStatus(): TreasuryStatus
    {
        return $this->status;
    }

    public function getEffectiveFrom(): DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getEffectiveTo(): ?DateTimeImmutable
    {
        return $this->effectiveTo;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === TreasuryStatus::ACTIVE;
    }

    public function isEffective(DateTimeImmutable $date): bool
    {
        if ($date < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $date > $this->effectiveTo) {
            return false;
        }

        return $this->isActive();
    }
}
