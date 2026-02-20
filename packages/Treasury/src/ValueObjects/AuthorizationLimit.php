<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

/**
 * Authorization Limit Value Object
 */
final readonly class AuthorizationLimit
{
    public function __construct(
        public ?string $userId,
        public ?string $roleId,
        public float $amount,
        public string $currency,
        public ?string $transactionType,
    ) {
    }

    public static function fromArray(array $data): self
    {
        // Validate required fields: amount
        if (!isset($data['amount'])) {
            throw new \InvalidArgumentException('The "amount" field is required.');
        }

        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \InvalidArgumentException('The "amount" must be a positive number.');
        }

        // Validate required fields: currency
        if (!isset($data['currency']) || empty($data['currency'])) {
            throw new \InvalidArgumentException('The "currency" field is required.');
        }

        // Validate that either user_id or role_id is provided
        $userId = $data['user_id'] ?? null;
        $roleId = $data['role_id'] ?? null;

        if ($userId === null && $roleId === null) {
            throw new \InvalidArgumentException('Either "user_id" or "role_id" must be provided.');
        }

        return new self(
            userId: $userId,
            roleId: $roleId,
            amount: $amount,
            currency: $data['currency'],
            transactionType: $data['transaction_type'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'role_id' => $this->roleId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_type' => $this->transactionType,
        ];
    }
}
