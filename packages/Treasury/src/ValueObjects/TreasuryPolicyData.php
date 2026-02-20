<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

/**
 * Treasury Policy Data Value Object
 */
final readonly class TreasuryPolicyData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $minimumCashBalance,
        public string $minimumCashBalanceCurrency,
        public float $maximumSingleTransaction,
        public string $maximumSingleTransactionCurrency,
        public bool $approvalRequired,
        public float $approvalThreshold,
        public string $approvalThresholdCurrency,
    ) {
    }

    public static function fromArray(array $data): self
    {
        // Validate required fields
        $requiredFields = [
            'name',
            'minimum_cash_balance',
            'minimum_cash_balance_currency',
            'maximum_single_transaction',
            'maximum_single_transaction_currency',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '') {
                throw new \InvalidArgumentException(
                    sprintf('Missing required field: %s', $field)
                );
            }
        }

        // Validate numeric fields are positive numbers
        $numericFields = [
            'minimum_cash_balance' => 'minimum_cash_balance',
            'maximum_single_transaction' => 'maximum_single_transaction',
        ];

        foreach ($numericFields as $field => $label) {
            $value = (float) $data[$field];
            if ($value < 0) {
                throw new \InvalidArgumentException(
                    sprintf('The field %s must be a positive number, got: %s', $label, $value)
                );
            }
        }

        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            minimumCashBalance: (float) $data['minimum_cash_balance'],
            minimumCashBalanceCurrency: $data['minimum_cash_balance_currency'],
            maximumSingleTransaction: (float) $data['maximum_single_transaction'],
            maximumSingleTransactionCurrency: $data['maximum_single_transaction_currency'],
            approvalRequired: (bool) ($data['approval_required'] ?? true),
            approvalThreshold: (float) ($data['approval_threshold'] ?? 0),
            approvalThresholdCurrency: $data['approval_threshold_currency'] ?? $data['minimum_cash_balance_currency'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'minimum_cash_balance' => $this->minimumCashBalance,
            'minimum_cash_balance_currency' => $this->minimumCashBalanceCurrency,
            'maximum_single_transaction' => $this->maximumSingleTransaction,
            'maximum_single_transaction_currency' => $this->maximumSingleTransactionCurrency,
            'approval_required' => $this->approvalRequired,
            'approval_threshold' => $this->approvalThreshold,
            'approval_threshold_currency' => $this->approvalThresholdCurrency,
        ];
    }
}
