<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\ValueObjects\PostingResult;

/**
 * Subledger Posting Interface
 * 
 * Interface for subledger packages (Receivable, Payable, Assets) to post
 * transactions to the general ledger. This provides a standardized way
 * for subledgers to integrate with the GL.
 */
interface SubledgerPostingInterface
{
    /**
     * Post a single transaction from a subledger to the GL
     * 
     * @param SubledgerPostingRequest $request The posting request
     * @return PostingResult Result of the posting operation
     */
    public function postToLedger(SubledgerPostingRequest $request): PostingResult;

    /**
     * Post multiple transactions in a batch
     * 
     * @param array<SubledgerPostingRequest> $requests Array of posting requests
     * @return array<PostingResult> Results for each posting
     */
    public function postBatch(array $requests): array;

    /**
     * Validate a posting request before attempting to post
     * 
     * @param SubledgerPostingRequest $request The posting request
     * @return ValidationResult Validation result
     */
    public function validatePosting(SubledgerPostingRequest $request): ValidationResult;

    /**
     * Get total amounts posted by a subledger for a period
     * 
     * Used for reconciliation and verification.
     * 
     * @param string $subledgerId Subledger identifier (e.g., customer ID, vendor ID)
     * @param string $periodId Period ULID
     * @returnotal_de array{tbits: AccountBalance, total_credits: AccountBalance}
     */
    public function getPostedAmounts(string $subledgerId, string $periodId): array;

    /**
     * Reverse a posting from a subledger
     * 
     * @param string $originalTransactionId Original transaction ULID
     * @param string $reason Reason for reversal
     * @return PostingResult Result of the reversal
     */
    public function reversePosting(string $originalTransactionId, string $reason): PostingResult;

    /**
     * Check if a subledger has posted transactions in a period
     * 
     * @param string $subledgerId Subledger identifier
     * @param string $periodId Period ULID
     * @return bool True if has posted transactions
     */
    public function hasPostedTransactions(string $subledgerId, string $periodId): bool;
}

/**
 * Subledger Posting Request
 * 
 * Value object representing a request to post from a subledger to GL.
 */
final readonly class SubledgerPostingRequest
{
    /**
     * @param string $subledgerId Subledger identifier (customer ID, vendor ID, asset ID)
     * @param string $subledgerType Type of subledger (RECEIVABLE, PAYABLE, ASSET)
     * @param string $ledgerAccountId Target GL account ULID
     * @param TransactionType $type Transaction type (DEBIT or CREDIT)
     * @param AccountBalance $amount Amount to post
     * @param string $periodId Period ULID
     * @param \DateTimeImmutable $postingDate Date to post
     * @param string $sourceDocumentId Source document ID (invoice, bill, etc.)
     * @param string $sourceDocumentLineId Source document line ID
     * @param string|null $description Description
     * @param string|null $reference External reference
     */
    public function __construct(
        public string $subledgerId,
        public string $subledgerType,
        public string $ledgerAccountId,
        public TransactionType $type,
        public AccountBalance $amount,
        public string $periodId,
        public \DateTimeImmutable $postingDate,
        public string $sourceDocumentId,
        public string $sourceDocumentLineId,
        public ?string $description = null,
        public ?string $reference = null,
    ) {}

    /**
     * Get the currency from the amount
     */
    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }
}

/**
 * Validation Result
 * 
 * Value object representing the result of validation.
 */
final readonly class ValidationResult
{
    /**
     * @param bool $isValid Whether validation passed
     * @param array<string> $errors Validation errors
     * @param array<string, mixed> $warnings Validation warnings
     */
    private function __construct(
        public bool $isValid,
        public array $errors,
        public array $warnings,
    ) {}

    /**
     * Create a successful validation result
     */
    public static function valid(array $warnings = []): self
    {
        return new self(isValid: true, errors: [], warnings: $warnings);
    }

    /**
     * Create a failed validation result
     */
    public static function invalid(array $errors, array $warnings = []): self
    {
        return new self(isValid: false, errors: $errors, warnings: $warnings);
    }
}
