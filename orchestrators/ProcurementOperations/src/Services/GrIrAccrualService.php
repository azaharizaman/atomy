<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\AccrualManagementServiceInterface;
use Nexus\ProcurementOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for managing GR/IR (Goods Receipt / Invoice Receipt) accruals.
 */
class GrIrAccrualService implements AccrualManagementServiceInterface
{
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly LoggerInterface $logger = new NullLogger(),
        protected readonly ?SecureIdGeneratorInterface $idGenerator = null,
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
        string $invoiceId,
        string $invoiceNumber,
        Money $invoiceAmount,
        \DateTimeImmutable $invoiceDate,
        string $matchedBy,
    ): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId);
        if ($accrual === null) {
            throw new \InvalidArgumentException("Accrual not found: {$accrualId}");
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

        $this->logger->info('GR/IR accrual matched with invoice', [
            'accrual_id' => $accrual->accrualId,
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
        string $invoiceId,
        string $invoiceNumber,
        Money $matchedAmount,
        Money $varianceAmount,
        string $varianceReason,
        string $matchedBy,
    ): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId);
        if ($accrual === null) { throw new \InvalidArgumentException("Accrual not found: {$accrualId}"); }

        return $accrual->withInvoiceMatch(
            invoiceId: $invoiceId,
            invoiceDate: new \DateTimeImmutable(),
            invoiceAmount: $matchedAmount,
            matchedBy: $matchedBy,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function writeOffAccrual(
        string $accrualId,
        string $writeOffReason,
        string $writeOffBy,
        ?string $writeOffAccountId = null,
    ): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId);
        if ($accrual === null) { throw new \InvalidArgumentException("Accrual not found: {$accrualId}"); }

        $writtenOff = $accrual->withWriteOff(
            reason: $writeOffReason,
            writtenOffBy: $writeOffBy,
        );

        $this->logger->warning('GR/IR accrual written off', [
            'accrual_id' => $accrual->accrualId,
            'reason' => $writeOffReason,
            'written_off_by' => $writeOffBy,
        ]);

        return $writtenOff;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnmatchedAccruals(string $tenantId, ?\DateTimeImmutable $asOfDate = null): array { return []; }

    /**
     * {@inheritDoc}
     */
    public function getAgedAccruals(string $tenantId, int $agingThresholdDays = 30, ?\DateTimeImmutable $asOfDate = null): array { return []; }

    /**
     * {@inheritDoc}
     */
    public function getAccrualsByVendor(string $tenantId, string $vendorId, bool $unmatchedOnly = true): array { return []; }

    /**
     * {@inheritDoc}
     */
    public function getAccrualsByPurchaseOrder(string $tenantId, string $purchaseOrderId): array { return []; }

    /**
     * {@inheritDoc}
     */
    public function getTotalAccrualBalance(string $tenantId, ?\DateTimeImmutable $asOfDate = null): Money { return Money::of(0, 'USD'); }

    /**
     * {@inheritDoc}
     */
    public function generateAgingReport(string $tenantId, \DateTimeImmutable $asOfDate, array $agingBuckets = [30, 60, 90]): array { return ['as_of_date' => $asOfDate->format('Y-m-d'), 'total_accrual_balance' => Money::of(0, 'USD'), 'aging_buckets' => [], 'by_vendor' => []]; }

    /**
     * {@inheritDoc}
     */
    public function suggestMatchingInvoices(string $accrualId, float $tolerancePercent = 5.0): array { return []; }

    /**
     * {@inheritDoc}
     */
    public function autoMatchAccruals(string $tenantId, float $tolerancePercent = 0.01, string $matchedBy = 'SYSTEM'): array { return ['matched_count' => 0, 'total_matched_amount' => Money::of(0, 'USD'), 'matches' => []]; }

    /**
     * {@inheritDoc}
     */
    public function reverseAccrual(string $accrualId, string $reversalReason, string $reversedBy): GrIrAccrualData {
        $accrual = $this->getAccrual($accrualId);
        if ($accrual === null) { throw new \InvalidArgumentException("Accrual not found: {$accrualId}"); }
        return $accrual;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccrual(string $accrualId): ?GrIrAccrualData { return null; }

    /**
     * {@inheritDoc}
     */
    public function calculatePeriodAccrualEntries(string $tenantId, \DateTimeImmutable $periodEndDate): array { return ['period_end_date' => $periodEndDate->format('Y-m-d'), 'accrual_entries' => [], 'total_debit' => Money::of(0, 'USD'), 'total_credit' => Money::of(0, 'USD')]; }

    public function generateAccrualId(): string
    {
        return $this->idGenerator?->generateId('accr-', 12) ?? ('accr-' . bin2hex(random_bytes(12)));
    }
}
