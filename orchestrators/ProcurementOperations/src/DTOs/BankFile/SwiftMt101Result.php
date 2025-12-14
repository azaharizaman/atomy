<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;

/**
 * Result of SWIFT MT101 file generation.
 *
 * Contains SWIFT-specific metadata including:
 * - Message Reference Number (MRN)
 * - Ordering Customer and Bank information
 * - Beneficiary details
 */
final readonly class SwiftMt101Result extends AbstractBankFileResult
{
    /**
     * @param array<string> $transactionReferences
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
        public string $messageReferenceNumber,
        public string $senderBic,
        public string $orderingCustomerAccount,
        public string $orderingCustomerName,
        public string $accountServicingInstitution,
        public array $transactionReferences,
        public \DateTimeImmutable $requestedExecutionDate,
        ?string $errorMessage = null,
        array $validationErrors = [],
        ?\DateTimeImmutable $generatedAt = null,
        array $metadata = [],
    ) {
        parent::__construct(
            batchId: $batchId,
            format: BankFileFormat::SWIFT_MT101,
            fileName: $fileName,
            fileContent: $fileContent,
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            success: $success,
            errorMessage: $errorMessage,
            validationErrors: $validationErrors,
            generatedAt: $generatedAt,
            metadata: array_merge($metadata, [
                'file_type' => 'SWIFT MT101',
                'standard' => 'SWIFT FIN',
                'message_type' => 'MT101',
                'mrn' => $messageReferenceNumber,
                'sender_bic' => $senderBic,
            ]),
        );
    }

    /**
     * Create a successful SWIFT MT101 result.
     *
     * @param array<string> $transactionReferences
     */
    public static function success(
        string $batchId,
        string $fileName,
        string $fileContent,
        int $totalRecords,
        Money $totalAmount,
        string $messageReferenceNumber,
        string $senderBic,
        string $orderingCustomerAccount,
        string $orderingCustomerName,
        string $accountServicingInstitution,
        array $transactionReferences,
        \DateTimeImmutable $requestedExecutionDate,
    ): self {
        return new self(
            batchId: $batchId,
            fileName: $fileName,
            fileContent: $fileContent,
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            success: true,
            messageReferenceNumber: $messageReferenceNumber,
            senderBic: $senderBic,
            orderingCustomerAccount: $orderingCustomerAccount,
            orderingCustomerName: $orderingCustomerName,
            accountServicingInstitution: $accountServicingInstitution,
            transactionReferences: $transactionReferences,
            requestedExecutionDate: $requestedExecutionDate,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a failed SWIFT MT101 result.
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
            messageReferenceNumber: '',
            senderBic: '',
            orderingCustomerAccount: '',
            orderingCustomerName: '',
            accountServicingInstitution: '',
            transactionReferences: [],
            requestedExecutionDate: new \DateTimeImmutable(),
            errorMessage: $errorMessage,
            validationErrors: $validationErrors,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Validate the SWIFT BIC format.
     */
    public static function isValidBic(string $bic): bool
    {
        // BIC is 8 or 11 characters
        // Format: AAAABBCCXXX where:
        // - AAAA: Bank code (letters)
        // - BB: Country code (letters)
        // - CC: Location code (letters/digits)
        // - XXX: Branch code (optional, letters/digits)
        return (bool) preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', strtoupper($bic));
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'message_reference_number' => $this->messageReferenceNumber,
            'sender_bic' => $this->senderBic,
            'ordering_customer_account' => $this->orderingCustomerAccount,
            'ordering_customer_name' => $this->orderingCustomerName,
            'account_servicing_institution' => $this->accountServicingInstitution,
            'transaction_count' => count($this->transactionReferences),
            'transaction_references' => $this->transactionReferences,
            'requested_execution_date' => $this->requestedExecutionDate->format('Y-m-d'),
        ]);
    }
}
