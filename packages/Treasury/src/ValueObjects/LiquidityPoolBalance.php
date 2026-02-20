<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class LiquidityPoolBalance
{
    public function __construct(
        public string $poolId,
        public Money $totalBalance,
        public Money $availableBalance,
        public Money $reservedBalance,
        public DateTimeImmutable $calculatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        $poolId = $data['pool_id'] ?? $data['poolId'] ?? null;
        if ($poolId === null || $poolId === '') {
            throw new InvalidArgumentException("Missing required field 'pool_id' in LiquidityPoolBalance::fromArray");
        }

        $totalBalanceData = $data['total_balance'] ?? $data['totalBalance'] ?? null;
        if ($totalBalanceData === null) {
            throw new InvalidArgumentException("Missing required field 'total_balance' in LiquidityPoolBalance::fromArray");
        }

        $availableBalanceData = $data['available_balance'] ?? $data['availableBalance'] ?? null;
        if ($availableBalanceData === null) {
            throw new InvalidArgumentException("Missing required field 'available_balance' in LiquidityPoolBalance::fromArray");
        }

        $reservedBalanceData = $data['reserved_balance'] ?? $data['reservedBalance'] ?? null;
        if ($reservedBalanceData === null) {
            throw new InvalidArgumentException("Missing required field 'reserved_balance' in LiquidityPoolBalance::fromArray");
        }

        $currency = $data['currency'] ?? null;

        return new self(
            poolId: $poolId,
            totalBalance: is_array($totalBalanceData) ? Money::fromArray($totalBalanceData) : Money::of($totalBalanceData, $currency ?? 'USD'),
            availableBalance: is_array($availableBalanceData) ? Money::fromArray($availableBalanceData) : Money::of($availableBalanceData, $currency ?? 'USD'),
            reservedBalance: is_array($reservedBalanceData) ? Money::fromArray($reservedBalanceData) : Money::of($reservedBalanceData, $currency ?? 'USD'),
            calculatedAt: new DateTimeImmutable($data['calculated_at'] ?? $data['calculatedAt'] ?? 'now'),
        );
    }

    public function toArray(): array
    {
        return [
            'poolId' => $this->poolId,
            'totalBalance' => $this->totalBalance->toArray(),
            'availableBalance' => $this->availableBalance->toArray(),
            'reservedBalance' => $this->reservedBalance->toArray(),
            'calculatedAt' => $this->calculatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getCurrency(): string
    {
        return $this->totalBalance->getCurrency();
    }

    public function utilizationPercentage(): float
    {
        if ($this->totalBalance->isZero()) {
            return 0.0;
        }

        $reserved = $this->reservedBalance->getAmount();
        $total = $this->totalBalance->getAmount();

        return ($reserved / $total) * 100;
    }
}
