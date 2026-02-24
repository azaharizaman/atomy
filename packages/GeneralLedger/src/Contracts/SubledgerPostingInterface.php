<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\ValueObjects\PostingResult;
use Nexus\GeneralLedger\ValueObjects\SubledgerPostingRequest;
use Nexus\GeneralLedger\ValueObjects\ValidationResult;

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
     * @return array{total_debits: AccountBalance, total_credits: AccountBalance}
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
