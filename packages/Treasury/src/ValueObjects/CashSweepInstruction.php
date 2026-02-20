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

        $sourceAccountId = $data['source_account_id'] ?? $data['sourceAccountId'] ?? null;
        $targetAccountId = $data['target_account_id'] ?? $data['targetAccountId'] ?? null;

        $sweepThresholdData = $data['sweep_threshold'] ?? $data['sweepThreshold'] ?? null;
        $sweepAmountData = $data['sweep_amount'] ?? $data['sweepAmount'] ?? null;

        if ($sourceAccountId === null || $sourceAccountId === '') {
            throw new InvalidArgumentException('sourceAccountId is required');
        }

        if ($targetAccountId === null || $targetAccountId === '') {
            throw new InvalidArgumentException('targetAccountId is required');
        }

        if ($sweepThresholdData === null) {
            throw new InvalidArgumentException('sweepThreshold is required');
        }

        if ($sweepAmountData === null) {
            throw new InvalidArgumentException('sweepAmount is required');
        }

        $sweepThreshold = is_array($sweepThresholdData)
            ? Money::fromArray($sweepThresholdData)
            : Money::of($sweepThresholdData, $currency);

        $sweepAmount = is_array($sweepAmountData)
            ? Money::fromArray($sweepAmountData)
            : Money::of($sweepAmountData, $currency);

        $retainAmount = null;
        $retainAmountData = $data['retain_amount'] ?? $data['retainAmount'] ?? null;
        if ($retainAmountData !== null) {
            $retainAmount = is_array($retainAmountData)
                ? Money::fromArray($retainAmountData)
                : Money::of($retainAmountData, $currency);
        }

        return new self(
            sourceAccountId: $sourceAccountId,
            targetAccountId: $targetAccountId,
            sweepThreshold: $sweepThreshold,
            sweepAmount: $sweepAmount,
            retainMinimum: $data['retain_minimum'] ?? $data['retainMinimum'] ?? true,
            retainAmount: $retainAmount,
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
