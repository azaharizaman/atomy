<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

interface IntercompanyTreasuryInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getFromEntityId(): string;

    public function getToEntityId(): string;

    public function getLoanType(): string;

    public function getPrincipalAmount(): Money;

    public function getInterestRate(): float;

    public function getOutstandingBalance(): Money;

    public function getStartDate(): DateTimeImmutable;

    public function getMaturityDate(): ?DateTimeImmutable;

    public function getAccruedInterest(): Money;

    public function getPaymentSchedule(): array;

    public function getReferenceNumber(): ?string;

    public function getNotes(): ?string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function isActive(): bool;

    public function isOverdue(): bool;

    public function getDaysOutstanding(): int;
}
