<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\ValueObjects;

use Nexus\GeneralLedger\Entities\Transaction;

/**
 * Posting Result Value Object
 * 
 * Represents the result of posting a transaction to the general ledger.
 * Contains both success indicators and any error information if posting failed.
 */
final readonly class PostingResult
{
    /**
     * @param bool $success Whether the posting was successful
     * @param Transaction|null $transaction The created transaction (if successful)
     * @param string|null $errorCode Error code if posting failed
     * @param string|null $errorMessage Error message if posting failed
     * @param array<string, mixed> $metadata Additional result metadata
     */
    private function __construct(
        public bool $success,
        public ?Transaction $transaction,
        public ?string $errorCode,
        public ?string $errorMessage,
        public array $metadata,
    ) {}

    /**
     * Create a successful posting result
     */
    public static function success(Transaction $transaction, array $metadata = []): self
    {
        return new self(
            success: true,
            transaction: $transaction,
            errorCode: null,
            errorMessage: null,
            metadata: $metadata,
        );
    }

    /**
     * Create a failed posting result
     */
    public static function failure(string $errorCode, string $errorMessage, array $metadata = []): self
    {
        return new self(
            success: false,
            transaction: null,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    /**
     * Create a successful batch posting result
     */
    public static function batchSuccess(array $transactions, array $metadata = []): self
    {
        return new self(
            success: true,
            transaction: null,
            errorCode: null,
            errorMessage: null,
            metadata: array_merge($metadata, [
                'transactions' => $transactions,
                'count' => count($transactions),
            ]),
        );
    }

    /**
     * Create a failed batch posting result with partial success
     */
    public static function batchPartialSuccess(
        array $successfulTransactions,
        array $failedItems,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            transaction: null,
            errorCode: 'PARTIAL_FAILURE',
            errorMessage: sprintf(
                '%d of %d items failed to post',
                count($failedItems),
                count($successfulTransactions) + count($failedItems)
            ),
            metadata: array_merge($metadata, [
                'successful_transactions' => $successfulTransactions,
                'failed_items' => $failedItems,
                'success_count' => count($successfulTransactions),
                'failure_count' => count($failedItems),
            ]),
        );
    }

    /**
     * Check if posting was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if posting failed
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get the transaction ID if successful
     */
    public function getTransactionId(): ?string
    {
        return $this->transaction?->id ?? null;
    }

    /**
     * Get metadata value by key
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->getTransactionId(),
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }
}
