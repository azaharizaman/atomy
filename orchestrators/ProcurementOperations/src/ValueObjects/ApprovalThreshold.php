<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\ValueObjects;

/**
 * Immutable value object representing an approval threshold.
 *
 * Approval thresholds define the amount boundaries that trigger
 * different levels of approval authority.
 */
final readonly class ApprovalThreshold
{
    /**
     * @param string $code Unique threshold code (e.g., 'level_1', 'level_2')
     * @param int $maxAmountCents Maximum amount for this threshold (in cents)
     * @param string $approverLevel Human-readable approver level description
     * @param string|null $description Optional description
     * @param bool $requiresDocumentation Whether additional documentation is required
     */
    public function __construct(
        public string $code,
        public int $maxAmountCents,
        public string $approverLevel,
        public ?string $description = null,
        public bool $requiresDocumentation = false,
    ) {}

    /**
     * Create a new threshold.
     */
    public static function create(
        string $code,
        int $maxAmountCents,
        string $approverLevel,
        ?string $description = null,
        bool $requiresDocumentation = false,
    ): self {
        return new self(
            code: $code,
            maxAmountCents: $maxAmountCents,
            approverLevel: $approverLevel,
            description: $description,
            requiresDocumentation: $requiresDocumentation,
        );
    }

    /**
     * Check if an amount falls within this threshold.
     */
    public function containsAmount(int $amountCents): bool
    {
        return $amountCents <= $this->maxAmountCents;
    }

    /**
     * Check if this is an unlimited threshold.
     */
    public function isUnlimited(): bool
    {
        return $this->maxAmountCents === PHP_INT_MAX;
    }

    /**
     * Get the maximum amount formatted as currency.
     */
    public function getFormattedMaxAmount(string $currency = 'USD', int $decimals = 2): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }

        $amount = $this->maxAmountCents / 100;

        return sprintf('%s %s', $currency, number_format($amount, $decimals));
    }

    /**
     * Compare with another threshold.
     */
    public function equals(self $other): bool
    {
        return $this->code === $other->code
            && $this->maxAmountCents === $other->maxAmountCents;
    }

    /**
     * Check if this threshold is higher (allows more) than another.
     */
    public function isHigherThan(self $other): bool
    {
        return $this->maxAmountCents > $other->maxAmountCents;
    }

    /**
     * Serialize to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'max_amount_cents' => $this->maxAmountCents,
            'approver_level' => $this->approverLevel,
            'description' => $this->description,
            'requires_documentation' => $this->requiresDocumentation,
        ];
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            maxAmountCents: $data['max_amount_cents'],
            approverLevel: $data['approver_level'],
            description: $data['description'] ?? null,
            requiresDocumentation: $data['requires_documentation'] ?? false,
        );
    }
}
