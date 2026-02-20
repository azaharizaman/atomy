<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use InvalidArgumentException;

final readonly class TreasuryPolicyData
{
    public function __construct(
        public string $name,
        public Money $minimumCashBalance,
        public Money $maximumSingleTransaction,
        public Money $approvalThreshold,
        public bool $approvalRequired = true,
        public ?string $description = null,
        public ?DateTimeImmutable $effectiveFrom = null,
        public ?DateTimeImmutable $effectiveTo = null,
    ) {
        if ($minimumCashBalance->getCurrency() !== $maximumSingleTransaction->getCurrency()) {
            throw new InvalidArgumentException('All monetary values must be in the same currency');
        }

        if ($minimumCashBalance->getCurrency() !== $approvalThreshold->getCurrency()) {
            throw new InvalidArgumentException('All monetary values must be in the same currency');
        }
    }

    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? $data['minimum_cash_balance_currency'] ?? 'USD';

        return new self(
            name: $data['name'],
            minimumCashBalance: Money::of(
                $data['minimum_cash_balance'] ?? $data['minimumCashBalance'],
                $data['minimum_cash_balance_currency'] ?? $currency
            ),
            maximumSingleTransaction: Money::of(
                $data['maximum_single_transaction'] ?? $data['maximumSingleTransaction'],
                $data['maximum_single_transaction_currency'] ?? $currency
            ),
            approvalThreshold: Money::of(
                $data['approval_threshold'] ?? $data['approvalThreshold'],
                $data['approval_threshold_currency'] ?? $currency
            ),
            approvalRequired: $data['approval_required'] ?? $data['approvalRequired'] ?? true,
            description: $data['description'] ?? null,
            effectiveFrom: isset($data['effective_from'])
                ? new DateTimeImmutable($data['effective_from'])
                : (isset($data['effectiveFrom'])
                    ? new DateTimeImmutable($data['effectiveFrom'])
                    : null),
            effectiveTo: isset($data['effective_to'])
                ? new DateTimeImmutable($data['effective_to'])
                : (isset($data['effectiveTo'])
                    ? new DateTimeImmutable($data['effectiveTo'])
                    : null),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'minimumCashBalance' => $this->minimumCashBalance->toArray(),
            'maximumSingleTransaction' => $this->maximumSingleTransaction->toArray(),
            'approvalThreshold' => $this->approvalThreshold->toArray(),
            'approvalRequired' => $this->approvalRequired,
            'description' => $this->description,
            'effectiveFrom' => $this->effectiveFrom?->format('Y-m-d'),
            'effectiveTo' => $this->effectiveTo?->format('Y-m-d'),
        ];
    }

    public function getCurrency(): string
    {
        return $this->minimumCashBalance->getCurrency();
    }
}
