<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for Blanket Purchase Order operations.
 */
final readonly class BlanketPOResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $blanketPoId Created/updated blanket PO ID
     * @param string|null $blanketPoNumber Human-readable blanket PO number
     * @param int $currentSpendCents Current cumulative spend in cents
     * @param int $remainingCents Remaining budget in cents
     * @param int $percentUtilized Percentage of budget utilized
     * @param string|null $status Blanket PO status
     * @param string|null $errorMessage Error message if failed
     * @param array<string, mixed> $metadata Additional response metadata
     */
    public function __construct(
        public bool $success,
        public ?string $blanketPoId = null,
        public ?string $blanketPoNumber = null,
        public int $currentSpendCents = 0,
        public int $remainingCents = 0,
        public int $percentUtilized = 0,
        public ?string $status = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a success result for blanket PO creation.
     */
    public static function created(
        string $blanketPoId,
        string $blanketPoNumber,
        int $maxAmountCents,
        string $status = 'ACTIVE'
    ): self {
        return new self(
            success: true,
            blanketPoId: $blanketPoId,
            blanketPoNumber: $blanketPoNumber,
            currentSpendCents: 0,
            remainingCents: $maxAmountCents,
            percentUtilized: 0,
            status: $status,
        );
    }

    /**
     * Create a success result with current spend status.
     */
    public static function withSpendStatus(
        string $blanketPoId,
        string $blanketPoNumber,
        int $maxAmountCents,
        int $currentSpendCents,
        string $status
    ): self {
        $remaining = max(0, $maxAmountCents - $currentSpendCents);
        $percent = $maxAmountCents > 0 
            ? (int) (($currentSpendCents * 100) / $maxAmountCents)
            : 0;

        return new self(
            success: true,
            blanketPoId: $blanketPoId,
            blanketPoNumber: $blanketPoNumber,
            currentSpendCents: $currentSpendCents,
            remainingCents: $remaining,
            percentUtilized: $percent,
            status: $status,
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $errorMessage): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Check if the blanket PO is approaching its spend limit.
     *
     * @param int $warningThresholdPercent Percentage to consider as approaching limit
     */
    public function isApproachingLimit(int $warningThresholdPercent = 80): bool
    {
        return $this->percentUtilized >= $warningThresholdPercent;
    }

    /**
     * Check if the blanket PO has exceeded its spend limit.
     */
    public function isOverLimit(): bool
    {
        return $this->percentUtilized >= 100;
    }
}
