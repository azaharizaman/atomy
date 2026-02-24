<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\ValueObjects;

use Nexus\GeneralLedger\Enums\SubledgerType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\Enums\BalanceType;
use Nexus\GeneralLedger\Exceptions\InvalidPostingException;

/**
 * Subledger Posting Request
 * 
 * Value object representing a request to post from a subledger to GL.
 */
final readonly class SubledgerPostingRequest
{
    /**
     * @param string $subledgerId Subledger identifier (customer ID, vendor ID, asset ID)
     * @param SubledgerType $subledgerType Type of subledger (RECEIVABLE, PAYABLE, ASSET)
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
        public SubledgerType $subledgerType,
        public string $ledgerAccountId,
        public TransactionType $type,
        public AccountBalance $amount,
        public string $periodId,
        public \DateTimeImmutable $postingDate,
        public string $sourceDocumentId,
        public string $sourceDocumentLineId,
        public ?string $description = null,
        public ?string $reference = null,
    ) {
        // Validate that TransactionType aligns with AccountBalance balance type
        $balanceType = $this->amount->balanceType;
        if ($this->type === TransactionType::DEBIT && $balanceType !== BalanceType::DEBIT) {
            throw new InvalidPostingException(
                'Debit subledger posting must have a debit-typed AccountBalance'
            );
        }
        if ($this->type === TransactionType::CREDIT && $balanceType !== BalanceType::CREDIT) {
            throw new InvalidPostingException(
                'Credit subledger posting must have a credit-typed AccountBalance'
            );
        }
    }

    /**
     * Get the currency from the amount
     */
    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }
}
