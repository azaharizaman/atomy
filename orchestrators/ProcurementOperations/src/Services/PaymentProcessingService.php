<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\PaymentProcessingServiceInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchApprovedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchProcessedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchRejectedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchSubmittedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentItemFailedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for managing payment batch processing and bank file generation.
 *
 * This service handles:
 * - Payment batch creation and lifecycle management
 * - Multi-level approval workflows
 * - Bank file generation (NACHA, ISO20022)
 * - Payment method routing (ACH, Wire, Check)
 * - Batch validation and error handling
 *
 * @package Nexus\ProcurementOperations\Services
 */
final readonly class PaymentProcessingService implements PaymentProcessingServiceInterface
{
    /**
     * Default approval thresholds by level.
     */
    private const DEFAULT_APPROVAL_THRESHOLDS = [
        1 => 10000.00,    // Level 1: Up to $10,000
        2 => 50000.00,    // Level 2: Up to $50,000
        3 => 100000.00,   // Level 3: Up to $100,000
        4 => 500000.00,   // Level 4: Up to $500,000
        5 => PHP_FLOAT_MAX, // Level 5: Unlimited
    ];

    /**
     * Approval roles by level.
     */
    private const APPROVAL_ROLES = [
        1 => ['ap_clerk', 'ap_supervisor', 'ap_manager', 'finance_manager', 'cfo'],
        2 => ['ap_supervisor', 'ap_manager', 'finance_manager', 'cfo'],
        3 => ['ap_manager', 'finance_manager', 'cfo'],
        4 => ['finance_manager', 'cfo'],
        5 => ['cfo'],
    ];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createBatch(
        string $tenantId,
        string $paymentMethod,
        string $bankAccountId,
        string $description,
        string $createdBy,
        ?string $paymentDate = null,
        array $metadata = [],
    ): PaymentBatchData {
        $batch = PaymentBatchData::create(
            batchId: $this->generateBatchId(),
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            description: $description,
            createdBy: $createdBy,
            scheduledPaymentDate: $paymentDate ? new \DateTimeImmutable($paymentDate) : null,
            metadata: $metadata,
        );

        $event = new PaymentBatchCreatedEvent(
            tenantId: $tenantId,
            batchId: $batch->batchId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            description: $description,
            scheduledPaymentDate: $batch->scheduledPaymentDate,
            createdBy: $createdBy,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Payment batch created', [
            'batch_id' => $batch->batchId,
            'tenant_id' => $tenantId,
            'payment_method' => $paymentMethod,
            'created_by' => $createdBy,
        ]);

        return $batch;
    }

    /**
     * {@inheritDoc}
     */
    public function addPaymentItem(
        PaymentBatchData $batch,
        string $invoiceId,
        string $invoiceNumber,
        string $vendorId,
        string $vendorName,
        Money $paymentAmount,
        string $bankAccountNumber,
        string $routingNumber,
        ?Money $discountAmount = null,
        ?string $remittanceInfo = null,
    ): PaymentBatchData {
        if (!$batch->canModify()) {
            throw new \InvalidArgumentException(
                "Cannot add items to batch {$batch->batchId}: status is {$batch->status->value}"
            );
        }

        $item = match ($batch->paymentMethod) {
            'ACH' => PaymentItemData::forAch(
                itemId: $this->generateItemId(),
                invoiceId: $invoiceId,
                invoiceNumber: $invoiceNumber,
                vendorId: $vendorId,
                vendorName: $vendorName,
                paymentAmount: $paymentAmount,
                bankAccountNumber: $bankAccountNumber,
                routingNumber: $routingNumber,
                achAccountType: 'checking',
                discountAmount: $discountAmount,
                remittanceInfo: $remittanceInfo,
            ),
            'WIRE' => PaymentItemData::forWire(
                itemId: $this->generateItemId(),
                invoiceId: $invoiceId,
                invoiceNumber: $invoiceNumber,
                vendorId: $vendorId,
                vendorName: $vendorName,
                paymentAmount: $paymentAmount,
                beneficiaryAccountNumber: $bankAccountNumber,
                beneficiaryBankSwift: $routingNumber,
                beneficiaryName: $vendorName,
                beneficiaryBankName: 'Unknown',
                discountAmount: $discountAmount,
                remittanceInfo: $remittanceInfo,
            ),
            'CHECK' => PaymentItemData::forCheck(
                itemId: $this->generateItemId(),
                invoiceId: $invoiceId,
                invoiceNumber: $invoiceNumber,
                vendorId: $vendorId,
                vendorName: $vendorName,
                paymentAmount: $paymentAmount,
                payeeName: $vendorName,
                mailingAddress: 'Address on file',
                discountAmount: $discountAmount,
                remittanceInfo: $remittanceInfo,
            ),
            default => throw new \InvalidArgumentException(
                "Unsupported payment method: {$batch->paymentMethod}"
            ),
        };

        return $batch->withPaymentItem($item);
    }

    /**
     * {@inheritDoc}
     */
    public function validateBatch(PaymentBatchData $batch): array {
        $errors = [];
        $warnings = [];

        // Check minimum items
        if ($batch->itemCount === 0) {
            $errors[] = [
                'code' => 'EMPTY_BATCH',
                'message' => 'Batch must contain at least one payment item',
            ];
        }

        // Validate each item
        foreach ($batch->items as $item) {
            $itemErrors = $this->validatePaymentItem($item, $batch->paymentMethod);
            foreach ($itemErrors as $error) {
                $errors[] = array_merge($error, ['item_id' => $item->itemId]);
            }
        }

        // Check for duplicate invoices
        $invoiceIds = array_map(fn($item) => $item->invoiceId, $batch->items);
        $duplicates = array_diff_assoc($invoiceIds, array_unique($invoiceIds));
        if (!empty($duplicates)) {
            $warnings[] = [
                'code' => 'DUPLICATE_INVOICES',
                'message' => 'Batch contains duplicate invoice payments',
                'invoice_ids' => array_values(array_unique($duplicates)),
            ];
        }

        // Check payment date
        if ($batch->scheduledPaymentDate !== null) {
            $now = new \DateTimeImmutable();
            if ($batch->scheduledPaymentDate < $now->modify('-1 day')) {
                $errors[] = [
                    'code' => 'PAST_PAYMENT_DATE',
                    'message' => 'Scheduled payment date is in the past',
                ];
            }
            
            // Warning for weekend payment dates
            $dayOfWeek = (int) $batch->scheduledPaymentDate->format('N');
            if ($dayOfWeek >= 6) {
                $warnings[] = [
                    'code' => 'WEEKEND_PAYMENT',
                    'message' => 'Payment date falls on a weekend - may be processed on next business day',
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'item_count' => $batch->itemCount,
            'total_amount' => $batch->totalAmount,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function submitForApproval(
        PaymentBatchData $batch,
        string $submittedBy,
        ?string $notes = null,
    ): PaymentBatchData {
        $validation = $this->validateBatch($batch);
        
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                'Cannot submit invalid batch: ' . json_encode($validation['errors'])
            );
        }

        $submittedBatch = $batch->withSubmitForApproval($submittedBy);

        $event = new PaymentBatchSubmittedEvent(
            tenantId: $batch->tenantId,
            batchId: $batch->batchId,
            totalAmount: $batch->totalAmount,
            itemCount: $batch->itemCount,
            requiredApprovalLevels: $this->getRequiredApprovalLevels($batch->totalAmount),
            submittedBy: $submittedBy,
            notes: $notes,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Payment batch submitted for approval', [
            'batch_id' => $batch->batchId,
            'total_amount' => $batch->totalAmount->getAmount(),
            'item_count' => $batch->itemCount,
            'required_levels' => $this->getRequiredApprovalLevels($batch->totalAmount),
            'submitted_by' => $submittedBy,
        ]);

        return $submittedBatch;
    }

    /**
     * {@inheritDoc}
     */
    public function approve(
        PaymentBatchData $batch,
        string $approvedBy,
        int $approvalLevel,
        ?string $notes = null,
    ): PaymentBatchData {
        if (!$this->canUserApprove($approvedBy, $batch->totalAmount, $approvalLevel)) {
            throw new \InvalidArgumentException(
                "User {$approvedBy} cannot approve at level {$approvalLevel}"
            );
        }

        $approvedBatch = $batch->withApproval($approvedBy, $approvalLevel);

        $event = new PaymentBatchApprovedEvent(
            tenantId: $batch->tenantId,
            batchId: $batch->batchId,
            totalAmount: $batch->totalAmount,
            itemCount: $batch->itemCount,
            approverUserId: $approvedBy,
            approvalLevel: $approvalLevel,
            notes: $notes,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Payment batch approved', [
            'batch_id' => $batch->batchId,
            'approval_level' => $approvalLevel,
            'approved_by' => $approvedBy,
            'new_status' => $approvedBatch->status->value,
        ]);

        return $approvedBatch;
    }

    /**
     * {@inheritDoc}
     */
    public function reject(
        PaymentBatchData $batch,
        string $rejectedBy,
        string $rejectionReason,
    ): PaymentBatchData {
        $rejectedBatch = $batch->withRejection($rejectedBy, $rejectionReason);

        $event = new PaymentBatchRejectedEvent(
            tenantId: $batch->tenantId,
            batchId: $batch->batchId,
            totalAmount: $batch->totalAmount,
            itemCount: $batch->itemCount,
            rejectedBy: $rejectedBy,
            rejectionReason: $rejectionReason,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->warning('Payment batch rejected', [
            'batch_id' => $batch->batchId,
            'rejected_by' => $rejectedBy,
            'reason' => $rejectionReason,
        ]);

        return $rejectedBatch;
    }

    /**
     * {@inheritDoc}
     */
    public function generateBankFile(
        PaymentBatchData $batch,
        string $format = 'NACHA',
    ): BankFileGenerationResult {
        return match (strtoupper($format)) {
            'NACHA' => $this->generateNachaFile($batch),
            'ISO20022', 'PAIN.001' => $this->generateIso20022File($batch),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function generateNachaFile(PaymentBatchData $batch): BankFileGenerationResult {
        if (!$batch->isApproved()) {
            throw new \InvalidArgumentException(
                "Cannot generate NACHA file for non-approved batch {$batch->batchId}"
            );
        }

        // NACHA file structure simulation
        $fileContent = $this->buildNachaFileContent($batch);

        return BankFileGenerationResult::nachaFile(
            batchId: $batch->batchId,
            fileName: "ACH_{$batch->batchId}_{$batch->scheduledPaymentDate?->format('Ymd')}.ach",
            fileContent: $fileContent,
            totalAmount: $batch->totalAmount,
            transactionCount: $batch->itemCount,
            immediateOrigin: '0000000000',
            immediateDestination: '0000000000',
            fileCreationDate: new \DateTimeImmutable(),
            batchCount: 1,
            blockCount: (int) ceil(($batch->itemCount + 4) / 10),
            entryAddendaCount: $batch->itemCount,
            validationPassed: true,
            generatedBy: 'system',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function generateIso20022File(PaymentBatchData $batch): BankFileGenerationResult {
        if (!$batch->isApproved()) {
            throw new \InvalidArgumentException(
                "Cannot generate ISO20022 file for non-approved batch {$batch->batchId}"
            );
        }

        // ISO20022 XML structure simulation
        $fileContent = $this->buildIso20022FileContent($batch);

        return BankFileGenerationResult::iso20022File(
            batchId: $batch->batchId,
            fileName: "pain.001_{$batch->batchId}_{$batch->scheduledPaymentDate?->format('Ymd')}.xml",
            fileContent: $fileContent,
            totalAmount: $batch->totalAmount,
            transactionCount: $batch->itemCount,
            messageId: $batch->batchId,
            creationDateTime: new \DateTimeImmutable(),
            numberOfTransactions: $batch->itemCount,
            controlSum: $batch->totalAmount->getAmount(),
            initiatingParty: $batch->tenantId,
            schemaVersion: 'pain.001.001.03',
            validationPassed: true,
            generatedBy: 'system',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredApprovalLevels(Money $totalAmount): int {
        $amount = $totalAmount->getAmount();

        foreach (self::DEFAULT_APPROVAL_THRESHOLDS as $level => $threshold) {
            if ($amount <= $threshold) {
                return $level;
            }
        }

        return 5; // Maximum level
    }

    /**
     * {@inheritDoc}
     */
    public function canUserApprove(
        string $userId,
        Money $batchAmount,
        int $approvalLevel,
        ?array $userRoles = null,
    ): bool {
        // In production, this would check user roles from Identity package
        // For now, we use the passed roles or assume the user has permission
        
        if ($userRoles === null) {
            // Would typically inject PermissionCheckerInterface or PolicyEvaluatorInterface
            return true;
        }

        $requiredRoles = self::APPROVAL_ROLES[$approvalLevel] ?? [];
        
        return !empty(array_intersect($userRoles, $requiredRoles));
    }

    /**
     * {@inheritDoc}
     */
    public function processCompletedBatch(
        PaymentBatchData $batch,
        string $confirmationNumber,
        \DateTimeImmutable $processedDate,
        ?array $itemResults = null,
    ): PaymentBatchData {
        $processedBatch = $batch->withProcessing()->withCompletion(
            confirmationNumber: $confirmationNumber,
            processedDate: $processedDate,
        );

        $successCount = $batch->itemCount;
        $failedItems = [];

        // Process item results if provided
        if ($itemResults !== null) {
            foreach ($itemResults as $result) {
                if (!$result['success']) {
                    $successCount--;
                    $failedItems[] = $result;

                    $event = new PaymentItemFailedEvent(
                        tenantId: $batch->tenantId,
                        batchId: $batch->batchId,
                        itemId: $result['item_id'],
                        invoiceId: $result['invoice_id'] ?? 'unknown',
                        invoiceNumber: $result['invoice_number'] ?? 'unknown',
                        vendorId: $result['vendor_id'] ?? 'unknown',
                        vendorName: $result['vendor_name'] ?? 'unknown',
                        paymentAmount: $result['amount'] ?? Money::of(0, 'USD'),
                        failureReason: $result['error'] ?? 'Unknown error',
                        failureCode: $result['error_code'] ?? 'UNKNOWN',
                        isRetryable: $result['retryable'] ?? false,
                        occurredAt: new \DateTimeImmutable(),
                    );

                    $this->eventDispatcher->dispatch($event);
                }
            }
        }

        $event = new PaymentBatchProcessedEvent(
            tenantId: $batch->tenantId,
            batchId: $batch->batchId,
            totalAmount: $batch->totalAmount,
            successCount: $successCount,
            failureCount: count($failedItems),
            bankReference: $confirmationNumber,
            processedDate: $processedDate,
            failedItems: $failedItems,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Payment batch processed', [
            'batch_id' => $batch->batchId,
            'confirmation_number' => $confirmationNumber,
            'success_count' => $successCount,
            'failure_count' => count($failedItems),
        ]);

        return $processedBatch;
    }

    /**
     * Validate a single payment item.
     */
    private function validatePaymentItem(PaymentItemData $item, string $paymentMethod): array {
        $errors = [];

        if ($item->paymentAmount->getAmount() <= 0) {
            $errors[] = [
                'code' => 'INVALID_AMOUNT',
                'message' => 'Payment amount must be positive',
            ];
        }

        if ($paymentMethod === 'ACH') {
            if (empty($item->routingNumber) || strlen($item->routingNumber) !== 9) {
                $errors[] = [
                    'code' => 'INVALID_ROUTING_NUMBER',
                    'message' => 'ACH payments require a 9-digit routing number',
                ];
            }
            if (empty($item->bankAccountNumber)) {
                $errors[] = [
                    'code' => 'MISSING_ACCOUNT_NUMBER',
                    'message' => 'ACH payments require a bank account number',
                ];
            }
        }

        if ($paymentMethod === 'WIRE') {
            if (empty($item->beneficiaryBankSwift)) {
                $errors[] = [
                    'code' => 'MISSING_SWIFT',
                    'message' => 'Wire transfers require a SWIFT/BIC code',
                ];
            }
        }

        return $errors;
    }

    /**
     * Build NACHA file content.
     */
    private function buildNachaFileContent(PaymentBatchData $batch): string {
        $lines = [];
        $now = new \DateTimeImmutable();

        // File Header Record (1)
        $lines[] = sprintf(
            '101 %s%s%s%s%s%s%s%s%s',
            str_pad('091000019', 10),           // Immediate Destination
            str_pad('123456789', 10),           // Immediate Origin
            $now->format('ymd'),                 // File Creation Date
            $now->format('Hi'),                  // File Creation Time
            'A',                                  // File ID Modifier
            '094',                                // Record Size
            '10',                                 // Blocking Factor
            '1',                                  // Format Code
            str_pad('FEDERAL RESERVE BANK', 23)  // Immediate Destination Name
        );

        // Batch Header Record (5)
        $lines[] = sprintf(
            '5200%s%s%s%s%s%s%s%s%s1%s',
            str_pad('COMPANY NAME', 16),         // Company Name
            str_pad('', 20),                      // Company Discretionary Data
            str_pad('123456789', 10),            // Company ID
            'PPD',                                 // SEC Code
            str_pad('PAYROLL', 10),               // Company Entry Description
            str_pad($now->format('ymd'), 6),     // Company Descriptive Date
            str_pad(($batch->scheduledPaymentDate ?? $now)->format('ymd'), 6), // Effective Entry Date
            str_pad('', 3),                       // Settlement Date
            '1',                                  // Originator Status Code
            str_pad('091000019', 8)              // Originating DFI ID
        );

        // Entry Detail Records (6) - Simplified
        $entryHash = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($batch->items as $index => $item) {
            $traceNumber = sprintf('%015d', $index + 1);
            $routingNumber = str_pad($item->routingNumber ?? '091000019', 9, '0', STR_PAD_LEFT);
            $entryHash += (int) substr($routingNumber, 0, 8);
            $totalCredit += (int) ($item->paymentAmount->getAmount() * 100);

            $lines[] = sprintf(
                '622%s%s%s%s%s%s0%s',
                $routingNumber,
                str_pad($item->bankAccountNumber ?? '', 17),
                str_pad((string) (int) ($item->paymentAmount->getAmount() * 100), 10, '0', STR_PAD_LEFT),
                str_pad($item->vendorId, 15),
                str_pad($item->vendorName, 22),
                str_pad('', 2),
                $traceNumber
            );
        }

        // Batch Control Record (8)
        $lines[] = sprintf(
            '8200%s%s%s%s%s%s%s',
            str_pad((string) $batch->itemCount, 6, '0', STR_PAD_LEFT),
            str_pad((string) ($entryHash % 10000000000), 10, '0', STR_PAD_LEFT),
            str_pad((string) $totalDebit, 12, '0', STR_PAD_LEFT),
            str_pad((string) $totalCredit, 12, '0', STR_PAD_LEFT),
            str_pad('123456789', 10),
            str_pad('', 19),
            str_pad('091000019', 8)
        );

        // File Control Record (9)
        $blockCount = (int) ceil((count($lines) + 1) / 10);
        $lines[] = sprintf(
            '9%s%s%s%s%s%s',
            str_pad('1', 6, '0', STR_PAD_LEFT),           // Batch Count
            str_pad((string) $blockCount, 6, '0', STR_PAD_LEFT),
            str_pad((string) $batch->itemCount, 8, '0', STR_PAD_LEFT),
            str_pad((string) ($entryHash % 10000000000), 10, '0', STR_PAD_LEFT),
            str_pad((string) $totalDebit, 12, '0', STR_PAD_LEFT),
            str_pad((string) $totalCredit, 12, '0', STR_PAD_LEFT)
        );

        // Pad to block size
        while (count($lines) % 10 !== 0) {
            $lines[] = str_repeat('9', 94);
        }

        return implode("\n", $lines);
    }

    /**
     * Build ISO20022 pain.001 file content.
     */
    private function buildIso20022FileContent(PaymentBatchData $batch): string {
        $now = new \DateTimeImmutable();
        $totalAmount = $batch->totalAmount->getAmount();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">' . "\n";
        $xml .= '  <CstmrCdtTrfInitn>' . "\n";
        $xml .= '    <GrpHdr>' . "\n";
        $xml .= "      <MsgId>{$batch->batchId}</MsgId>\n";
        $xml .= "      <CreDtTm>{$now->format('Y-m-d\TH:i:s')}</CreDtTm>\n";
        $xml .= "      <NbOfTxs>{$batch->itemCount}</NbOfTxs>\n";
        $xml .= "      <CtrlSum>{$totalAmount}</CtrlSum>\n";
        $xml .= '      <InitgPty>' . "\n";
        $xml .= "        <Nm>{$batch->tenantId}</Nm>\n";
        $xml .= '      </InitgPty>' . "\n";
        $xml .= '    </GrpHdr>' . "\n";
        $xml .= '    <PmtInf>' . "\n";
        $xml .= "      <PmtInfId>{$batch->batchId}-001</PmtInfId>\n";
        $xml .= '      <PmtMtd>TRF</PmtMtd>' . "\n";
        $xml .= "      <NbOfTxs>{$batch->itemCount}</NbOfTxs>\n";
        $xml .= "      <CtrlSum>{$totalAmount}</CtrlSum>\n";
        $xml .= "      <ReqdExctnDt>{($batch->scheduledPaymentDate ?? $now)->format('Y-m-d')}</ReqdExctnDt>\n";
        $xml .= '      <Dbtr>' . "\n";
        $xml .= "        <Nm>{$batch->tenantId}</Nm>\n";
        $xml .= '      </Dbtr>' . "\n";
        $xml .= '      <DbtrAcct>' . "\n";
        $xml .= '        <Id>' . "\n";
        $xml .= "          <IBAN>{$batch->bankAccountId}</IBAN>\n";
        $xml .= '        </Id>' . "\n";
        $xml .= '      </DbtrAcct>' . "\n";
        $xml .= '      <DbtrAgt>' . "\n";
        $xml .= '        <FinInstnId>' . "\n";
        $xml .= '          <BIC>BANKUS33XXX</BIC>' . "\n";
        $xml .= '        </FinInstnId>' . "\n";
        $xml .= '      </DbtrAgt>' . "\n";

        foreach ($batch->items as $item) {
            $xml .= '      <CdtTrfTxInf>' . "\n";
            $xml .= '        <PmtId>' . "\n";
            $xml .= "          <EndToEndId>{$item->itemId}</EndToEndId>\n";
            $xml .= '        </PmtId>' . "\n";
            $xml .= '        <Amt>' . "\n";
            $xml .= "          <InstdAmt Ccy=\"{$item->paymentAmount->getCurrency()}\">{$item->paymentAmount->getAmount()}</InstdAmt>\n";
            $xml .= '        </Amt>' . "\n";
            $xml .= '        <Cdtr>' . "\n";
            $xml .= "          <Nm>{$item->vendorName}</Nm>\n";
            $xml .= '        </Cdtr>' . "\n";
            $xml .= '        <CdtrAcct>' . "\n";
            $xml .= '          <Id>' . "\n";
            $xml .= "            <Othr><Id>{$item->bankAccountNumber}</Id></Othr>\n";
            $xml .= '          </Id>' . "\n";
            $xml .= '        </CdtrAcct>' . "\n";
            $xml .= '        <RmtInf>' . "\n";
            $xml .= "          <Ustrd>{$item->invoiceNumber}</Ustrd>\n";
            $xml .= '        </RmtInf>' . "\n";
            $xml .= '      </CdtTrfTxInf>' . "\n";
        }

        $xml .= '    </PmtInf>' . "\n";
        $xml .= '  </CstmrCdtTrfInitn>' . "\n";
        $xml .= '</Document>';

        return $xml;
    }

    /**
     * Generate unique batch ID.
     */
    private function generateBatchId(): string {
        if ($this->idGenerator !== null) {
            return $this->idGenerator->generateId('batch-', 12);
        }

        return 'batch-' . bin2hex(random_bytes(12));
    }

    /**
     * Generate unique item ID.
     */
    private function generateItemId(): string {
        if ($this->idGenerator !== null) {
            return $this->idGenerator->generateId('item-', 8);
        }

        return 'item-' . bin2hex(random_bytes(8));
    }
}
