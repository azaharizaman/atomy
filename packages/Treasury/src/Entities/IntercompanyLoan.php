<?php

declare(strict_types=1);

namespace Nexus\Treasury\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\IntercompanyTreasuryInterface;

final readonly class IntercompanyLoan implements IntercompanyTreasuryInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $fromEntityId,
        private string $toEntityId,
        private string $loanType,
        private Money $principalAmount,
        private float $interestRate,
        private Money $outstandingBalance,
        private DateTimeImmutable $startDate,
        private ?DateTimeImmutable $maturityDate,
        private Money $accruedInterest,
        private array $paymentSchedule,
        private ?string $referenceNumber,
        private ?string $notes,
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

    public function getFromEntityId(): string
    {
        return $this->fromEntityId;
    }

    public function getToEntityId(): string
    {
        return $this->toEntityId;
    }

    public function getLoanType(): string
    {
        return $this->loanType;
    }

    public function getPrincipalAmount(): Money
    {
        return $this->principalAmount;
    }

    public function getInterestRate(): float
    {
        return $this->interestRate;
    }

    public function getOutstandingBalance(): Money
    {
        return $this->outstandingBalance;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getMaturityDate(): ?DateTimeImmutable
    {
        return $this->maturityDate;
    }

    public function getAccruedInterest(): Money
    {
        return $this->accruedInterest;
    }

    public function getPaymentSchedule(): array
    {
        return $this->paymentSchedule;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
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
        return !$this->outstandingBalance->isZero();
    }

    public function isOverdue(): bool
    {
        if ($this->maturityDate === null) {
            return false;
        }

        return new DateTimeImmutable() > $this->maturityDate && !$this->outstandingBalance->isZero();
    }

    public function getDaysOutstanding(): int
    {
        return (int) $this->startDate->diff(new DateTimeImmutable())->days;
    }
}
