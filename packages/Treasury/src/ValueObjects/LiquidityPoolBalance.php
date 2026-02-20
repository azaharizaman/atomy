<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use DateTimeImmutable;

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
        $currency = $data['currency'] ?? 'USD';

        return new self(
            poolId: $data['pool_id'] ?? $data['poolId'],
            totalBalance: Money::of($data['total_balance'] ?? $data['totalBalance'], $currency),
            availableBalance: Money::of($data['available_balance'] ?? $data['availableBalance'], $currency),
            reservedBalance: Money::of($data['reserved_balance'] ?? $data['reservedBalance'], $currency),
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
