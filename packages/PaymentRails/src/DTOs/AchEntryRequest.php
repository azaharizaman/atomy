<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\TransactionCode;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Request DTO for a single ACH entry.
 */
final readonly class AchEntryRequest
{
    /**
     * @param RoutingNumber $receivingDfi Receiving bank routing number
     * @param string $accountNumber Account number (max 17 chars)
     * @param AccountType $accountType Account type (checking/savings)
     * @param Money $amount Transaction amount
     * @param string $receiverName Receiver name (max 22 chars)
     * @param string $receiverId Receiver identification (max 15 chars)
     * @param TransactionCode|null $transactionCode Specific transaction code (auto-determined if null)
     * @param bool $isDebit Whether this is a debit (pull) transaction
     * @param bool $isPrenote Whether this is a prenote (zero-dollar validation)
     * @param string|null $addendaRecord Optional addenda information
     * @param string|null $externalId External reference for tracking
     */
    public function __construct(
        public RoutingNumber $receivingDfi,
        public string $accountNumber,
        public AccountType $accountType,
        public Money $amount,
        public string $receiverName,
        public string $receiverId,
        public ?TransactionCode $transactionCode = null,
        public bool $isDebit = false,
        public bool $isPrenote = false,
        public ?string $addendaRecord = null,
        public ?string $externalId = null,
    ) {}

    /**
     * Create a credit entry request.
     */
    public static function credit(
        RoutingNumber $receivingDfi,
        string $accountNumber,
        AccountType $accountType,
        Money $amount,
        string $receiverName,
        string $receiverId = '',
        ?string $addendaRecord = null,
    ): self {
        return new self(
            receivingDfi: $receivingDfi,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: $amount,
            receiverName: $receiverName,
            receiverId: $receiverId,
            isDebit: false,
            addendaRecord: $addendaRecord,
        );
    }

    /**
     * Create a debit entry request.
     */
    public static function debit(
        RoutingNumber $receivingDfi,
        string $accountNumber,
        AccountType $accountType,
        Money $amount,
        string $receiverName,
        string $receiverId = '',
        ?string $addendaRecord = null,
    ): self {
        return new self(
            receivingDfi: $receivingDfi,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: $amount,
            receiverName: $receiverName,
            receiverId: $receiverId,
            isDebit: true,
            addendaRecord: $addendaRecord,
        );
    }

    /**
     * Create a prenote request.
     */
    public static function prenote(
        RoutingNumber $receivingDfi,
        string $accountNumber,
        AccountType $accountType,
        string $receiverName,
        bool $isDebit = false,
    ): self {
        return new self(
            receivingDfi: $receivingDfi,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: Money::zero('USD'),
            receiverName: $receiverName,
            receiverId: '',
            isDebit: $isDebit,
            isPrenote: true,
        );
    }

    /**
     * Determine the appropriate transaction code.
     */
    public function getTransactionCode(): TransactionCode
    {
        if ($this->transactionCode !== null) {
            return $this->transactionCode;
        }

        if ($this->isPrenote) {
            return match ($this->accountType) {
                AccountType::CHECKING => $this->isDebit
                    ? TransactionCode::PRENOTIFICATION_DEBIT_CHECKING
                    : TransactionCode::PRENOTIFICATION_CREDIT_CHECKING,
                AccountType::SAVINGS => $this->isDebit
                    ? TransactionCode::PRENOTIFICATION_DEBIT_SAVINGS
                    : TransactionCode::PRENOTIFICATION_CREDIT_SAVINGS,
                default => TransactionCode::PRENOTIFICATION_CREDIT_CHECKING,
            };
        }

        return match ($this->accountType) {
            AccountType::CHECKING => $this->isDebit
                ? TransactionCode::DEBIT_CHECKING
                : TransactionCode::CREDIT_CHECKING,
            AccountType::SAVINGS => $this->isDebit
                ? TransactionCode::DEBIT_SAVINGS
                : TransactionCode::CREDIT_SAVINGS,
            default => $this->isDebit
                ? TransactionCode::DEBIT_CHECKING
                : TransactionCode::CREDIT_CHECKING,
        };
    }

    /**
     * Check if this entry has an addenda record.
     */
    public function hasAddenda(): bool
    {
        return $this->addendaRecord !== null && $this->addendaRecord !== '';
    }

    /**
     * Validate the entry request.
     *
     * @return array<string> Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if (mb_strlen($this->accountNumber) > 17) {
            $errors[] = 'Account number must not exceed 17 characters';
        }

        if (empty($this->accountNumber)) {
            $errors[] = 'Account number is required';
        }

        if (mb_strlen($this->receiverName) > 22) {
            $errors[] = 'Receiver name must not exceed 22 characters';
        }

        if (empty($this->receiverName)) {
            $errors[] = 'Receiver name is required';
        }

        if (mb_strlen($this->receiverId) > 15) {
            $errors[] = 'Receiver ID must not exceed 15 characters';
        }

        if (!$this->isPrenote && $this->amount->isZero()) {
            $errors[] = 'Amount must be greater than zero for non-prenote entries';
        }

        if ($this->amount->isNegative()) {
            $errors[] = 'Amount cannot be negative';
        }

        if ($this->hasAddenda() && mb_strlen($this->addendaRecord) > 80) {
            $errors[] = 'Addenda record must not exceed 80 characters';
        }

        return $errors;
    }
}
