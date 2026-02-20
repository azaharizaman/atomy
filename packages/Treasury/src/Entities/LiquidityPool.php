<?php

declare(strict_types=1);

namespace Nexus\Treasury\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\LiquidityPoolInterface;
use Nexus\Treasury\Enums\TreasuryStatus;

final readonly class LiquidityPool implements LiquidityPoolInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private string $name,
        private ?string $description,
        private string $currency,
        private Money $totalBalance,
        private Money $availableBalance,
        private Money $reservedBalance,
        private TreasuryStatus $status,
        private array $bankAccountIds,
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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTotalBalance(): Money
    {
        return $this->totalBalance;
    }

    public function getAvailableBalance(): Money
    {
        return $this->availableBalance;
    }

    public function getReservedBalance(): Money
    {
        return $this->reservedBalance;
    }

    public function getStatus(): TreasuryStatus
    {
        return $this->status;
    }

    public function getBankAccountIds(): array
    {
        return $this->bankAccountIds;
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

    public function hasSufficientLiquidity(Money $amount): bool
    {
        if ($amount->getCurrency() !== $this->currency) {
            return false;
        }

        return $this->availableBalance->greaterThanOrEqual($amount);
    }
}
