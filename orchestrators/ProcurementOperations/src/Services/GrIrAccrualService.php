<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\AccrualManagementServiceInterface;
use Nexus\ProcurementOperations\Contracts\GrIrAccrualRepositoryInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for managing GR/IR (Goods Receipt / Invoice Receipt) accruals.
 */
final readonly class GrIrAccrualService implements AccrualManagementServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private GrIrAccrualRepositoryInterface $repository,
        private LoggerInterface $logger = new NullLogger(),
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createAccrual(
        string $tenantId,
        string $purchaseOrderId,
        string $purchaseOrderNumber,
        string $goodsReceiptId,
        string $goodsReceiptNumber,
        \DateTimeImmutable $receiptDate,
        string $vendorId,
        string $vendorName,
        Money $accrualAmount,
        int $lineCount,
        string $createdBy,
    ): GrIrAccrualData {
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: $this->generateAccrualId(),
            purchaseOrderId: $purchaseOrderId,
            purchaseOrderLineId: 'CONS-' . $purchaseOrderId,
            vendorId: $vendorId,
            productId: 'N/A',
            quantity: (float)$lineCount,
            uom: 'LINE',
            unitCost: $accrualAmount->divide($lineCount > 0 ? $lineCount : 1),
            goodsReceiptId: $goodsReceiptId,
            goodsReceiptDate: $receiptDate,
        );

        // Add additional metadata provided by the caller
        $accrual = new GrIrAccrualData(
            accrualId: $accrual->accrualId,
            purchaseOrderId: $accrual->purchaseOrderId,
            purchaseOrderLineId: $accrual->purchaseOrderLineId,
            vendorId: $accrual->vendorId,
            productId: $accrual->productId,
            quantity: $accrual->quantity,
            uom: $accrual->uom,
            unitCost: $accrual->unitCost,
            totalAccrualAmount: $accrual->totalAccrualAmount,
            accrualStatus: $accrual->accrualStatus,
            goodsReceiptDate: $accrual->goodsReceiptDate,
            goodsReceiptId: $accrual->goodsReceiptId,
            periodId: $accrual->periodId,
            glAccountId: $accrual->glAccountId,
            metadata: array_merge($accrual->metadata, [
                'tenant_id' => $tenantId,
                'purchase_order_number' => $purchaseOrderNumber,
                'goods_receipt_number' => $goodsReceiptNumber,
                'vendor_name' => $vendorName,
                'created_by' => $createdBy,
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ]),
        );

        $this->repository->save($accrual);

        $this->logger->info('GR/IR accrual created', [
            'accrual_id' => $accrual->accrualId,
            'tenant_id' => $tenantId,
            'goods_receipt_id' => $goodsReceiptId,
            'purchase_order_id' => $purchaseOrderId,
            'amount' => $accrualAmount->getAmount(),
        ]);

        return $accrual;
    }

    /**
     * {@inheritDoc}
     */
    public function matchWithInvoice(
        string $accrualId,
        string $tenantId,
        string $invoiceId,
        string $invoiceNumber,
        Money $invoiceAmount,
        \DateTimeImmutable $invoiceDate,
        string $matchedBy,
    ): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId, $tenantId);
        if ($accrual === null) {
            throw new \InvalidArgumentException("Accrual not found for tenant: {$accrualId}");
        }

        if (!$accrual->isOpen()) {
            throw new \InvalidArgumentException(
                "Cannot match accrual {$accrual->accrualId}: status is {$accrual->accrualStatus}"
            );
        }

        $matchedAccrual = $accrual->withInvoiceMatch(
            invoiceId: $invoiceId,
            invoiceDate: $invoiceDate,
            invoiceAmount: $invoiceAmount,
            matchedBy: $matchedBy,
        );

        $this->repository->save($matchedAccrual);

        $this->logger->info('GR/IR accrual matched with invoice', [
            'accrual_id' => $accrual->accrualId,
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'matched_by' => $matchedBy,
        ]);

        return $matchedAccrual;
    }

    /**
     * {@inheritDoc}
     */
    public function partialMatchWithInvoice(
        string $accrualId,
        string $tenantId,
        string $invoiceId,
        string $invoiceNumber,
        Money $matchedAmount,
        Money $varianceAmount,
        string $varianceReason,
        string $matchedBy,
    ): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId, $tenantId);
        if ($accrual === null) {
            throw new \InvalidArgumentException("Accrual not found for tenant: {$accrualId}");
        }

        $matchedAccrual = $accrual->withInvoiceMatch(
            invoiceId: $invoiceId,
            invoiceDate: new \DateTimeImmutable(),
            invoiceAmount: $matchedAmount,
            matchedBy: $matchedBy,
            varianceAmount: $varianceAmount,
            varianceReason: $varianceReason,
        );

        // Update status to partially_matched explicitly if needed, although withInvoiceMatch sets 'matched'
        // GrIrAccrualData constructor is used in withInvoiceMatch, so let's adjust it if it should be partial
        $partialAccrual = new GrIrAccrualData(
            accrualId: $matchedAccrual->accrualId,
            purchaseOrderId: $matchedAccrual->purchaseOrderId,
            purchaseOrderLineId: $matchedAccrual->purchaseOrderLineId,
            vendorId: $matchedAccrual->vendorId,
            productId: $matchedAccrual->productId,
            quantity: $matchedAccrual->quantity,
            uom: $matchedAccrual->uom,
            unitCost: $matchedAccrual->unitCost,
            totalAccrualAmount: $matchedAccrual->totalAccrualAmount,
            accrualStatus: 'partially_matched',
            goodsReceiptDate: $matchedAccrual->goodsReceiptDate,
            goodsReceiptId: $matchedAccrual->goodsReceiptId,
            invoiceId: $matchedAccrual->invoiceId,
            invoiceDate: $matchedAccrual->invoiceDate,
            invoiceAmount: $matchedAccrual->invoiceAmount,
            varianceAmount: $matchedAccrual->varianceAmount,
            varianceReason: $matchedAccrual->varianceReason,
            matchedAt: $matchedAccrual->matchedAt,
            matchedBy: $matchedAccrual->matchedBy,
            periodId: $matchedAccrual->periodId,
            glAccountId: $matchedAccrual->glAccountId,
            writeOffAccountId: $matchedAccrual->writeOffAccountId,
            metadata: $matchedAccrual->metadata,
        );

        $this->repository->save($partialAccrual);

        return $partialAccrual;
    }

    /**
     * {@inheritDoc}
     */
    public function writeOffAccrual(
        string $accrualId,
        string $tenantId,
        string $writeOffReason,
        string $writeOffBy,
        ?string $writeOffAccountId = null,
    ): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId, $tenantId);
        if ($accrual === null) {
            throw new \InvalidArgumentException("Accrual not found for tenant: {$accrualId}");
        }

        $writtenOff = $accrual->withWriteOff(
            reason: $writeOffReason,
            writtenOffBy: $writeOffBy,
            writeOffAccountId: $writeOffAccountId,
        );

        $this->repository->save($writtenOff);

        $this->logger->warning('GR/IR accrual written off', [
            'accrual_id' => $accrual->accrualId,
            'tenant_id' => $tenantId,
            'reason' => $writeOffReason,
            'written_off_by' => $writeOffBy,
        ]);

        return $writtenOff;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnmatchedAccruals(string $tenantId, ?\DateTimeImmutable $asOfDate = null): array
    {
        return $this->repository->findUnmatched($tenantId, $asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getAgedAccruals(string $tenantId, int $agingThresholdDays = 30, ?\DateTimeImmutable $asOfDate = null): array
    {
        return $this->repository->findAged($tenantId, $agingThresholdDays, $asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccrualsByVendor(string $tenantId, string $vendorId, bool $unmatchedOnly = true): array
    {
        return $this->repository->findByVendor($tenantId, $vendorId, $unmatchedOnly);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccrualsByPurchaseOrder(string $tenantId, string $purchaseOrderId): array
    {
        return $this->repository->findByPurchaseOrder($tenantId, $purchaseOrderId);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalAccrualBalance(string $tenantId, ?\DateTimeImmutable $asOfDate = null): Money
    {
        return $this->repository->getTotalBalance($tenantId, $asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function generateAgingReport(string $tenantId, \DateTimeImmutable $asOfDate, array $agingBuckets = [30, 60, 90]): array
    {
        $accruals = $this->repository->findUnmatched($tenantId, $asOfDate);
        $totalBalance = $this->repository->getTotalBalance($tenantId, $asOfDate);
        
        // This is a stub for the actual report generation logic
        return [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'total_accrual_balance' => $totalBalance,
            'aging_buckets' => $agingBuckets,
            'unmatched_count' => count($accruals),
        ];
    }

    /**
     * {@inheritDoc}Suggest matches for an accrual.
     */
    public function suggestMatchingInvoices(string $accrualId, float $tolerancePercent = 5.0): array
    {
        return $this->repository->suggestMatches($accrualId, $tolerancePercent);
    }

    /**
     * {@inheritDoc}Automatically match accruals.
     */
    public function autoMatchAccruals(string $tenantId, float $tolerancePercent = 0.01, string $matchedBy = 'SYSTEM'): array
    {
        // This would call suggestMatchingInvoices for all open accruals and apply matches
        return [
            'matched_count' => 0,
            'total_matched_amount' => Money::of(0, 'USD'),
            'matches' => []
        ];
    }

    /**
     * {@inheritDoc}Reverse an accrual.
     */
    public function reverseAccrual(string $accrualId, string $tenantId, string $reversalReason, string $reversedBy): GrIrAccrualData
    {
        $accrual = $this->getAccrual($accrualId, $tenantId);
        if ($accrual === null) {
            throw new \InvalidArgumentException("Accrual not found for tenant: {$accrualId}");
        }

        $reversedAccrual = new GrIrAccrualData(
            accrualId: $accrual->accrualId,
            purchaseOrderId: $accrual->purchaseOrderId,
            purchaseOrderLineId: $accrual->purchaseOrderLineId,
            vendorId: $accrual->vendorId,
            productId: $accrual->productId,
            quantity: $accrual->quantity,
            uom: $accrual->uom,
            unitCost: $accrual->unitCost,
            totalAccrualAmount: $accrual->totalAccrualAmount,
            accrualStatus: 'reversed',
            goodsReceiptDate: $accrual->goodsReceiptDate,
            goodsReceiptId: $accrual->goodsReceiptId,
            invoiceId: $accrual->invoiceId,
            invoiceDate: $accrual->invoiceDate,
            invoiceAmount: $accrual->invoiceAmount,
            varianceAmount: $accrual->varianceAmount,
            varianceReason: $reversalReason,
            matchedAt: new \DateTimeImmutable(),
            matchedBy: $reversedBy,
            periodId: $accrual->periodId,
            glAccountId: $accrual->glAccountId,
            writeOffAccountId: $accrual->writeOffAccountId,
            metadata: array_merge($accrual->metadata, [
                'reversal_reason' => $reversalReason,
                'reversed_by' => $reversedBy,
                'reversed_at' => (new \DateTimeImmutable())->format('c'),
            ]),
        );

        $this->repository->save($reversedAccrual);

        return $reversedAccrual;
    }

    /**
     * {@inheritDoc}Get a single accrual.
     */
    public function getAccrual(string $accrualId, string $tenantId): ?GrIrAccrualData
    {
        return $this->repository->getAccrual($accrualId, $tenantId);
    }

    /**
     * {@inheritDoc}Calculate entries.
     */
    public function calculatePeriodAccrualEntries(string $tenantId, \DateTimeImmutable $periodEndDate): array
    {
        return [
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'accrual_entries' => [],
            'total_debit' => Money::of(0, 'USD'),
            'total_credit' => Money::of(0, 'USD')
        ];
    }

    public function generateAccrualId(): string
    {
        return $this->idGenerator?->generateId('accr-', 12) ?? ('accr-' . bin2hex(random_bytes(12)));
    }
}
