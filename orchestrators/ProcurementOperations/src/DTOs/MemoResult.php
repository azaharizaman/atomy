<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\MemoStatus;
use Nexus\ProcurementOperations\Enums\MemoType;

/**
 * Result DTO for memo creation/update operations.
 */
final readonly class MemoResult
{
    private function __construct(
        public bool $success,
        public ?string $memoId = null,
        public ?string $memoNumber = null,
        public ?MemoType $type = null,
        public ?MemoStatus $status = null,
        public ?Money $totalAmount = null,
        public ?Money $appliedAmount = null,
        public ?Money $remainingAmount = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
    ) {}

    /**
     * Create successful result.
     */
    public static function success(
        string $memoId,
        string $memoNumber,
        MemoType $type,
        MemoStatus $status,
        Money $totalAmount,
        Money $appliedAmount,
        Money $remainingAmount
    ): self {
        return new self(
            success: true,
            memoId: $memoId,
            memoNumber: $memoNumber,
            type: $type,
            status: $status,
            totalAmount: $totalAmount,
            appliedAmount: $appliedAmount,
            remainingAmount: $remainingAmount
        );
    }

    /**
     * Create failure result.
     */
    public static function failure(string $message, ?string $code = null): self
    {
        return new self(
            success: false,
            errorMessage: $message,
            errorCode: $code
        );
    }

    /**
     * Check if memo is fully applied.
     */
    public function isFullyApplied(): bool
    {
        if (!$this->success || $this->remainingAmount === null) {
            return false;
        }

        return $this->remainingAmount->isZero();
    }

    /**
     * Check if memo has been partially applied.
     */
    public function isPartiallyApplied(): bool
    {
        if (!$this->success || $this->appliedAmount === null || $this->remainingAmount === null) {
            return false;
        }

        return !$this->appliedAmount->isZero() && !$this->remainingAmount->isZero();
    }
}
