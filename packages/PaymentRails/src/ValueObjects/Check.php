<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\CheckStatus;

/**
 * Represents a physical or virtual check.
 *
 * Encapsulates all information needed for check issuance,
 * printing, and tracking.
 */
final class Check
{
    /**
     * @param string $id Unique identifier for the check
     * @param CheckNumber $checkNumber The check number
     * @param Money $amount Check amount
     * @param string $payeeName Payee name (pay to the order of)
     * @param string|null $payeeAddress Payee mailing address
     * @param string $memo Check memo/description
     * @param \DateTimeImmutable $checkDate Date on the check
     * @param CheckStatus $status Current check status
     * @param string|null $bankAccountId Issuing bank account identifier
     * @param \DateTimeImmutable|null $printedAt When the check was printed
     * @param \DateTimeImmutable|null $mailedAt When the check was mailed
     * @param \DateTimeImmutable|null $clearedAt When the check cleared
     * @param \DateTimeImmutable|null $voidedAt When the check was voided
     * @param string|null $voidReason Reason for voiding
     * @param string|null $positivePayReference Positive Pay reference
     */
    public function __construct(
        public readonly string $id,
        public readonly CheckNumber $checkNumber,
        public readonly Money $amount,
        public readonly string $payeeName,
        public readonly ?string $payeeAddress,
        public readonly string $memo,
        public readonly \DateTimeImmutable $checkDate,
        public readonly CheckStatus $status = CheckStatus::PENDING,
        public readonly ?string $bankAccountId = null,
        public readonly ?\DateTimeImmutable $printedAt = null,
        public readonly ?\DateTimeImmutable $mailedAt = null,
        public readonly ?\DateTimeImmutable $clearedAt = null,
        public readonly ?\DateTimeImmutable $voidedAt = null,
        public readonly ?string $voidReason = null,
        public readonly ?string $positivePayReference = null,
    ) {}

    /**
     * Create a new pending check.
     */
    public static function create(
        string $id,
        CheckNumber $checkNumber,
        Money $amount,
        string $payeeName,
        ?string $payeeAddress = null,
        string $memo = '',
        ?\DateTimeImmutable $checkDate = null,
    ): self {
        return new self(
            id: $id,
            checkNumber: $checkNumber,
            amount: $amount,
            payeeName: $payeeName,
            payeeAddress: $payeeAddress,
            memo: $memo,
            checkDate: $checkDate ?? new \DateTimeImmutable(),
        );
    }

    /**
     * Mark the check as printed.
     */
    public function markPrinted(): self
    {
        if (!$this->status->canTransitionTo(CheckStatus::PRINTED)) {
            throw new \LogicException("Cannot print check in {$this->status->value} status");
        }

        return new self(
            id: $this->id,
            checkNumber: $this->checkNumber,
            amount: $this->amount,
            payeeName: $this->payeeName,
            payeeAddress: $this->payeeAddress,
            memo: $this->memo,
            checkDate: $this->checkDate,
            status: CheckStatus::PRINTED,
            bankAccountId: $this->bankAccountId,
            printedAt: new \DateTimeImmutable(),
            mailedAt: $this->mailedAt,
            clearedAt: $this->clearedAt,
            voidedAt: $this->voidedAt,
            voidReason: $this->voidReason,
            positivePayReference: $this->positivePayReference,
        );
    }

    /**
     * Mark the check as mailed.
     */
    public function markMailed(): self
    {
        if (!$this->status->canTransitionTo(CheckStatus::MAILED)) {
            throw new \LogicException("Cannot mail check in {$this->status->value} status");
        }

        return new self(
            id: $this->id,
            checkNumber: $this->checkNumber,
            amount: $this->amount,
            payeeName: $this->payeeName,
            payeeAddress: $this->payeeAddress,
            memo: $this->memo,
            checkDate: $this->checkDate,
            status: CheckStatus::MAILED,
            bankAccountId: $this->bankAccountId,
            printedAt: $this->printedAt,
            mailedAt: new \DateTimeImmutable(),
            clearedAt: $this->clearedAt,
            voidedAt: $this->voidedAt,
            voidReason: $this->voidReason,
            positivePayReference: $this->positivePayReference,
        );
    }

    /**
     * Mark the check as cleared/cashed.
     */
    public function markCleared(): self
    {
        if (!$this->status->canTransitionTo(CheckStatus::CLEARED)) {
            throw new \LogicException("Cannot clear check in {$this->status->value} status");
        }

        return new self(
            id: $this->id,
            checkNumber: $this->checkNumber,
            amount: $this->amount,
            payeeName: $this->payeeName,
            payeeAddress: $this->payeeAddress,
            memo: $this->memo,
            checkDate: $this->checkDate,
            status: CheckStatus::CLEARED,
            bankAccountId: $this->bankAccountId,
            printedAt: $this->printedAt,
            mailedAt: $this->mailedAt,
            clearedAt: new \DateTimeImmutable(),
            voidedAt: $this->voidedAt,
            voidReason: $this->voidReason,
            positivePayReference: $this->positivePayReference,
        );
    }

    /**
     * Void the check.
     */
    public function void(string $reason): self
    {
        if (!$this->status->canVoid()) {
            throw new \LogicException("Cannot void check in {$this->status->value} status");
        }

        return new self(
            id: $this->id,
            checkNumber: $this->checkNumber,
            amount: $this->amount,
            payeeName: $this->payeeName,
            payeeAddress: $this->payeeAddress,
            memo: $this->memo,
            checkDate: $this->checkDate,
            status: CheckStatus::VOIDED,
            bankAccountId: $this->bankAccountId,
            printedAt: $this->printedAt,
            mailedAt: $this->mailedAt,
            clearedAt: $this->clearedAt,
            voidedAt: new \DateTimeImmutable(),
            voidReason: $reason,
            positivePayReference: $this->positivePayReference,
        );
    }

    /**
     * Stop payment on the check.
     */
    public function stopPayment(string $reason): self
    {
        if (!$this->status->canStopPayment()) {
            throw new \LogicException("Cannot stop payment on check in {$this->status->value} status");
        }

        return new self(
            id: $this->id,
            checkNumber: $this->checkNumber,
            amount: $this->amount,
            payeeName: $this->payeeName,
            payeeAddress: $this->payeeAddress,
            memo: $this->memo,
            checkDate: $this->checkDate,
            status: CheckStatus::STOP_PAYMENT,
            bankAccountId: $this->bankAccountId,
            printedAt: $this->printedAt,
            mailedAt: $this->mailedAt,
            clearedAt: $this->clearedAt,
            voidedAt: new \DateTimeImmutable(),
            voidReason: $reason,
            positivePayReference: $this->positivePayReference,
        );
    }

    /**
     * Check if the check is still outstanding (not cleared or voided).
     */
    public function isOutstanding(): bool
    {
        return !in_array($this->status, [
            CheckStatus::CLEARED,
            CheckStatus::VOIDED,
            CheckStatus::STOP_PAYMENT,
            CheckStatus::RETURNED,
            CheckStatus::EXPIRED,
        ], true);
    }

    /**
     * Check if the check is stale (older than 6 months typically).
     */
    public function isStale(int $staleDays = 180): bool
    {
        $now = new \DateTimeImmutable();
        $daysSinceIssued = $this->checkDate->diff($now)->days;

        return $daysSinceIssued > $staleDays;
    }

    /**
     * Check if the check needs to appear on positive pay file.
     */
    public function needsPositivePay(): bool
    {
        return $this->isOutstanding()
            && $this->status !== CheckStatus::PENDING
            && $this->positivePayReference === null;
    }

    /**
     * Get the formatted payee line for printing.
     */
    public function getFormattedPayeeLine(): string
    {
        return mb_strtoupper($this->payeeName);
    }

    /**
     * Get the amount in words for printing.
     */
    public function getAmountInWords(): string
    {
        $amount = $this->amount->getAmountAsFloat();
        $dollars = (int) floor($amount);
        $cents = (int) round(($amount - $dollars) * 100);

        $words = self::numberToWords($dollars);

        return sprintf('%s AND %02d/100 DOLLARS', strtoupper($words), $cents);
    }

    /**
     * Convert number to words (simplified version).
     */
    private static function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $thousands = ['', 'Thousand', 'Million', 'Billion'];

        $words = '';
        $i = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk !== 0) {
                $chunkWords = '';
                if ($chunk >= 100) {
                    $chunkWords .= $ones[(int) floor($chunk / 100)] . ' Hundred ';
                    $chunk %= 100;
                }
                if ($chunk >= 20) {
                    $chunkWords .= $tens[(int) floor($chunk / 10)] . ' ';
                    $chunk %= 10;
                }
                if ($chunk > 0) {
                    $chunkWords .= $ones[$chunk] . ' ';
                }
                $words = trim($chunkWords) . ($thousands[$i] !== '' ? ' ' . $thousands[$i] : '') . ' ' . $words;
            }
            $number = (int) floor($number / 1000);
            $i++;
        }

        return trim($words);
    }
}
