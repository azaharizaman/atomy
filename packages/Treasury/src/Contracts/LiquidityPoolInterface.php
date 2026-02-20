<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Enums\TreasuryStatus;

interface LiquidityPoolInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getName(): string;

    public function getDescription(): ?string;

    public function getCurrency(): string;

    public function getTotalBalance(): Money;

    public function getAvailableBalance(): Money;

    public function getReservedBalance(): Money;

    public function getStatus(): TreasuryStatus;

    public function getBankAccountIds(): array;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;

    public function isActive(): bool;

    public function hasSufficientLiquidity(Money $amount): bool;
}
