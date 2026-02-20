<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

interface TreasuryPositionInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getEntityId(): ?string;

    public function getPositionDate(): DateTimeImmutable;

    public function getTotalCashBalance(): Money;

    public function getAvailableCashBalance(): Money;

    public function getReservedCashBalance(): Money;

    public function getInvestedCashBalance(): Money;

    public function getProjectedInflows(): Money;

    public function getProjectedOutflows(): Money;

    public function getNetCashFlow(): Money;

    public function getCurrency(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function getNetPosition(): Money;

    public function hasSufficientLiquidity(Money $amount): bool;
}
