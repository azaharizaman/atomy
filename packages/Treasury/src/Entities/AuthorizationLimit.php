<?php

declare(strict_types=1);

namespace Nexus\Treasury\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\AuthorizationMatrixInterface;

final readonly class AuthorizationLimit implements AuthorizationMatrixInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private ?string $userId,
        private ?string $roleId,
        private string $transactionType,
        private Money $approvalLimit,
        private ?Money $dailyLimit,
        private ?Money $weeklyLimit,
        private ?Money $monthlyLimit,
        private bool $requiresDualApproval,
        private DateTimeImmutable $effectiveFrom,
        private ?DateTimeImmutable $effectiveTo,
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

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    public function getApprovalLimit(): Money
    {
        return $this->approvalLimit;
    }

    public function getDailyLimit(): ?Money
    {
        return $this->dailyLimit;
    }

    public function getWeeklyLimit(): ?Money
    {
        return $this->weeklyLimit;
    }

    public function getMonthlyLimit(): ?Money
    {
        return $this->monthlyLimit;
    }

    public function getRequiresDualApproval(): bool
    {
        return $this->requiresDualApproval;
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
        return $this->isEffective(new DateTimeImmutable());
    }

    public function isEffective(DateTimeImmutable $date): bool
    {
        if ($date < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $date > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    public function canAuthorize(Money $amount): bool
    {
        if ($amount->getCurrency() !== $this->approvalLimit->getCurrency()) {
            return false;
        }

        return $amount->lessThanOrEqual($this->approvalLimit);
    }
}
