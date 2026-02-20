<?php

declare(strict_types=1);

namespace Nexus\Treasury\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\InvestmentInterface;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;

final readonly class Investment implements InvestmentInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private InvestmentType $investmentType,
        private string $name,
        private ?string $description,
        private Money $principalAmount,
        private float $interestRate,
        private DateTimeImmutable $maturityDate,
        private DateTimeImmutable $investmentDate,
        private InvestmentStatus $status,
        private Money $maturityAmount,
        private Money $accruedInterest,
        private string $bankAccountId,
        private ?string $referenceNumber,
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

    public function getInvestmentType(): InvestmentType
    {
        return $this->investmentType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrincipalAmount(): Money
    {
        return $this->principalAmount;
    }

    public function getInterestRate(): float
    {
        return $this->interestRate;
    }

    public function getMaturityDate(): DateTimeImmutable
    {
        return $this->maturityDate;
    }

    public function getInvestmentDate(): DateTimeImmutable
    {
        return $this->investmentDate;
    }

    public function getStatus(): InvestmentStatus
    {
        return $this->status;
    }

    public function getMaturityAmount(): Money
    {
        return $this->maturityAmount;
    }

    public function getAccruedInterest(): Money
    {
        return $this->accruedInterest;
    }

    public function getBankAccountId(): string
    {
        return $this->bankAccountId;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
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
        return $this->status === InvestmentStatus::ACTIVE;
    }

    public function isMatured(): bool
    {
        return $this->status === InvestmentStatus::MATURED;
    }

    public function isPending(): bool
    {
        return $this->status === InvestmentStatus::PENDING;
    }

    public function getDaysToMaturity(): int
    {
        $now = new DateTimeImmutable();
        if ($now >= $this->maturityDate) {
            return 0;
        }

        return (int) $now->diff($this->maturityDate)->days;
    }

    public function getDurationDays(): int
    {
        return (int) $this->investmentDate->diff($this->maturityDate)->days;
    }
}
