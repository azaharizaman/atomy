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
 *
 * This service handles:
 * - Creating accruals when goods are received before invoice
 * - Matching accruals with invoices (full and partial)
 * - Writing off aged or unmatchable accruals
 * - Generating accrual aging reports
 * - Suggesting matches for unmatched accruals
 *
 * GR/IR Accrual Process:
 * 1. Goods Receipt → Create GR/IR Accrual (DR GR/IR Clearing, CR Accrued Liability)
 * 2. Invoice Receipt → Match Accrual (DR Accrued Liability, CR Accounts Payable)
 * 3. Aged/Unmatched → Write-off (DR GR/IR Clearing, CR Expense/Income)
 *
 * @package Nexus\ProcurementOperations\Services
 */
final readonly class GrIrAccrualService implements AccrualManagementServiceInterface
{
    /**
     * Default aging threshold in days for flagging aged accruals.
     */
    private const DEFAULT_AGING_THRESHOLD_DAYS = 90;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
        private ?SecureIdGeneratorInterface $idGenerator = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createAccrual(
        string $tenantId,
        string $goodsReceiptId,
        string $goodsReceiptNumber,
        string $purchaseOrderId,
        string $purchaseOrderNumber,
        string $vendorId,
        string $vendorName,
        Money $accrualAmount,
        string $productId,
        string $productName,
        float $quantity,
        string $uom,
        ?string $costCenterId = null,
        ?string $projectId = null,
        array $metadata = [],
    ): GrIrAccrualData {
        $accrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: $this->generateAccrualId(),
            tenantId: $tenantId,
            goodsReceiptId: $goodsReceiptId,
            goodsReceiptNumber: $goodsReceiptNumber,
            purchaseOrderId: $purchaseOrderId,
            purchaseOrderNumber: $purchaseOrderNumber,
            vendorId: $vendorId,
            vendorName: $vendorName,
            accrualAmount: $accrualAmount,
            productId: $productId,
            productName: $productName,
            quantity: $quantity,
            uom: $uom,
            goodsReceiptDate: new \DateTimeImmutable(),
            costCenterId: $costCenterId,
            projectId: $projectId,
            metadata: $metadata,
        );

        $this->logger->info('GR/IR accrual created', [
            'accrual_id' => $accrual->accrualId,
            'goods_receipt_id' => $goodsReceiptId,
            'purchase_order_id' => $purchaseOrderId,
            'amount' => $accrualAmount->getAmount(),
            'vendor_id' => $vendorId,
        ]);

        return $accrual;
    }

    /**
     * {@inheritDoc}
     */
    public function matchWithInvoice(
        GrIrAccrualData $accrual,
        string $invoiceId,
        string $invoiceNumber,
        Money $invoiceAmount,
        \DateTimeImmutable $invoiceDate,
        ?string $matchedBy = null,
    ): GrIrAccrualData {
        if ($accrual->status !== 'OPEN') {
            throw new \InvalidArgumentException(
                "Cannot match accrual {$accrual->accrualId}: status is {$accrual->status}"
            );
        }

        $matchedAccrual = $accrual->withInvoiceMatch(
            invoiceId: $invoiceId,
            invoiceNumber: $invoiceNumber,
            invoiceAmount: $invoiceAmount,
            invoiceDate: $invoiceDate,
        );

        $this->logger->info('GR/IR accrual matched with invoice', [
            'accrual_id' => $accrual->accrualId,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber,
            'invoice_amount' => $invoiceAmount->getAmount(),
            'accrual_amount' => $accrual->accrualAmount->getAmount(),
            'variance' => $matchedAccrual->varianceAmount?->getAmount(),
            'matched_by' => $matchedBy,
        ]);

        return $matchedAccrual;
    }

    /**
     * {@inheritDoc}
     */
    public function partialMatchWithInvoice(
        GrIrAccrualData $accrual,
        string $invoiceId,
        string $invoiceNumber,
        Money $matchedAmount,
        \DateTimeImmutable $invoiceDate,
        ?string $matchedBy = null,
    ): array {
        if ($accrual->status !== 'OPEN') {
            throw new \InvalidArgumentException(
                "Cannot partial match accrual {$accrual->accrualId}: status is {$accrual->status}"
            );
        }

        if ($matchedAmount->getAmount() >= $accrual->accrualAmount->getAmount()) {
            throw new \InvalidArgumentException(
                'Matched amount must be less than accrual amount for partial match'
            );
        }

        // Create matched portion
        $matchedPortion = GrIrAccrualData::fromGoodsReceipt(
            accrualId: $this->generateAccrualId(),
            tenantId: $accrual->tenantId,
            goodsReceiptId: $accrual->goodsReceiptId,
            goodsReceiptNumber: $accrual->goodsReceiptNumber,
            purchaseOrderId: $accrual->purchaseOrderId,
            purchaseOrderNumber: $accrual->purchaseOrderNumber,
            vendorId: $accrual->vendorId,
            vendorName: $accrual->vendorName,
            accrualAmount: $matchedAmount,
            productId: $accrual->productId,
            productName: $accrual->productName,
            quantity: $accrual->quantity * ($matchedAmount->getAmount() / $accrual->accrualAmount->getAmount()),
            uom: $accrual->uom,
            goodsReceiptDate: $accrual->goodsReceiptDate,
            costCenterId: $accrual->costCenterId,
            projectId: $accrual->projectId,
            metadata: array_merge($accrual->metadata, [
                'partial_match_from' => $accrual->accrualId,
                'partial_match_type' => 'matched',
            ]),
        )->withInvoiceMatch(
            invoiceId: $invoiceId,
            invoiceNumber: $invoiceNumber,
            invoiceAmount: $matchedAmount,
            invoiceDate: $invoiceDate,
        );

        // Create remaining portion
        $remainingAmount = Money::of(
            $accrual->accrualAmount->getAmount() - $matchedAmount->getAmount(),
            $accrual->accrualAmount->getCurrency()
        );

        $remainingPortion = GrIrAccrualData::fromGoodsReceipt(
            accrualId: $this->generateAccrualId(),
            tenantId: $accrual->tenantId,
            goodsReceiptId: $accrual->goodsReceiptId,
            goodsReceiptNumber: $accrual->goodsReceiptNumber,
            purchaseOrderId: $accrual->purchaseOrderId,
            purchaseOrderNumber: $accrual->purchaseOrderNumber,
            vendorId: $accrual->vendorId,
            vendorName: $accrual->vendorName,
            accrualAmount: $remainingAmount,
            productId: $accrual->productId,
            productName: $accrual->productName,
            quantity: $accrual->quantity * ($remainingAmount->getAmount() / $accrual->accrualAmount->getAmount()),
            uom: $accrual->uom,
            goodsReceiptDate: $accrual->goodsReceiptDate,
            costCenterId: $accrual->costCenterId,
            projectId: $accrual->projectId,
            metadata: array_merge($accrual->metadata, [
                'partial_match_from' => $accrual->accrualId,
                'partial_match_type' => 'remaining',
            ]),
        );

        $this->logger->info('GR/IR accrual partially matched', [
            'original_accrual_id' => $accrual->accrualId,
            'matched_accrual_id' => $matchedPortion->accrualId,
            'remaining_accrual_id' => $remainingPortion->accrualId,
            'matched_amount' => $matchedAmount->getAmount(),
            'remaining_amount' => $remainingAmount->getAmount(),
            'matched_by' => $matchedBy,
        ]);

        return [
            'matched' => $matchedPortion,
            'remaining' => $remainingPortion,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function writeOffAccrual(
        GrIrAccrualData $accrual,
        string $writeOffReason,
        string $writeOffBy,
        ?string $writeOffAccountId = null,
        ?string $approvalReference = null,
    ): GrIrAccrualData {
        if ($accrual->status !== 'OPEN') {
            throw new \InvalidArgumentException(
                "Cannot write off accrual {$accrual->accrualId}: status is {$accrual->status}"
            );
        }

        $writtenOff = $accrual->withWriteOff(
            writeOffReason: $writeOffReason,
            writeOffBy: $writeOffBy,
            writeOffAccountId: $writeOffAccountId,
        );

        $this->logger->warning('GR/IR accrual written off', [
            'accrual_id' => $accrual->accrualId,
            'amount' => $accrual->accrualAmount->getAmount(),
            'reason' => $writeOffReason,
            'written_off_by' => $writeOffBy,
            'approval_reference' => $approvalReference,
        ]);

        return $writtenOff;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnmatchedAccruals(
        string $tenantId,
        ?string $vendorId = null,
        ?string $purchaseOrderId = null,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array {
        // This would typically query a repository
        // Returning empty array - actual implementation would filter stored accruals
        $this->logger->debug('Querying unmatched accruals', [
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'purchase_order_id' => $purchaseOrderId,
        ]);

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAgedAccruals(
        string $tenantId,
        int $agingThresholdDays = self::DEFAULT_AGING_THRESHOLD_DAYS,
        ?string $vendorId = null,
    ): array {
        // This would typically query a repository for accruals older than threshold
        $this->logger->debug('Querying aged accruals', [
            'tenant_id' => $tenantId,
            'threshold_days' => $agingThresholdDays,
            'vendor_id' => $vendorId,
        ]);

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function generateAgingReport(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
        ?string $vendorId = null,
        array $agingBuckets = [30, 60, 90, 120],
    ): array {
        // Initialize buckets
        $bucketResults = [];
        $previousBoundary = 0;

        foreach ($agingBuckets as $days) {
            $bucketKey = "{$previousBoundary}-{$days}";
            $bucketResults[$bucketKey] = [
                'label' => "{$previousBoundary}-{$days} days",
                'count' => 0,
                'total_amount' => Money::of(0, 'USD'),
                'accruals' => [],
            ];
            $previousBoundary = $days;
        }

        // Add overflow bucket
        $bucketResults["{$previousBoundary}+"] = [
            'label' => "Over {$previousBoundary} days",
            'count' => 0,
            'total_amount' => Money::of(0, 'USD'),
            'accruals' => [],
        ];

        return [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'total_open_accruals' => 0,
            'total_open_amount' => Money::of(0, 'USD'),
            'buckets' => $bucketResults,
            'summary' => [
                'current' => Money::of(0, 'USD'),
                'overdue' => Money::of(0, 'USD'),
                'critical' => Money::of(0, 'USD'),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function suggestMatchingInvoices(
        GrIrAccrualData $accrual,
        float $amountTolerancePercentage = 5.0,
        int $dateRangeDays = 30,
    ): array {
        // This would typically query vendor invoices that could match this accrual
        // Based on PO reference, amount within tolerance, and date range
        
        $this->logger->debug('Suggesting matching invoices for accrual', [
            'accrual_id' => $accrual->accrualId,
            'vendor_id' => $accrual->vendorId,
            'po_number' => $accrual->purchaseOrderNumber,
            'accrual_amount' => $accrual->accrualAmount->getAmount(),
            'amount_tolerance' => $amountTolerancePercentage,
            'date_range_days' => $dateRangeDays,
        ]);

        return [
            'accrual_id' => $accrual->accrualId,
            'suggested_invoices' => [],
            'match_criteria' => [
                'vendor_id' => $accrual->vendorId,
                'purchase_order' => $accrual->purchaseOrderNumber,
                'amount_range' => [
                    'min' => $accrual->accrualAmount->getAmount() * (1 - $amountTolerancePercentage / 100),
                    'max' => $accrual->accrualAmount->getAmount() * (1 + $amountTolerancePercentage / 100),
                ],
                'date_range' => [
                    'from' => $accrual->goodsReceiptDate->modify("-{$dateRangeDays} days")->format('Y-m-d'),
                    'to' => (new \DateTimeImmutable())->modify("+{$dateRangeDays} days")->format('Y-m-d'),
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function autoMatchAccruals(
        string $tenantId,
        ?string $vendorId = null,
        float $amountTolerancePercentage = 0.0,
        bool $exactMatchOnly = true,
    ): array {
        $matchedCount = 0;
        $matchedAmount = Money::of(0, 'USD');
        $unmatchedCount = 0;
        $errors = [];

        $this->logger->info('Auto-matching accruals', [
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'exact_match_only' => $exactMatchOnly,
            'tolerance' => $amountTolerancePercentage,
        ]);

        // This would iterate through unmatched accruals and invoices
        // performing automatic matching based on criteria

        return [
            'matched_count' => $matchedCount,
            'matched_amount' => $matchedAmount,
            'unmatched_count' => $unmatchedCount,
            'errors' => $errors,
            'match_criteria' => [
                'exact_match_only' => $exactMatchOnly,
                'amount_tolerance_percentage' => $amountTolerancePercentage,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function reverseAccrual(
        GrIrAccrualData $accrual,
        string $reversalReason,
        string $reversedBy,
    ): GrIrAccrualData {
        if ($accrual->status !== 'OPEN') {
            throw new \InvalidArgumentException(
                "Cannot reverse accrual {$accrual->accrualId}: status is {$accrual->status}"
            );
        }

        // Create reversal entry
        $reversedAccrual = GrIrAccrualData::fromGoodsReceipt(
            accrualId: $this->generateAccrualId(),
            tenantId: $accrual->tenantId,
            goodsReceiptId: $accrual->goodsReceiptId,
            goodsReceiptNumber: $accrual->goodsReceiptNumber . '-REV',
            purchaseOrderId: $accrual->purchaseOrderId,
            purchaseOrderNumber: $accrual->purchaseOrderNumber,
            vendorId: $accrual->vendorId,
            vendorName: $accrual->vendorName,
            accrualAmount: Money::of(
                -$accrual->accrualAmount->getAmount(),
                $accrual->accrualAmount->getCurrency()
            ),
            productId: $accrual->productId,
            productName: $accrual->productName,
            quantity: -$accrual->quantity,
            uom: $accrual->uom,
            goodsReceiptDate: new \DateTimeImmutable(),
            costCenterId: $accrual->costCenterId,
            projectId: $accrual->projectId,
            metadata: [
                'reversal_of' => $accrual->accrualId,
                'reversal_reason' => $reversalReason,
                'reversed_by' => $reversedBy,
            ],
        );

        $this->logger->info('GR/IR accrual reversed', [
            'original_accrual_id' => $accrual->accrualId,
            'reversal_accrual_id' => $reversedAccrual->accrualId,
            'amount' => $accrual->accrualAmount->getAmount(),
            'reason' => $reversalReason,
            'reversed_by' => $reversedBy,
        ]);

        return $reversedAccrual;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePeriodAccrualEntries(
        string $tenantId,
        \DateTimeImmutable $periodEndDate,
        ?string $costCenterId = null,
    ): array {
        // This would calculate journal entries needed for period-end accruals
        $this->logger->info('Calculating period accrual entries', [
            'tenant_id' => $tenantId,
            'period_end' => $periodEndDate->format('Y-m-d'),
            'cost_center_id' => $costCenterId,
        ]);

        return [
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'journal_entries' => [],
            'total_accrual_amount' => Money::of(0, 'USD'),
            'total_reversal_amount' => Money::of(0, 'USD'),
            'net_impact' => Money::of(0, 'USD'),
        ];
    }

    /**
     * Generate unique accrual ID.
     */
    private function generateAccrualId(): string
    {
        if ($this->idGenerator !== null) {
            return $this->idGenerator->generateId('accr-', 12);
        }

        return 'accr-' . bin2hex(random_bytes(12));
    }
}
