<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\PaymentProcessingServiceInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchCreatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for managing payment batch processing and bank file generation.
 */
final readonly class PaymentProcessingService implements PaymentProcessingServiceInterface
{
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
        \DateTimeImmutable $paymentDate,
        string $currency,
        string $createdBy,
    ): PaymentBatchData {
        $batch = PaymentBatchData::create(
            batchId: $this->generateBatchId(),
            batchNumber: 'BN-' . bin2hex(random_bytes(4)),
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            paymentDate: $paymentDate,
            currency: $currency,
            createdBy: $createdBy,
        );

        $event = new PaymentBatchCreatedEvent(
            batchId: $batch->batchId,
            batchNumber: $batch->batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            paymentDate: $batch->paymentDate,
            currency: $currency,
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
        string $vendorId,
        string $vendorName,
        Money $amount,
        array $invoiceIds,
        string $vendorBankAccount,
        string $vendorBankRoutingNumber,
        ?string $vendorBankSwiftCode = null,
        ?string $checkPayeeName = null,
        ?string $checkMailingAddress = null,
    ): PaymentItemData {
        return match ($batch->paymentMethod) {
            'ACH' => PaymentItemData::forAch(
                paymentItemId: $this->generateItemId(),
                vendorId: $vendorId,
                vendorName: $vendorName,
                amount: $amount,
                invoiceIds: $invoiceIds,
                paymentReference: 'MULTIPLE',
                bankAccountNumber: $vendorBankAccount,
                routingNumber: $vendorBankRoutingNumber,
                bankName: 'Unknown',
                accountName: 'Unknown',
            ),
            'WIRE' => PaymentItemData::forWire(
                paymentItemId: $this->generateItemId(),
                vendorId: $vendorId,
                vendorName: $vendorName,
                amount: $amount,
                invoiceIds: $invoiceIds,
                paymentReference: 'MULTIPLE',
                bankAccountNumber: $vendorBankAccount,
                routingNumber: $vendorBankRoutingNumber,
                bankName: 'Unknown',
                accountName: 'Unknown',
            ),
            'CHECK' => PaymentItemData::forCheck(
                paymentItemId: $this->generateItemId(),
                vendorId: $vendorId,
                vendorName: $vendorName,
                amount: $amount,
                invoiceIds: $invoiceIds,
                paymentReference: 'MULTIPLE',
                checkNumber: $vendorBankAccount,
            ),
            default => throw new \InvalidArgumentException(
                "Unsupported payment method: {$batch->paymentMethod}"
            ),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function validateBatch(PaymentBatchData $batch): array {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function validatePaymentItem(PaymentItemData $item): array {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function generateBankFile(PaymentBatchData $batch): BankFileGenerationResult {
        return $this->generateNachaFile($batch);
    }

    /**
     * {@inheritDoc}
     */
    public function generateNachaFile(PaymentBatchData $batch): BankFileGenerationResult {
        return BankFileGenerationResult::nachaFile(
            batchId: $batch->batchId,
            fileName: "ACH_{$batch->batchId}.ach",
            fileContent: 'MOCK NACHA CONTENT',
            totalRecords: $batch->itemCount,
            totalAmount: $batch->totalAmount,
            includedPaymentIds: array_map(fn($item) => $item->paymentItemId, $batch->paymentItems),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function generateIso20022File(PaymentBatchData $batch): BankFileGenerationResult {
        return BankFileGenerationResult::iso20022File(
            batchId: $batch->batchId,
            fileName: "pain.001_{$batch->batchId}.xml",
            fileContent: 'MOCK ISO20022 CONTENT',
            totalRecords: $batch->itemCount,
            totalAmount: $batch->totalAmount,
            includedPaymentIds: array_map(fn($item) => $item->paymentItemId, $batch->paymentItems),
            messageType: 'pain.001.001.03',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function generateCheckPrintFile(PaymentBatchData $batch): BankFileGenerationResult {
        return BankFileGenerationResult::nachaFile(
            batchId: $batch->batchId,
            fileName: "checks_{$batch->batchId}.pdf",
            fileContent: 'MOCK CHECK CONTENT',
            totalRecords: $batch->itemCount,
            totalAmount: $batch->totalAmount,
            includedPaymentIds: array_map(fn($item) => $item->paymentItemId, $batch->paymentItems),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredApprovalLevels(PaymentBatchData $batch): int {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function canUserApprove(
        string $userId,
        PaymentBatchData $batch,
        int $approvalLevel,
    ): bool {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getVendorPaymentHistory(
        string $tenantId,
        string $vendorId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getPendingBatchesForApproval(
        string $tenantId,
        string $approverId,
    ): array {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function estimateBankFees(PaymentBatchData $batch): Money {
        return Money::of(0, 'USD');
    }

    /**
     * {@inheritDoc}
     */
    public function validateVendorBankingDetails(
        string $vendorId,
        string $paymentMethod,
    ): array {
        return ['valid' => true, 'errors' => []];
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
