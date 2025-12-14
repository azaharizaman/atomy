<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\PositivePayFormat;

/**
 * Result of Positive Pay file generation.
 *
 * Contains Positive Pay-specific metadata including:
 * - Check count and total
 * - Voided and stop payment counts
 * - Bank-specific format information
 */
final readonly class PositivePayResult extends AbstractBankFileResult
{
    /**
     * @param array<string> $includedCheckNumbers
     * @param array<string> $voidedCheckNumbers
     * @param array<string> $stopPaymentCheckNumbers
     * @param array<string> $validationErrors
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $batchId,
        string $fileName,
        string $fileContent,
        int $totalRecords,
        Money $totalAmount,
        bool $success,
        public PositivePayFormat $positivePayFormat,
        public string $bankAccountNumber,
        public int $checkCount,
        public int $voidedCheckCount,
        public int $stopPaymentCount,
        public array $includedCheckNumbers,
        public array $voidedCheckNumbers,
        public array $stopPaymentCheckNumbers,
        public \DateTimeImmutable $issueDate,
        ?string $errorMessage = null,
        array $validationErrors = [],
        ?\DateTimeImmutable $generatedAt = null,
        array $metadata = [],
    ) {
        parent::__construct(
            batchId: $batchId,
            format: BankFileFormat::POSITIVE_PAY,
            fileName: $fileName,
            fileContent: $fileContent,
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            success: $success,
            errorMessage: $errorMessage,
            validationErrors: $validationErrors,
            generatedAt: $generatedAt,
            metadata: array_merge($metadata, [
                'file_type' => 'Positive Pay',
                'format_variant' => $positivePayFormat->value,
                'format_label' => $positivePayFormat->label(),
                'bank_account' => substr($bankAccountNumber, -4),
            ]),
        );
    }

    /**
     * Create a successful Positive Pay result.
     *
     * @param array<string> $includedCheckNumbers
     * @param array<string> $voidedCheckNumbers
     * @param array<string> $stopPaymentCheckNumbers
     */
    public static function success(
        string $batchId,
        string $fileName,
        string $fileContent,
        Money $totalAmount,
        PositivePayFormat $positivePayFormat,
        string $bankAccountNumber,
        array $includedCheckNumbers,
        array $voidedCheckNumbers = [],
        array $stopPaymentCheckNumbers = [],
        \DateTimeImmutable $issueDate,
    ): self {
        $checkCount = count($includedCheckNumbers);
        $voidedCount = count($voidedCheckNumbers);
        $stopPaymentCount = count($stopPaymentCheckNumbers);

        return new self(
            batchId: $batchId,
            fileName: $fileName,
            fileContent: $fileContent,
            totalRecords: $checkCount + $voidedCount + $stopPaymentCount,
            totalAmount: $totalAmount,
            success: true,
            positivePayFormat: $positivePayFormat,
            bankAccountNumber: $bankAccountNumber,
            checkCount: $checkCount,
            voidedCheckCount: $voidedCount,
            stopPaymentCount: $stopPaymentCount,
            includedCheckNumbers: $includedCheckNumbers,
            voidedCheckNumbers: $voidedCheckNumbers,
            stopPaymentCheckNumbers: $stopPaymentCheckNumbers,
            issueDate: $issueDate,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a failed Positive Pay result.
     *
     * @param array<string> $validationErrors
     */
    public static function failure(
        string $batchId,
        string $errorMessage,
        array $validationErrors = [],
    ): self {
        return new self(
            batchId: $batchId,
            fileName: '',
            fileContent: '',
            totalRecords: 0,
            totalAmount: Money::zero('USD'),
            success: false,
            positivePayFormat: PositivePayFormat::STANDARD_CSV,
            bankAccountNumber: '',
            checkCount: 0,
            voidedCheckCount: 0,
            stopPaymentCount: 0,
            includedCheckNumbers: [],
            voidedCheckNumbers: [],
            stopPaymentCheckNumbers: [],
            issueDate: new \DateTimeImmutable(),
            errorMessage: $errorMessage,
            validationErrors: $validationErrors,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get all check numbers in the file.
     *
     * @return array<string>
     */
    public function getAllCheckNumbers(): array
    {
        return array_merge(
            $this->includedCheckNumbers,
            $this->voidedCheckNumbers,
            $this->stopPaymentCheckNumbers,
        );
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'positive_pay_format' => $this->positivePayFormat->value,
            'positive_pay_format_label' => $this->positivePayFormat->label(),
            'bank_account_last4' => substr($this->bankAccountNumber, -4),
            'check_count' => $this->checkCount,
            'voided_check_count' => $this->voidedCheckCount,
            'stop_payment_count' => $this->stopPaymentCount,
            'issue_date' => $this->issueDate->format('Y-m-d'),
            'check_number_range' => $this->getCheckNumberRange(),
        ]);
    }

    /**
     * Get the range of check numbers in the file.
     */
    private function getCheckNumberRange(): ?string
    {
        $allChecks = $this->includedCheckNumbers;

        if (empty($allChecks)) {
            return null;
        }

        $sorted = $allChecks;
        sort($sorted, SORT_NUMERIC);

        return $sorted[0] . ' - ' . end($sorted);
    }
}
