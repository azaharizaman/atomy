<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Enums\InvestmentStatus;
use Nexus\Treasury\Enums\InvestmentType;

interface InvestmentInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getInvestmentType(): InvestmentType;

    public function getName(): string;

    public function getDescription(): ?string;

    public function getPrincipalAmount(): Money;

    public function getInterestRate(): float;

    public function getMaturityDate(): DateTimeImmutable;

    public function getInvestmentDate(): DateTimeImmutable;

    public function getStatus(): InvestmentStatus;

    public function getMaturityAmount(): Money;

    public function getAccruedInterest(): Money;

    public function getBankAccountId(): string;

    public function getReferenceNumber(): ?string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function isActive(): bool;

    public function isMatured(): bool;

    public function isPending(): bool;

    public function getDaysToMaturity(): int;

    public function getDurationDays(): int;
}
