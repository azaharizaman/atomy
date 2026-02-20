<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use InvalidArgumentException;

final readonly class CashSweepInstruction
{
    public function __construct(
        public string $sourceAccountId,
        public string $targetAccountId,
        public Money $sweepThreshold,
        public Money $sweepAmount,
        public bool $retainMinimum = true,
        public ?Money $retainAmount = null,
    ) {
        if ($sweepThreshold->getCurrency() !== $sweepAmount->getCurrency()) {
            throw new InvalidArgumentException('Sweep threshold and amount must be in the same currency');
        }

        if ($retainAmount !== null && $retainAmount->getCurrency() !== $sweepThreshold->getCurrency()) {
            throw new InvalidArgumentException('Retain amount must be in the same currency as sweep threshold');
        }
    }

    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? 'USD';

        return new self(
            sourceAccountId: $data['source_account_id'] ?? $data['sourceAccountId'],
            targetAccountId: $data['target_account_id'] ?? $data['targetAccountId'],
            sweepThreshold: Money::of($data['sweep_threshold'] ?? $data['sweepThreshold'], $currency),
            sweepAmount: Money::of($data['sweep_amount'] ?? $data['sweepAmount'], $currency),
            retainMinimum: $data['retain_minimum'] ?? $data['retainMinimum'] ?? true,
            retainAmount: isset($data['retain_amount'])
                ? Money::of($data['retain_amount'], $currency)
                : (isset($data['retainAmount'])
                    ? Money::of($data['retainAmount'], $currency)
                    : null),
        );
    }

    public function toArray(): array
    {
        return [
            'sourceAccountId' => $this->sourceAccountId,
            'targetAccountId' => $this->targetAccountId,
            'sweepThreshold' => $this->sweepThreshold->toArray(),
            'sweepAmount' => $this->sweepAmount->toArray(),
            'retainMinimum' => $this->retainMinimum,
            'retainAmount' => $this->retainAmount?->toArray(),
        ];
    }

    public function getCurrency(): string
    {
        return $this->sweepThreshold->getCurrency();
    }

    public function isFullSweep(): bool
    {
        return !$this->retainMinimum || $this->retainAmount === null || $this->retainAmount->isZero();
    }
}
