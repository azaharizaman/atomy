<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Enums\TreasuryStatus;

interface TreasuryPolicyInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getName(): string;

    public function getDescription(): ?string;

    public function getMinimumCashBalance(): Money;

    public function getMaximumSingleTransaction(): Money;

    public function getApprovalThreshold(): Money;

    public function isApprovalRequired(): bool;

    public function getStatus(): TreasuryStatus;

    public function getEffectiveFrom(): DateTimeImmutable;

    public function getEffectiveTo(): ?DateTimeImmutable;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function isActive(): bool;

    public function isEffective(DateTimeImmutable $date): bool;
}
