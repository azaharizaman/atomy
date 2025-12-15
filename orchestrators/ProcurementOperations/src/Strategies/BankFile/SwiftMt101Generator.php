<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\BankFile;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\DTOs\BankFile\SwiftMt101Result;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\ValueObjects\SwiftMt101Configuration;
use Nexus\ProcurementOperations\DTOs\BankFile\BankFileResultInterface;

/**
 * SWIFT MT101 File Generator.
 *
 * Generates SWIFT MT101 (Request for Transfer) messages for international
 * wire transfers. MT101 is used to request fund transfers on behalf of
 * the ordering customer.
 *
 * Message structure:
 * - Block 1: Basic Header Block
 * - Block 2: Application Header Block
 * - Block 3: User Header Block (optional)
 * - Block 4: Text Block (message content)
 * - Block 5: Trailer Block
 *
 * @see https://www.swift.com/standards/mt-messages
 */
final readonly class SwiftMt101Generator extends AbstractBankFileGenerator
{
    protected const string VERSION = '1.0.0';

    /** Maximum length for SWIFT message reference */
    private const int MAX_REFERENCE_LENGTH = 16;

    /** Maximum payee name length */
    private const int MAX_NAME_LENGTH = 35;

    private readonly SwiftMt101Configuration $configuration;

    public function __construct(
        SwiftMt101Configuration $configuration,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($logger);
        $this->configuration = $configuration;
    }

    public function getFormat(): BankFileFormat
    {
        return BankFileFormat::SWIFT_MT101;
    }

    public function getFileExtension(): string
    {
        return 'fin';
    }

    public function getMimeType(): string
    {
        return 'application/x-swift';
    }

    public function supports(PaymentBatchData $batch): bool
    {
        // Early return for empty payment items
        if (empty($batch->paymentItems)) {
            return false;
        }

        // SWIFT MT101 requires BIC codes and IBAN for international transfers
        foreach ($batch->paymentItems as $item) {
            // Must have beneficiary bank BIC or other routing info
            if (empty($item->beneficiaryBic) && empty($item->vendorBankRoutingNumber)) {
                return false;
            }
        }

        return true;
    }

    public function validate(PaymentBatchData $batch): array
    {
        $errors = $this->validateCommonFields($batch);

        // Validate configuration
        if (!$this->configuration->isValid()) {
            $errors[] = 'Invalid SWIFT MT101 configuration';
        }

        // Validate sender BIC
        if (!$this->isValidBic($this->configuration->senderBic)) {
            $errors[] = 'Invalid sender BIC format';
        }

        // Validate each payment item
        foreach ($batch->paymentItems as $index => $item) {
            $itemErrors = $this->validatePaymentItem($item, $index);
            $errors = array_merge($errors, $itemErrors);
        }

        return $errors;
    }

    public function generate(PaymentBatchData $batch): BankFileResultInterface
    {
        $this->logGenerationStart($batch);

        // Validate first
        $errors = $this->validate($batch);
        if (!empty($errors)) {
            $this->logGenerationFailure($batch->batchId, $errors);

            return SwiftMt101Result::failure(
                batchId: $batch->batchId,
                errorMessage: 'Validation failed: ' . implode('; ', $errors),
                validationErrors: $errors,
            );
        }

        try {
            $fileContent = $this->buildFileContent($batch);
            $result = $this->createResult($batch, $fileContent);
            $this->logGenerationSuccess($result);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('SWIFT MT101 generation failed with exception', [
                'batch_id' => $batch->batchId,
                'error' => $e->getMessage(),
            ]);

            return SwiftMt101Result::failure(
                batchId: $batch->batchId,
                errorMessage: 'Generation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Validate BIC format.
     */
    private function isValidBic(string $bic): bool
    {
        // BIC is 8 or 11 characters
        return (bool) preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', strtoupper($bic));
    }

    /**
     * Validate IBAN format (basic check).
     */
    private function isValidIban(string $iban): bool
    {
        $iban = strtoupper(preg_replace('/[^A-Z0-9]/', '', $iban) ?? '');

        // IBAN is 15-34 characters, starts with 2 letter country code
        return (bool) preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban);
    }

    /**
     * Check if string contains control characters.
     *
     * Detects ASCII control characters (0x00-0x1F, 0x7F) that could be used
     * for injection attacks in SWIFT message structures.
     */
    private function containsControlCharacters(string $value): bool
    {
        return (bool) preg_match('/[\x00-\x1F\x7F]/', $value);
    }

    /**
     * Sanitize account number/IBAN for SWIFT message.
     *
     * Strips all non-alphanumeric characters and converts to uppercase
     * to prevent injection of SWIFT tags or message structure manipulation.
     */
    private function sanitizeAccountForSwift(string $account): string
    {
        // Strip all non-alphanumeric characters (including control chars, colons, newlines)
        $sanitized = preg_replace('/[^A-Z0-9]/', '', strtoupper($account)) ?? '';

        return $sanitized;
    }

    /**
     * Validate and whitelist SWIFT charge code.
     *
     * Only allows valid SWIFT MT101 charge codes (SHA, OUR, BEN) to prevent
     * injection attacks via metadata manipulation.
     *
     * @param string|null $chargeCode The charge code to validate
     * @param string $default Default charge code if invalid or null
     * @return string Valid charge code (SHA, OUR, or BEN)
     */
    private function validateChargeCode(?string $chargeCode, string $default = 'SHA'): string
    {
        // Allowed SWIFT charge codes per MT101 specification
        $allowedCodes = ['SHA', 'OUR', 'BEN'];

        if ($chargeCode === null) {
            return in_array($default, $allowedCodes, true) ? $default : 'SHA';
        }

        // Normalize: uppercase and strip any non-alpha characters
        $normalized = strtoupper(preg_replace('/[^A-Z]/', '', $chargeCode) ?? '');

        // Strict whitelist check
        if (in_array($normalized, $allowedCodes, true)) {
            return $normalized;
        }

        // Fall back to default if invalid
        return in_array($default, $allowedCodes, true) ? $default : 'SHA';
    }

    /**
     * Validate individual payment item.
     *
     * @return array<string>
     */
    private function validatePaymentItem(PaymentItemData $item, int $index): array
    {
        $errors = [];
        $prefix = "Item {$index}";

        // Beneficiary identification
        if (empty($item->vendorName)) {
            $errors[] = "{$prefix}: Beneficiary name is required";
        }

        // Beneficiary account
        if (empty($item->vendorBankAccountNumber) && empty($item->beneficiaryIban)) {
            $errors[] = "{$prefix}: Beneficiary account number or IBAN is required";
        }

        // Validate IBAN if provided
        if (!empty($item->beneficiaryIban)) {
            // Reject IBANs containing control characters (injection prevention)
            if ($this->containsControlCharacters($item->beneficiaryIban)) {
                $errors[] = "{$prefix}: IBAN contains invalid control characters";
            } elseif (!$this->isValidIban($item->beneficiaryIban)) {
                $errors[] = "{$prefix}: Invalid IBAN format";
            }
        }

        // Validate account number for control characters
        if (!empty($item->vendorBankAccountNumber) && $this->containsControlCharacters($item->vendorBankAccountNumber)) {
            $errors[] = "{$prefix}: Account number contains invalid control characters";
        }

        // Beneficiary bank BIC
        if (!empty($item->beneficiaryBic) && !$this->isValidBic($item->beneficiaryBic)) {
            $errors[] = "{$prefix}: Invalid beneficiary bank BIC format";
        }

        // Amount validation
        if ($item->amount->getAmount() <= 0) {
            $errors[] = "{$prefix}: Amount must be positive";
        }

        return $errors;
    }

    /**
     * Build the complete SWIFT MT101 file content.
     */
    private function buildFileContent(PaymentBatchData $batch): string
    {
        $mrn = $this->generateMessageReference($batch->batchId);
        $now = new \DateTimeImmutable();
        $executionDate = $batch->paymentDate ?? $now->modify('+2 business days');

        $lines = [];

        // Block 1: Basic Header
        $lines[] = $this->buildBlock1();

        // Block 2: Application Header
        $lines[] = $this->buildBlock2();

        // Block 4: Text Block (main message content)
        $lines[] = '{4:';

        // Sender's Reference (Tag 20)
        $lines[] = ':20:' . $mrn;

        // Message Index Total (Tag 28D)
        $lines[] = ':28D:1/1';

        // Requested Execution Date (Tag 30)
        $lines[] = ':30:' . $executionDate->format('Ymd');

        // Ordering Customer (Tag 50H or 50F)
        $lines = array_merge($lines, $this->buildOrderingCustomer());

        // Account Servicing Institution (Tag 52A)
        if ($this->configuration->accountServicingInstitution) {
            $lines[] = ':52A:' . $this->configuration->accountServicingInstitution;
        }

        // Transaction details for each payment
        $transactionIndex = 0;
        foreach ($batch->paymentItems as $item) {
            $transactionIndex++;
            $transactionLines = $this->buildTransactionDetails($item, $transactionIndex);
            $lines = array_merge($lines, $transactionLines);
        }

        // End of Block 4
        $lines[] = '-}';

        // Block 5: Trailer (optional)
        $lines[] = $this->buildBlock5();

        return implode("\r\n", $lines);
    }

    /**
     * Build Block 1: Basic Header.
     */
    private function buildBlock1(): string
    {
        return sprintf(
            '{1:F01%s0000000000}',
            $this->padString($this->configuration->senderBic, 12),
        );
    }

    /**
     * Build Block 2: Application Header (Output).
     */
    private function buildBlock2(): string
    {
        $now = new \DateTimeImmutable();

        return sprintf(
            '{2:O101%s%sN}',
            $now->format('Hi'),
            $now->format('ymd'),
        );
    }

    /**
     * Build Block 5: Trailer.
     */
    private function buildBlock5(): string
    {
        return '{5:}';
    }

    /**
     * Build Ordering Customer fields.
     *
     * @return array<string>
     */
    private function buildOrderingCustomer(): array
    {
        $lines = [];

        // Tag 50H: Ordering Customer (with account and BIC)
        $lines[] = ':50H:/' . $this->configuration->orderingCustomerAccount;
        $lines[] = $this->configuration->senderBic;

        // Customer name and address (max 4 lines of 35 characters)
        $nameLines = $this->splitIntoLines($this->configuration->orderingCustomerName, self::MAX_NAME_LENGTH);
        foreach (array_slice($nameLines, 0, 4) as $nameLine) {
            $lines[] = $nameLine;
        }

        return $lines;
    }

    /**
     * Build transaction details for a single payment.
     *
     * @return array<string>
     */
    private function buildTransactionDetails(PaymentItemData $item, int $index): array
    {
        $lines = [];

        // Transaction sequence number (Tag 21)
        $txnRef = $this->generateTransactionReference($item, $index);
        $lines[] = ':21:' . $txnRef;

        // Instructed Currency and Amount (Tag 32B)
        $amount = number_format($item->amount->getAmount(), 2, ',', '');
        $lines[] = ':32B:' . $item->amount->getCurrency() . $amount;

        // Beneficiary Bank (Tag 57A or 57D)
        if (!empty($item->beneficiaryBic)) {
            $lines[] = ':57A:' . strtoupper($item->beneficiaryBic);
        } elseif (!empty($item->vendorBankRoutingNumber)) {
            // Sanitize routing number to prevent SWIFT tag injection
            $sanitizedRouting = $this->sanitizeForSwift($item->vendorBankRoutingNumber);
            $lines[] = ':57D:' . $sanitizedRouting;
            if (!empty($item->vendorBankName)) {
                $lines[] = $this->truncate($item->vendorBankName, self::MAX_NAME_LENGTH);
            }
        }

        // Beneficiary (Tag 59 or 59A)
        // Sanitize account to prevent SWIFT tag injection
        $rawAccount = $item->beneficiaryIban ?? $item->vendorBankAccountNumber ?? '';
        $accountNumber = $this->sanitizeAccountForSwift($rawAccount);
        $lines[] = ':59:/' . $accountNumber;

        // Beneficiary name and address
        $beneficiaryLines = $this->splitIntoLines($item->vendorName ?? '', self::MAX_NAME_LENGTH);
        foreach (array_slice($beneficiaryLines, 0, 4) as $line) {
            $lines[] = $line;
        }

        // Add beneficiary address if available
        if (!empty($item->beneficiaryAddress)) {
            $addressLines = $this->splitIntoLines($item->beneficiaryAddress, self::MAX_NAME_LENGTH);
            foreach (array_slice($addressLines, 0, 2) as $line) {
                $lines[] = $line;
            }
        }

        // Remittance Information (Tag 70) - payment reference/description
        if (!empty($item->paymentReference)) {
            $lines[] = ':70:' . $this->truncate($item->paymentReference, 35);
        }

        // Details of Charges (Tag 71A) - SHA (shared), OUR, BEN
        // Strictly whitelist charge codes to prevent injection attacks
        $chargeCode = $this->validateChargeCode(
            $item->metadata['charge_code'] ?? null,
            $this->configuration->defaultChargeCode ?? 'SHA'
        );
        $lines[] = ':71A:' . $chargeCode;

        return $lines;
    }

    /**
     * Generate message reference number.
     */
    private function generateMessageReference(string $batchId): string
    {
        $timestamp = (new \DateTimeImmutable())->format('ymdHis');
        $ref = strtoupper(substr($batchId, 0, 4));

        return $this->truncate($ref . $timestamp, self::MAX_REFERENCE_LENGTH);
    }

    /**
     * Generate transaction reference.
     */
    private function generateTransactionReference(PaymentItemData $item, int $index): string
    {
        $ref = $item->paymentReference ?? sprintf('TXN%05d', $index);

        return $this->truncate(strtoupper($ref), self::MAX_REFERENCE_LENGTH);
    }

    /**
     * Split text into lines of specified maximum length.
     *
     * @return array<string>
     */
    private function splitIntoLines(string $text, int $maxLength): array
    {
        $text = $this->sanitizeForSwift($text);

        if (strlen($text) <= $maxLength) {
            return [$text];
        }

        return str_split($text, $maxLength) ?: [];
    }

    /**
     * Truncate text to specified length.
     */
    private function truncate(string $text, int $maxLength): string
    {
        return substr($this->sanitizeForSwift($text), 0, $maxLength);
    }

    /**
     * Sanitize text for SWIFT message.
     */
    private function sanitizeForSwift(string $text): string
    {
        // SWIFT messages use uppercase and limited character set
        $text = strtoupper($text);

        // Remove or replace invalid characters
        // SWIFT allows: A-Z, 0-9, space, and certain special characters
        $text = preg_replace('/[^A-Z0-9 \/\-\?\:\(\)\.\,\'\+]/', '', $text) ?? '';

        return trim($text);
    }

    /**
     * Create the result object.
     */
    private function createResult(PaymentBatchData $batch, string $fileContent): SwiftMt101Result
    {
        $mrn = $this->generateMessageReference($batch->batchId);
        $executionDate = $batch->paymentDate ?? (new \DateTimeImmutable())->modify('+2 business days');
        $totalAmount = $this->calculateTotalAmount($batch);

        // Collect transaction references
        $transactionRefs = [];
        foreach ($batch->paymentItems as $index => $item) {
            $transactionRefs[] = $this->generateTransactionReference($item, $index + 1);
        }

        return SwiftMt101Result::success(
            batchId: $batch->batchId,
            fileName: $this->generateFileName('SWIFT_MT101', $batch->batchId),
            fileContent: $fileContent,
            totalRecords: count($batch->paymentItems),
            totalAmount: $totalAmount,
            messageReferenceNumber: $mrn,
            senderBic: $this->configuration->senderBic,
            orderingCustomerAccount: $this->configuration->orderingCustomerAccount,
            orderingCustomerName: $this->configuration->orderingCustomerName,
            accountServicingInstitution: $this->configuration->accountServicingInstitution ?? '',
            transactionReferences: $transactionRefs,
            requestedExecutionDate: $executionDate,
        );
    }
}
