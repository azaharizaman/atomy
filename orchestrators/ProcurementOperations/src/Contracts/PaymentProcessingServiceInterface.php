<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;

/**
 * Payment Processing Service Interface
 * 
 * Defines the contract for payment processing operations including:
 * - Batch creation and management
 * - Payment method support (ACH, Wire, Check)
 * - Bank file generation (NACHA, ISO 20022)
 * - Payment validation
 * 
 * Consuming applications must implement this interface using their
 * chosen payment processor (bank API, file-based, etc.).
 * 
 * @see PaymentBatchData
 * @see PaymentItemData
 * @see BankFileGenerationResult
 */
interface PaymentProcessingServiceInterface
{
    /**
     * Create a new payment batch
     *
     * @param string $tenantId Tenant identifier
     * @param string $paymentMethod Payment method (ACH, WIRE, CHECK)
     * @param string $bankAccountId Source bank account
     * @param \DateTimeImmutable $paymentDate Scheduled payment date
     * @param string $currency Currency code (e.g., 'USD', 'MYR')
     * @param string $createdBy User who created the batch
     * @return PaymentBatchData Created batch data
     */
    public function createBatch(
        string $tenantId,
        string $paymentMethod,
        string $bankAccountId,
        \DateTimeImmutable $paymentDate,
        string $currency,
        string $createdBy,
    ): PaymentBatchData;

    /**
     * Add payment item to batch
     *
     * @param PaymentBatchData $batch Current batch
     * @param string $vendorId Vendor being paid
     * @param string $vendorName Vendor display name
     * @param Money $amount Payment amount
     * @param array<string> $invoiceIds Invoices being paid
     * @param string $vendorBankAccount Vendor's bank account
     * @param string $vendorBankRoutingNumber Vendor's routing number (ACH/Wire)
     * @param string|null $vendorBankSwiftCode SWIFT code for international wires
     * @param string|null $checkPayeeName Payee name for checks
     * @param string|null $checkMailingAddress Mailing address for checks
     * @return PaymentItemData Created payment item
     */
    public function addPaymentItem(
        PaymentBatchData $batch,
        string $vendorId,
        string $vendorName,
        Money $amount,
        array $invoiceIds,
        string $vendorBankAccount,
        string $vendorBankRoutingNumber,
        ?string $vendorBankSwiftCode = null,
        ?string $checkPayeeName = null,
        ?string $checkMailingAddress = null,
    ): PaymentItemData;

    /**
     * Validate payment batch before submission
     *
     * @param PaymentBatchData $batch Batch to validate
     * @return array<string, string[]> Validation errors by payment item ID
     */
    public function validateBatch(PaymentBatchData $batch): array;

    /**
     * Validate individual payment item
     *
     * @param PaymentItemData $item Item to validate
     * @return array<string> Validation errors
     */
    public function validatePaymentItem(PaymentItemData $item): array;

    /**
     * Generate bank file for the payment batch
     *
     * @param PaymentBatchData $batch Approved batch
     * @return BankFileGenerationResult Bank file generation result
     */
    public function generateBankFile(PaymentBatchData $batch): BankFileGenerationResult;

    /**
     * Generate NACHA file for ACH payments
     *
     * @param PaymentBatchData $batch Approved ACH batch
     * @return BankFileGenerationResult NACHA file result
     */
    public function generateNachaFile(PaymentBatchData $batch): BankFileGenerationResult;

    /**
     * Generate ISO 20022 file for wire transfers
     *
     * @param PaymentBatchData $batch Approved wire batch
     * @return BankFileGenerationResult ISO 20022 file result
     */
    public function generateIso20022File(PaymentBatchData $batch): BankFileGenerationResult;

    /**
     * Generate check print file
     *
     * @param PaymentBatchData $batch Approved check batch
     * @return BankFileGenerationResult Check print file result
     */
    public function generateCheckPrintFile(PaymentBatchData $batch): BankFileGenerationResult;

    /**
     * Get required approval levels based on batch amount
     *
     * @param PaymentBatchData $batch Batch to evaluate
     * @return int Number of approval levels required
     */
    public function getRequiredApprovalLevels(PaymentBatchData $batch): int;

    /**
     * Check if user can approve at given level
     *
     * @param string $userId User attempting approval
     * @param PaymentBatchData $batch Batch to approve
     * @param int $approvalLevel Approval level
     * @return bool True if user can approve
     */
    public function canUserApprove(
        string $userId,
        PaymentBatchData $batch,
        int $approvalLevel,
    ): bool;

    /**
     * Get payment history for a vendor
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param \DateTimeImmutable|null $fromDate Start date filter
     * @param \DateTimeImmutable|null $toDate End date filter
     * @return array<PaymentItemData> Payment history
     */
    public function getVendorPaymentHistory(
        string $tenantId,
        string $vendorId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array;

    /**
     * Get pending batches for approval
     *
     * @param string $tenantId Tenant identifier
     * @param string $approverId User who can approve
     * @return array<PaymentBatchData> Pending batches
     */
    public function getPendingBatchesForApproval(
        string $tenantId,
        string $approverId,
    ): array;

    /**
     * Estimate bank fees for payment batch
     *
     * @param PaymentBatchData $batch Batch to estimate
     * @return Money Estimated total fees
     */
    public function estimateBankFees(PaymentBatchData $batch): Money;

    /**
     * Check if vendor banking details are valid for payment method
     *
     * @param string $vendorId Vendor identifier
     * @param string $paymentMethod Payment method
     * @return array{valid: bool, errors: array<string>} Validation result
     */
    public function validateVendorBankingDetails(
        string $vendorId,
        string $paymentMethod,
    ): array;
}
