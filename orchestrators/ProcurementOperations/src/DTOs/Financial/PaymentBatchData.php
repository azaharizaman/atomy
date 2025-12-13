<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment Batch Data
 * 
 * Represents a batch of vendor payments for processing.
 * Supports multiple payment methods and bank file generation.
 */
final readonly class PaymentBatchData
{
    /**
     * @param array<PaymentItemData> $paymentItems
     */
    public function __construct(
        public string $batchId,
        public string $batchNumber,
        public string $tenantId,
        public string $paymentMethod, // 'ach', 'wire', 'check', 'virtual_card'
        public string $bankAccountId,
        public \DateTimeImmutable $paymentDate,
        public string $currency,
        public Money $totalAmount,
        public int $itemCount,
        public array $paymentItems,
        public string $status, // 'draft', 'pending_approval', 'approved', 'processing', 'completed', 'failed', 'cancelled'
        public \DateTimeImmutable $createdAt,
        public string $createdBy,
        public ?\DateTimeImmutable $approvedAt = null,
        public ?string $approvedBy = null,
        public ?\DateTimeImmutable $processedAt = null,
        public ?string $bankFileReference = null,
        public ?string $bankFileName = null,
        public array $approvalChain = [],
        public array $metadata = [],
    ) {}

    /**
     * Create new payment batch
     */
    public static function create(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        string $paymentMethod,
        string $bankAccountId,
        \DateTimeImmutable $paymentDate,
        string $currency,
        string $createdBy,
    ): self {
        return new self(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            paymentDate: $paymentDate,
            currency: $currency,
            totalAmount: Money::zero($currency),
            itemCount: 0,
            paymentItems: [],
            status: 'draft',
            createdAt: new \DateTimeImmutable(),
            createdBy: $createdBy,
        );
    }

    /**
     * Check if batch is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if batch is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if batch is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if batch is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if batch failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if batch can be edited
     */
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if batch can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'pending_approval' && $this->itemCount > 0;
    }

    /**
     * Check if batch can be processed
     */
    public function canProcess(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Add payment item to batch
     */
    public function withPaymentItem(PaymentItemData $item): self
    {
        $items = $this->paymentItems;
        $items[] = $item;

        return new self(
            batchId: $this->batchId,
            batchNumber: $this->batchNumber,
            tenantId: $this->tenantId,
            paymentMethod: $this->paymentMethod,
            bankAccountId: $this->bankAccountId,
            paymentDate: $this->paymentDate,
            currency: $this->currency,
            totalAmount: $this->totalAmount->add($item->amount),
            itemCount: $this->itemCount + 1,
            paymentItems: $items,
            status: $this->status,
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            approvedAt: $this->approvedAt,
            approvedBy: $this->approvedBy,
            processedAt: $this->processedAt,
            bankFileReference: $this->bankFileReference,
            bankFileName: $this->bankFileName,
            approvalChain: $this->approvalChain,
            metadata: $this->metadata,
        );
    }

    /**
     * Submit for approval
     */
    public function withSubmitForApproval(): self
    {
        if ($this->status !== 'draft') {
            throw new \RuntimeException('Can only submit draft batches for approval');
        }

        if ($this->itemCount === 0) {
            throw new \RuntimeException('Cannot submit empty batch for approval');
        }

        return new self(
            batchId: $this->batchId,
            batchNumber: $this->batchNumber,
            tenantId: $this->tenantId,
            paymentMethod: $this->paymentMethod,
            bankAccountId: $this->bankAccountId,
            paymentDate: $this->paymentDate,
            currency: $this->currency,
            totalAmount: $this->totalAmount,
            itemCount: $this->itemCount,
            paymentItems: $this->paymentItems,
            status: 'pending_approval',
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            approvedAt: $this->approvedAt,
            approvedBy: $this->approvedBy,
            processedAt: $this->processedAt,
            bankFileReference: $this->bankFileReference,
            bankFileName: $this->bankFileName,
            approvalChain: $this->approvalChain,
            metadata: $this->metadata,
        );
    }

    /**
     * Approve batch
     */
    public function withApproval(string $approvedBy, ?\DateTimeImmutable $approvedAt = null): self
    {
        if ($this->status !== 'pending_approval') {
            throw new \RuntimeException('Can only approve pending batches');
        }

        $chain = $this->approvalChain;
        $chain[] = [
            'approved_by' => $approvedBy,
            'approved_at' => ($approvedAt ?? new \DateTimeImmutable())->format('c'),
            'action' => 'approve',
        ];

        return new self(
            batchId: $this->batchId,
            batchNumber: $this->batchNumber,
            tenantId: $this->tenantId,
            paymentMethod: $this->paymentMethod,
            bankAccountId: $this->bankAccountId,
            paymentDate: $this->paymentDate,
            currency: $this->currency,
            totalAmount: $this->totalAmount,
            itemCount: $this->itemCount,
            paymentItems: $this->paymentItems,
            status: 'approved',
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            approvedAt: $approvedAt ?? new \DateTimeImmutable(),
            approvedBy: $approvedBy,
            processedAt: $this->processedAt,
            bankFileReference: $this->bankFileReference,
            bankFileName: $this->bankFileName,
            approvalChain: $chain,
            metadata: $this->metadata,
        );
    }

    /**
     * Mark as processing with bank file details
     */
    public function withProcessing(string $bankFileReference, string $bankFileName): self
    {
        if ($this->status !== 'approved') {
            throw new \RuntimeException('Can only process approved batches');
        }

        return new self(
            batchId: $this->batchId,
            batchNumber: $this->batchNumber,
            tenantId: $this->tenantId,
            paymentMethod: $this->paymentMethod,
            bankAccountId: $this->bankAccountId,
            paymentDate: $this->paymentDate,
            currency: $this->currency,
            totalAmount: $this->totalAmount,
            itemCount: $this->itemCount,
            paymentItems: $this->paymentItems,
            status: 'processing',
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            approvedAt: $this->approvedAt,
            approvedBy: $this->approvedBy,
            processedAt: new \DateTimeImmutable(),
            bankFileReference: $bankFileReference,
            bankFileName: $bankFileName,
            approvalChain: $this->approvalChain,
            metadata: $this->metadata,
        );
    }

    /**
     * Mark batch as completed
     */
    public function withCompletion(): self
    {
        return new self(
            batchId: $this->batchId,
            batchNumber: $this->batchNumber,
            tenantId: $this->tenantId,
            paymentMethod: $this->paymentMethod,
            bankAccountId: $this->bankAccountId,
            paymentDate: $this->paymentDate,
            currency: $this->currency,
            totalAmount: $this->totalAmount,
            itemCount: $this->itemCount,
            paymentItems: $this->paymentItems,
            status: 'completed',
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            approvedAt: $this->approvedAt,
            approvedBy: $this->approvedBy,
            processedAt: $this->processedAt,
            bankFileReference: $this->bankFileReference,
            bankFileName: $this->bankFileName,
            approvalChain: $this->approvalChain,
            metadata: $this->metadata,
        );
    }

    /**
     * Get vendors in batch
     */
    public function getVendorIds(): array
    {
        return array_unique(array_map(fn($item) => $item->vendorId, $this->paymentItems));
    }

    /**
     * Get invoice IDs in batch
     */
    public function getInvoiceIds(): array
    {
        $invoiceIds = [];
        foreach ($this->paymentItems as $item) {
            $invoiceIds = array_merge($invoiceIds, $item->invoiceIds);
        }
        return array_unique($invoiceIds);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'batch_id' => $this->batchId,
            'batch_number' => $this->batchNumber,
            'tenant_id' => $this->tenantId,
            'payment_method' => $this->paymentMethod,
            'bank_account_id' => $this->bankAccountId,
            'payment_date' => $this->paymentDate->format('Y-m-d'),
            'currency' => $this->currency,
            'total_amount' => $this->totalAmount->toArray(),
            'item_count' => $this->itemCount,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('c'),
            'created_by' => $this->createdBy,
            'approved_at' => $this->approvedAt?->format('c'),
            'approved_by' => $this->approvedBy,
            'processed_at' => $this->processedAt?->format('c'),
            'bank_file_reference' => $this->bankFileReference,
            'bank_file_name' => $this->bankFileName,
            'vendor_count' => count($this->getVendorIds()),
            'invoice_count' => count($this->getInvoiceIds()),
        ];
    }
}
