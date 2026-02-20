<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

interface AuthorizationMatrixInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getUserId(): ?string;

    public function getRoleId(): ?string;

    public function getTransactionType(): string;

    public function getApprovalLimit(): Money;

    public function getDailyLimit(): ?Money;

    public function getWeeklyLimit(): ?Money;

    public function getMonthlyLimit(): ?Money;

    public function getRequiresDualApproval(): bool;

    public function getEffectiveFrom(): DateTimeImmutable;

    public function getEffectiveTo(): ?DateTimeImmutable;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function isActive(): bool;

    public function isEffective(DateTimeImmutable $date): bool;

    public function canAuthorize(Money $amount): bool;
}
