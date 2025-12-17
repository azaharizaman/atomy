<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for generating bank payment files in various formats.
 *
 * Supported Formats:
 * - NACHA/ACH: US domestic ACH payments
 * - ISO20022 pain.001: International credit transfers
 * - BAI2: Bank Administration Institute balance reporting
 * - MT940: SWIFT statement message
 *
 * @package Nexus\ProcurementOperations\Services
 */
final readonly class BankFileGenerationService
{
    /**
     * NACHA file record sizes and limits.
     */
    private const NACHA_RECORD_SIZE = 94;
    private const NACHA_BLOCKING_FACTOR = 10;

    /**
     * ISO20022 schema identifiers.
     */
    private const ISO20022_SCHEMAS = [
        'pain.001.001.03' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03',
        'pain.001.001.09' => 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09',
    ];

    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Generate a NACHA/ACH file for domestic US payments.
     *
     * @param PaymentBatchData $batch The payment batch
     * @param string $immediateOrigin Originating bank routing number
     * @param string $immediateDestination Destination bank routing number
     * @param string $companyName Company name (up to 16 chars)
     * @param string $companyId Company identification (10 chars)
     * @param string $secCode Standard Entry Class code (PPD, CCD, CTX, etc.)
     * @param string $entryDescription Entry description (10 chars)
     * @return BankFileGenerationResult The generation result
     */
    public function generateNachaFile(
        PaymentBatchData $batch,
        string $immediateOrigin,
        string $immediateDestination,
        string $companyName,
        string $companyId,
        string $secCode = 'CCD',
        string $entryDescription = 'PAYMENT',
    ): BankFileGenerationResult {
        $now = new \DateTimeImmutable();
        $lines = [];

        // Validate inputs
        $validationErrors = $this->validateNachaInputs(
            $batch,
            $immediateOrigin,
            $immediateDestination,
            $companyId,
            $secCode
        );

        if (!empty($validationErrors)) {
            return BankFileGenerationResult::nachaFile(
                batchId: $batch->batchId,
                fileName: '',
                fileContent: '',
                totalAmount: $batch->totalAmount,
                transactionCount: $batch->itemCount,
                immediateOrigin: $immediateOrigin,
                immediateDestination: $immediateDestination,
                fileCreationDate: $now,
                batchCount: 0,
                blockCount: 0,
                entryAddendaCount: 0,
                validationPassed: false,
                generatedBy: 'system',
            )->withValidationErrors($validationErrors);
        }

        // Build File Header Record (Record Type 1)
        $lines[] = $this->buildNachaFileHeader(
            $immediateDestination,
            $immediateOrigin,
            $companyName,
            $now
        );

        // Build Batch Header Record (Record Type 5)
        $lines[] = $this->buildNachaBatchHeader(
            $companyName,
            $companyId,
            $secCode,
            $entryDescription,
            $batch->scheduledPaymentDate ?? $now,
            $immediateOrigin
        );

        // Build Entry Detail Records (Record Type 6)
        $entryHash = 0;
        $totalDebitAmount = 0;
        $totalCreditAmount = 0;
        $traceNumber = 1;

        foreach ($batch->items as $item) {
            $routing = str_pad($item->routingNumber ?? '000000000', 9, '0', STR_PAD_LEFT);
            $entryHash += (int) substr($routing, 0, 8);

            $amountCents = (int) ($item->paymentAmount->getAmount() * 100);
            $totalCreditAmount += $amountCents;

            $lines[] = $this->buildNachaEntryDetail(
                $item,
                $routing,
                $amountCents,
                $companyId,
                $immediateOrigin,
                $traceNumber++
            );
        }

        // Build Batch Control Record (Record Type 8)
        $lines[] = $this->buildNachaBatchControl(
            $batch->itemCount,
            $entryHash,
            $totalDebitAmount,
            $totalCreditAmount,
            $companyId,
            $immediateOrigin
        );

        // Build File Control Record (Record Type 9)
        $blockCount = (int) ceil((count($lines) + 1) / self::NACHA_BLOCKING_FACTOR);
        $lines[] = $this->buildNachaFileControl(
            1, // batchCount
            $blockCount,
            $batch->itemCount,
            $entryHash,
            $totalDebitAmount,
            $totalCreditAmount
        );

        // Pad file to block size
        while (count($lines) % self::NACHA_BLOCKING_FACTOR !== 0) {
            $lines[] = str_repeat('9', self::NACHA_RECORD_SIZE);
        }

        $fileContent = implode("\n", $lines);
        $effectiveDate = ($batch->scheduledPaymentDate ?? $now)->format('Ymd');
        $fileName = sprintf('ACH_%s_%s_%s.ach', $batch->batchId, $effectiveDate, $now->format('His'));

        $this->logger->info('NACHA file generated', [
            'batch_id' => $batch->batchId,
            'file_name' => $fileName,
            'record_count' => count($lines),
            'total_amount' => $batch->totalAmount->getAmount(),
        ]);

        return BankFileGenerationResult::nachaFile(
            batchId: $batch->batchId,
            fileName: $fileName,
            fileContent: $fileContent,
            totalAmount: $batch->totalAmount,
            transactionCount: $batch->itemCount,
            immediateOrigin: $immediateOrigin,
            immediateDestination: $immediateDestination,
            fileCreationDate: $now,
            batchCount: 1,
            blockCount: $blockCount,
            entryAddendaCount: $batch->itemCount,
            validationPassed: true,
            generatedBy: 'system',
        );
    }

    /**
     * Generate an ISO20022 pain.001 file for international payments.
     *
     * @param PaymentBatchData $batch The payment batch
     * @param string $initiatingPartyName Initiating party name
     * @param string $initiatingPartyId Initiating party ID (BIC or other)
     * @param string $debtorAccountIban Debtor IBAN
     * @param string $debtorBankBic Debtor bank BIC
     * @param string $schemaVersion Schema version (default pain.001.001.03)
     * @return BankFileGenerationResult The generation result
     */
    public function generateIso20022File(
        PaymentBatchData $batch,
        string $initiatingPartyName,
        string $initiatingPartyId,
        string $debtorAccountIban,
        string $debtorBankBic,
        string $schemaVersion = 'pain.001.001.03',
    ): BankFileGenerationResult {
        $now = new \DateTimeImmutable();

        // Validate schema version
        if (!isset(self::ISO20022_SCHEMAS[$schemaVersion])) {
            return BankFileGenerationResult::iso20022File(
                batchId: $batch->batchId,
                fileName: '',
                fileContent: '',
                totalAmount: $batch->totalAmount,
                transactionCount: $batch->itemCount,
                messageId: $batch->batchId,
                creationDateTime: $now,
                numberOfTransactions: $batch->itemCount,
                controlSum: $batch->totalAmount->getAmount(),
                initiatingParty: $initiatingPartyName,
                schemaVersion: $schemaVersion,
                validationPassed: false,
                generatedBy: 'system',
            )->withValidationErrors(['Unsupported schema version: ' . $schemaVersion]);
        }

        $namespace = self::ISO20022_SCHEMAS[$schemaVersion];
        $xml = $this->buildIso20022Document(
            $batch,
            $initiatingPartyName,
            $initiatingPartyId,
            $debtorAccountIban,
            $debtorBankBic,
            $namespace,
            $now
        );

        $effectiveDate = ($batch->scheduledPaymentDate ?? $now)->format('Ymd');
        $fileName = sprintf('%s_%s_%s.xml', $schemaVersion, $batch->batchId, $effectiveDate);

        $this->logger->info('ISO20022 file generated', [
            'batch_id' => $batch->batchId,
            'file_name' => $fileName,
            'schema' => $schemaVersion,
            'transaction_count' => $batch->itemCount,
        ]);

        return BankFileGenerationResult::iso20022File(
            batchId: $batch->batchId,
            fileName: $fileName,
            fileContent: $xml,
            totalAmount: $batch->totalAmount,
            transactionCount: $batch->itemCount,
            messageId: $batch->batchId,
            creationDateTime: $now,
            numberOfTransactions: $batch->itemCount,
            controlSum: $batch->totalAmount->getAmount(),
            initiatingParty: $initiatingPartyName,
            schemaVersion: $schemaVersion,
            validationPassed: true,
            generatedBy: 'system',
        );
    }

    /**
     * Validate a generated file against its format specification.
     *
     * @param BankFileGenerationResult $result The generated file result
     * @return array<string, mixed> Validation result with errors if any
     */
    public function validateGeneratedFile(BankFileGenerationResult $result): array {
        $errors = [];
        $warnings = [];

        if ($result->format === 'NACHA') {
            // NACHA-specific validation
            $lines = explode("\n", $result->fileContent);
            
            // Check record count
            if (count($lines) % self::NACHA_BLOCKING_FACTOR !== 0) {
                $errors[] = 'File block count does not match NACHA blocking factor';
            }

            // Check record sizes
            foreach ($lines as $lineNumber => $line) {
                $length = strlen($line);
                if ($length !== self::NACHA_RECORD_SIZE && $length !== 0) {
                    $warnings[] = sprintf(
                        'Line %d has incorrect length: %d (expected %d)',
                        $lineNumber + 1,
                        $length,
                        self::NACHA_RECORD_SIZE
                    );
                }
            }

            // Check file header exists
            if (!str_starts_with($lines[0], '1')) {
                $errors[] = 'Missing File Header Record (Type 1)';
            }

            // Check file control exists
            $lastNonPaddingLine = '';
            foreach (array_reverse($lines) as $line) {
                if (!preg_match('/^9+$/', $line)) {
                    $lastNonPaddingLine = $line;
                    break;
                }
            }
            if (!str_starts_with($lastNonPaddingLine, '9')) {
                $errors[] = 'Missing File Control Record (Type 9)';
            }

        } elseif ($result->format === 'ISO20022') {
            // XML validation
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            
            if (!$doc->loadXML($result->fileContent)) {
                $xmlErrors = libxml_get_errors();
                foreach ($xmlErrors as $error) {
                    $errors[] = sprintf(
                        'XML error at line %d: %s',
                        $error->line,
                        trim($error->message)
                    );
                }
                libxml_clear_errors();
            }

            // Check required elements
            if (strpos($result->fileContent, '<MsgId>') === false) {
                $errors[] = 'Missing required element: MsgId';
            }
            if (strpos($result->fileContent, '<NbOfTxs>') === false) {
                $errors[] = 'Missing required element: NbOfTxs';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'format' => $result->format,
            'file_name' => $result->fileName,
        ];
    }

    /**
     * Convert a payment batch to positive pay file format.
     *
     * Positive Pay is a fraud prevention service for check payments.
     *
     * @param PaymentBatchData $batch The payment batch (must be CHECK method)
     * @param string $accountNumber Bank account number
     * @param string $format Positive pay format (STANDARD, BAI2)
     * @return string The positive pay file content
     */
    public function generatePositivePayFile(
        PaymentBatchData $batch,
        string $accountNumber,
        string $format = 'STANDARD',
    ): string {
        if ($batch->paymentMethod !== 'CHECK') {
            throw new \InvalidArgumentException(
                'Positive Pay files can only be generated for check payments'
            );
        }

        $lines = [];
        $now = new \DateTimeImmutable();

        if ($format === 'STANDARD') {
            // Standard CSV-like format
            $lines[] = 'Account Number,Check Number,Amount,Payee Name,Issue Date';
            
            foreach ($batch->items as $item) {
                $lines[] = sprintf(
                    '%s,%s,%.2f,"%s",%s',
                    $accountNumber,
                    $item->checkNumber ?? $item->itemId,
                    $item->paymentAmount->getAmount(),
                    str_replace('"', '""', $item->payeeName ?? $item->vendorName),
                    ($batch->scheduledPaymentDate ?? $now)->format('Y-m-d')
                );
            }
        } else {
            // BAI2 format header
            $lines[] = sprintf(
                '01,%s,%s,%s,,,/',
                $accountNumber,
                'POSITIVEPAY',
                $now->format('Ymd')
            );

            foreach ($batch->items as $item) {
                $lines[] = sprintf(
                    '16,475,%d,,,,%s/%s/',
                    (int) ($item->paymentAmount->getAmount() * 100),
                    $item->checkNumber ?? $item->itemId,
                    str_replace(',', '', $item->payeeName ?? $item->vendorName)
                );
            }

            $lines[] = sprintf(
                '99,%d,%d,/',
                count($batch->items),
                (int) ($batch->totalAmount->getAmount() * 100)
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Validate NACHA file inputs.
     */
    private function validateNachaInputs(
        PaymentBatchData $batch,
        string $immediateOrigin,
        string $immediateDestination,
        string $companyId,
        string $secCode,
    ): array {
        $errors = [];

        if (strlen($immediateOrigin) !== 9 && strlen($immediateOrigin) !== 10) {
            $errors[] = 'Immediate origin must be 9 or 10 digits';
        }

        if (strlen($immediateDestination) !== 9 && strlen($immediateDestination) !== 10) {
            $errors[] = 'Immediate destination must be 9 or 10 digits';
        }

        if (strlen($companyId) !== 10) {
            $errors[] = 'Company ID must be exactly 10 characters';
        }

        $validSecCodes = ['CCD', 'CTX', 'PPD', 'WEB', 'TEL'];
        if (!in_array($secCode, $validSecCodes, true)) {
            $errors[] = 'Invalid SEC code. Must be one of: ' . implode(', ', $validSecCodes);
        }

        if ($batch->itemCount === 0) {
            $errors[] = 'Batch must contain at least one payment item';
        }

        foreach ($batch->items as $item) {
            if (empty($item->routingNumber) || strlen($item->routingNumber) !== 9) {
                $errors[] = sprintf('Item %s has invalid routing number', $item->itemId);
            }
            if (empty($item->bankAccountNumber)) {
                $errors[] = sprintf('Item %s has missing bank account number', $item->itemId);
            }
        }

        return $errors;
    }

    /**
     * Build NACHA File Header Record (Type 1).
     */
    private function buildNachaFileHeader(
        string $immediateDestination,
        string $immediateOrigin,
        string $companyName,
        \DateTimeImmutable $creationDate,
    ): string {
        return sprintf(
            '1%s%s%s%sA094101 %s%s',
            str_pad(' ' . $immediateDestination, 10),  // Immediate Destination (b + 9 digits)
            str_pad(' ' . $immediateOrigin, 10),       // Immediate Origin (b + 9 digits)
            $creationDate->format('ymd'),               // File Creation Date
            $creationDate->format('Hi'),                // File Creation Time
            str_pad(strtoupper(substr($companyName, 0, 23)), 23),  // Immediate Destination Name
            str_pad(strtoupper(substr($companyName, 0, 23)), 23)   // Immediate Origin Name
        );
    }

    /**
     * Build NACHA Batch Header Record (Type 5).
     */
    private function buildNachaBatchHeader(
        string $companyName,
        string $companyId,
        string $secCode,
        string $entryDescription,
        \DateTimeImmutable $effectiveDate,
        string $originatingDfi,
    ): string {
        return sprintf(
            '5200%s%s%s%s%s      %s%s1%s0000001',
            str_pad(strtoupper(substr($companyName, 0, 16)), 16),   // Company Name
            str_pad('', 20),                                         // Company Discretionary Data
            str_pad($companyId, 10),                                 // Company Identification
            $secCode,                                                 // SEC Code
            str_pad(strtoupper(substr($entryDescription, 0, 10)), 10), // Entry Description
            $effectiveDate->format('ymd'),                           // Effective Entry Date
            str_pad('', 3),                                          // Settlement Date (reserved)
            substr($originatingDfi, 0, 8)                            // Originating DFI Identification
        );
    }

    /**
     * Build NACHA Entry Detail Record (Type 6).
     */
    private function buildNachaEntryDetail(
        PaymentItemData $item,
        string $routingNumber,
        int $amountCents,
        string $companyId,
        string $originatingDfi,
        int $traceNumber,
    ): string {
        return sprintf(
            '622%s%s%s%s%s  0%s%s',
            $routingNumber,                                          // Receiving DFI ID
            str_pad($item->bankAccountNumber ?? '', 17),            // DFI Account Number
            str_pad((string) $amountCents, 10, '0', STR_PAD_LEFT),  // Amount
            str_pad(substr($item->vendorId, 0, 15), 15),            // Individual Identification
            str_pad(substr($item->vendorName, 0, 22), 22),          // Individual Name
            substr($originatingDfi, 0, 8),                           // Trace Number Part 1
            str_pad((string) $traceNumber, 7, '0', STR_PAD_LEFT)    // Trace Number Part 2
        );
    }

    /**
     * Build NACHA Batch Control Record (Type 8).
     */
    private function buildNachaBatchControl(
        int $entryCount,
        int $entryHash,
        int $totalDebit,
        int $totalCredit,
        string $companyId,
        string $originatingDfi,
    ): string {
        return sprintf(
            '8200%s%s%s%s%s%s%s0000001',
            str_pad((string) $entryCount, 6, '0', STR_PAD_LEFT),
            str_pad((string) ($entryHash % 10000000000), 10, '0', STR_PAD_LEFT),
            str_pad((string) $totalDebit, 12, '0', STR_PAD_LEFT),
            str_pad((string) $totalCredit, 12, '0', STR_PAD_LEFT),
            str_pad($companyId, 10),
            str_pad('', 25),  // Message Authentication Code + Reserved
            substr($originatingDfi, 0, 8)
        );
    }

    /**
     * Build NACHA File Control Record (Type 9).
     */
    private function buildNachaFileControl(
        int $batchCount,
        int $blockCount,
        int $entryCount,
        int $entryHash,
        int $totalDebit,
        int $totalCredit,
    ): string {
        return sprintf(
            '9%s%s%s%s%s%s%s',
            str_pad((string) $batchCount, 6, '0', STR_PAD_LEFT),
            str_pad((string) $blockCount, 6, '0', STR_PAD_LEFT),
            str_pad((string) $entryCount, 8, '0', STR_PAD_LEFT),
            str_pad((string) ($entryHash % 10000000000), 10, '0', STR_PAD_LEFT),
            str_pad((string) $totalDebit, 12, '0', STR_PAD_LEFT),
            str_pad((string) $totalCredit, 12, '0', STR_PAD_LEFT),
            str_pad('', 39)  // Reserved
        );
    }

    /**
     * Build ISO20022 pain.001 XML document.
     */
    private function buildIso20022Document(
        PaymentBatchData $batch,
        string $initiatingPartyName,
        string $initiatingPartyId,
        string $debtorAccountIban,
        string $debtorBankBic,
        string $namespace,
        \DateTimeImmutable $creationDateTime,
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Document root
        $document = $dom->createElementNS($namespace, 'Document');
        $dom->appendChild($document);

        // Customer Credit Transfer Initiation
        $cstmrCdtTrfInitn = $dom->createElement('CstmrCdtTrfInitn');
        $document->appendChild($cstmrCdtTrfInitn);

        // Group Header
        $grpHdr = $dom->createElement('GrpHdr');
        $cstmrCdtTrfInitn->appendChild($grpHdr);

        $grpHdr->appendChild($dom->createElement('MsgId', $batch->batchId));
        $grpHdr->appendChild($dom->createElement('CreDtTm', $creationDateTime->format('Y-m-d\TH:i:s')));
        $grpHdr->appendChild($dom->createElement('NbOfTxs', (string) $batch->itemCount));
        $grpHdr->appendChild($dom->createElement('CtrlSum', number_format($batch->totalAmount->getAmount(), 2, '.', '')));

        $initgPty = $dom->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);
        $initgPty->appendChild($dom->createElement('Nm', $this->sanitizeXmlValue($initiatingPartyName)));
        
        $id = $dom->createElement('Id');
        $initgPty->appendChild($id);
        $orgId = $dom->createElement('OrgId');
        $id->appendChild($orgId);
        $othr = $dom->createElement('Othr');
        $orgId->appendChild($othr);
        $othr->appendChild($dom->createElement('Id', $initiatingPartyId));

        // Payment Information
        $pmtInf = $dom->createElement('PmtInf');
        $cstmrCdtTrfInitn->appendChild($pmtInf);

        $pmtInf->appendChild($dom->createElement('PmtInfId', $batch->batchId . '-001'));
        $pmtInf->appendChild($dom->createElement('PmtMtd', 'TRF'));
        $pmtInf->appendChild($dom->createElement('BtchBookg', 'true'));
        $pmtInf->appendChild($dom->createElement('NbOfTxs', (string) $batch->itemCount));
        $pmtInf->appendChild($dom->createElement('CtrlSum', number_format($batch->totalAmount->getAmount(), 2, '.', '')));

        $pmtTpInf = $dom->createElement('PmtTpInf');
        $pmtInf->appendChild($pmtTpInf);
        $svcLvl = $dom->createElement('SvcLvl');
        $pmtTpInf->appendChild($svcLvl);
        $svcLvl->appendChild($dom->createElement('Cd', 'NORM'));

        $reqExctnDt = ($batch->scheduledPaymentDate ?? $creationDateTime)->format('Y-m-d');
        $pmtInf->appendChild($dom->createElement('ReqdExctnDt', $reqExctnDt));

        // Debtor
        $dbtr = $dom->createElement('Dbtr');
        $pmtInf->appendChild($dbtr);
        $dbtr->appendChild($dom->createElement('Nm', $this->sanitizeXmlValue($initiatingPartyName)));

        $dbtrAcct = $dom->createElement('DbtrAcct');
        $pmtInf->appendChild($dbtrAcct);
        $dbtrAcctId = $dom->createElement('Id');
        $dbtrAcct->appendChild($dbtrAcctId);
        $dbtrAcctId->appendChild($dom->createElement('IBAN', $debtorAccountIban));

        $dbtrAgt = $dom->createElement('DbtrAgt');
        $pmtInf->appendChild($dbtrAgt);
        $dbtrAgtFinInstnId = $dom->createElement('FinInstnId');
        $dbtrAgt->appendChild($dbtrAgtFinInstnId);
        $dbtrAgtFinInstnId->appendChild($dom->createElement('BIC', $debtorBankBic));

        // Credit Transfer Transaction Information
        foreach ($batch->items as $item) {
            $cdtTrfTxInf = $dom->createElement('CdtTrfTxInf');
            $pmtInf->appendChild($cdtTrfTxInf);

            $pmtId = $dom->createElement('PmtId');
            $cdtTrfTxInf->appendChild($pmtId);
            $pmtId->appendChild($dom->createElement('EndToEndId', $item->itemId));

            $amt = $dom->createElement('Amt');
            $cdtTrfTxInf->appendChild($amt);
            $instdAmt = $dom->createElement('InstdAmt', number_format($item->paymentAmount->getAmount(), 2, '.', ''));
            $instdAmt->setAttribute('Ccy', $item->paymentAmount->getCurrency());
            $amt->appendChild($instdAmt);

            // Creditor Agent (if wire transfer with SWIFT)
            if (!empty($item->beneficiaryBankSwift)) {
                $cdtrAgt = $dom->createElement('CdtrAgt');
                $cdtTrfTxInf->appendChild($cdtrAgt);
                $cdtrAgtFinInstnId = $dom->createElement('FinInstnId');
                $cdtrAgt->appendChild($cdtrAgtFinInstnId);
                $cdtrAgtFinInstnId->appendChild($dom->createElement('BIC', $item->beneficiaryBankSwift));
            }

            // Creditor
            $cdtr = $dom->createElement('Cdtr');
            $cdtTrfTxInf->appendChild($cdtr);
            $cdtr->appendChild($dom->createElement('Nm', $this->sanitizeXmlValue($item->vendorName)));

            // Creditor Account
            $cdtrAcct = $dom->createElement('CdtrAcct');
            $cdtTrfTxInf->appendChild($cdtrAcct);
            $cdtrAcctId = $dom->createElement('Id');
            $cdtrAcct->appendChild($cdtrAcctId);
            $othr = $dom->createElement('Othr');
            $cdtrAcctId->appendChild($othr);
            $othr->appendChild($dom->createElement('Id', $item->bankAccountNumber ?? $item->beneficiaryAccountNumber ?? ''));

            // Remittance Information
            $rmtInf = $dom->createElement('RmtInf');
            $cdtTrfTxInf->appendChild($rmtInf);
            $rmtInf->appendChild($dom->createElement('Ustrd', $this->sanitizeXmlValue($item->invoiceNumber)));
        }

        return $dom->saveXML();
    }

    /**
     * Sanitize a value for XML content (remove invalid characters).
     */
    private function sanitizeXmlValue(string $value): string {
        // Remove control characters and limit length
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        return substr($sanitized ?? '', 0, 140); // ISO20022 max text length
    }
}
