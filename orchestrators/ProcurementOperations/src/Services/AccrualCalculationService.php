<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\AccrualServiceInterface;
use Nexus\ProcurementOperations\DTOs\GoodsReceiptContext;
use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Exceptions\AccrualException;
use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles GR-IR accrual calculations and GL posting.
 *
 * This service manages the accounting entries for:
 * - Goods Receipt accrual (GR-IR clearing)
 * - Invoice matching and accrual reversal
 * - AP liability posting
 * - Payment journal entries
 *
 * GL Flow:
 * 1. On Goods Receipt:
 *    DR Inventory Asset (at PO price)
 *    CR GR-IR Clearing Account
 *
 * 2. On Invoice Match:
 *    DR GR-IR Clearing Account
 *    CR Accounts Payable
 *    (+ variance entries if price differs)
 *
 * 3. On Payment:
 *    DR Accounts Payable
 *    CR Bank Account
 */
final readonly class AccrualCalculationService implements AccrualServiceInterface
{
    public function __construct(
        private ?JournalEntryManagerInterface $journalEntryManager = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Calculate and post GR-IR accrual for goods receipt.
     *
     * @inheritDoc
     */
    public function postGoodsReceiptAccrual(
        string $tenantId,
        string $goodsReceiptId,
        string $purchaseOrderId,
        array $lineItems,
        string $postedBy
    ): string {
        $this->logger->info('Posting GR-IR accrual', [
            'tenant_id' => $tenantId,
            'goods_receipt_id' => $goodsReceiptId,
            'purchase_order_id' => $purchaseOrderId,
            'line_count' => count($lineItems),
        ]);

        // Calculate total accrual amount
        $totalCents = $this->calculateTotalFromLines($lineItems);

        if ($totalCents === 0) {
            throw AccrualException::zeroAmount($goodsReceiptId);
        }

        // If no journal entry manager, return a placeholder
        if ($this->journalEntryManager === null) {
            $this->logger->warning('JournalEntryManager not available, skipping GL posting', [
                'goods_receipt_id' => $goodsReceiptId,
            ]);
            return 'PENDING-' . $goodsReceiptId;
        }

        // Build journal entry lines
        $journalLines = $this->buildGoodsReceiptJournalLines($lineItems, $tenantId);

        // Post to GL
        try {
            $journalEntryId = $this->journalEntryManager->post(
                tenantId: $tenantId,
                description: sprintf('GR-IR Accrual for GR %s (PO %s)', $goodsReceiptId, $purchaseOrderId),
                lines: $journalLines,
                reference: $goodsReceiptId,
                referenceType: 'goods_receipt',
                postedBy: $postedBy,
            );

            $this->logger->info('GR-IR accrual posted successfully', [
                'journal_entry_id' => $journalEntryId,
                'goods_receipt_id' => $goodsReceiptId,
                'total_cents' => $totalCents,
            ]);

            return $journalEntryId;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to post GR-IR accrual', [
                'goods_receipt_id' => $goodsReceiptId,
                'error' => $e->getMessage(),
            ]);
            throw AccrualException::postingFailed($goodsReceiptId, $e->getMessage());
        }
    }

    /**
     * Reverse GR-IR accrual when invoice is matched.
     *
     * @inheritDoc
     */
    public function reverseAccrualOnMatch(
        string $tenantId,
        string $vendorBillId,
        array $goodsReceiptIds,
        string $postedBy
    ): string {
        $this->logger->info('Reversing GR-IR accrual on invoice match', [
            'tenant_id' => $tenantId,
            'vendor_bill_id' => $vendorBillId,
            'goods_receipt_ids' => $goodsReceiptIds,
        ]);

        if ($this->journalEntryManager === null) {
            $this->logger->warning('JournalEntryManager not available, skipping accrual reversal', [
                'vendor_bill_id' => $vendorBillId,
            ]);
            return 'PENDING-REVERSAL-' . $vendorBillId;
        }

        // Build reversal journal lines
        // DR GR-IR Clearing
        // CR Inventory (or Expense variance)
        $journalLines = [
            [
                'accountCode' => $this->getGrIrClearingAccount($tenantId),
                'debit' => 0, // Will be calculated from matched GRs
                'credit' => 0,
                'description' => 'GR-IR clearing reversal',
            ],
        ];

        try {
            $journalEntryId = $this->journalEntryManager->post(
                tenantId: $tenantId,
                description: sprintf('GR-IR Reversal for Invoice %s', $vendorBillId),
                lines: $journalLines,
                reference: $vendorBillId,
                referenceType: 'vendor_bill',
                postedBy: $postedBy,
            );

            $this->logger->info('GR-IR accrual reversed successfully', [
                'journal_entry_id' => $journalEntryId,
                'vendor_bill_id' => $vendorBillId,
            ]);

            return $journalEntryId;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to reverse GR-IR accrual', [
                'vendor_bill_id' => $vendorBillId,
                'error' => $e->getMessage(),
            ]);
            throw AccrualException::reversalFailed($vendorBillId, $e->getMessage());
        }
    }

    /**
     * Post AP liability journal entry when invoice is matched.
     *
     * @inheritDoc
     */
    public function postPayableLiability(
        string $tenantId,
        string $vendorBillId,
        string $vendorId,
        int $amountCents,
        string $currency,
        string $postedBy
    ): string {
        $this->logger->info('Posting AP liability', [
            'tenant_id' => $tenantId,
            'vendor_bill_id' => $vendorBillId,
            'vendor_id' => $vendorId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
        ]);

        if ($this->journalEntryManager === null) {
            $this->logger->warning('JournalEntryManager not available, skipping AP posting', [
                'vendor_bill_id' => $vendorBillId,
            ]);
            return 'PENDING-AP-' . $vendorBillId;
        }

        $journalLines = [
            [
                'accountCode' => $this->getGrIrClearingAccount($tenantId),
                'debit' => $amountCents,
                'credit' => 0,
                'description' => 'Clear GR-IR accrual',
            ],
            [
                'accountCode' => $this->getAccountsPayableAccount($tenantId, $vendorId),
                'debit' => 0,
                'credit' => $amountCents,
                'description' => sprintf('AP liability for vendor %s', $vendorId),
            ],
        ];

        try {
            $journalEntryId = $this->journalEntryManager->post(
                tenantId: $tenantId,
                description: sprintf('AP Liability for Invoice %s', $vendorBillId),
                lines: $journalLines,
                reference: $vendorBillId,
                referenceType: 'vendor_bill',
                postedBy: $postedBy,
            );

            $this->logger->info('AP liability posted successfully', [
                'journal_entry_id' => $journalEntryId,
                'vendor_bill_id' => $vendorBillId,
                'amount_cents' => $amountCents,
            ]);

            return $journalEntryId;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to post AP liability', [
                'vendor_bill_id' => $vendorBillId,
                'error' => $e->getMessage(),
            ]);
            throw AccrualException::postingFailed($vendorBillId, $e->getMessage());
        }
    }

    /**
     * Post payment journal entry.
     *
     * @inheritDoc
     */
    public function postPaymentEntry(
        string $tenantId,
        string $paymentId,
        string $vendorId,
        string $bankAccountId,
        int $amountCents,
        string $currency,
        string $postedBy
    ): string {
        $this->logger->info('Posting payment entry', [
            'tenant_id' => $tenantId,
            'payment_id' => $paymentId,
            'vendor_id' => $vendorId,
            'bank_account_id' => $bankAccountId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
        ]);

        if ($this->journalEntryManager === null) {
            $this->logger->warning('JournalEntryManager not available, skipping payment posting', [
                'payment_id' => $paymentId,
            ]);
            return 'PENDING-PAYMENT-' . $paymentId;
        }

        $journalLines = [
            [
                'accountCode' => $this->getAccountsPayableAccount($tenantId, $vendorId),
                'debit' => $amountCents,
                'credit' => 0,
                'description' => sprintf('Clear AP for vendor %s', $vendorId),
            ],
            [
                'accountCode' => $bankAccountId,
                'debit' => 0,
                'credit' => $amountCents,
                'description' => sprintf('Payment from bank account %s', $bankAccountId),
            ],
        ];

        try {
            $journalEntryId = $this->journalEntryManager->post(
                tenantId: $tenantId,
                description: sprintf('Payment %s to vendor %s', $paymentId, $vendorId),
                lines: $journalLines,
                reference: $paymentId,
                referenceType: 'payment',
                postedBy: $postedBy,
            );

            $this->logger->info('Payment entry posted successfully', [
                'journal_entry_id' => $journalEntryId,
                'payment_id' => $paymentId,
                'amount_cents' => $amountCents,
            ]);

            return $journalEntryId;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to post payment entry', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            throw AccrualException::postingFailed($paymentId, $e->getMessage());
        }
    }

    /**
     * Calculate accrual amount from goods receipt context.
     */
    public function calculateAccrualAmount(GoodsReceiptContext $context): Money
    {
        return Money::ofCents($context->totalValueCents, $context->currency);
    }

    /**
     * Calculate price variance between GR and Invoice.
     *
     * @param ThreeWayMatchContext $context
     * @return array{
     *     varianceAmountCents: int,
     *     variancePercent: float,
     *     lineVariances: array<string, array{
     *         poPrice: int,
     *         invoicePrice: int,
     *         variance: int,
     *         variancePercent: float
     *     }>
     * }
     */
    public function calculatePriceVariance(ThreeWayMatchContext $context): array
    {
        $totalVariance = 0;
        $lineVariances = [];

        foreach ($context->lineComparison as $line) {
            $poPrice = $line['poUnitPriceCents'];
            $invoicePrice = $line['invoiceUnitPriceCents'];
            $variance = $invoicePrice - $poPrice;
            $variancePercent = $poPrice > 0 ? ($variance / $poPrice) * 100 : 0.0;

            $totalVariance += $variance * (int) $line['invoiceQuantity'];
            $lineVariances[$line['lineId']] = [
                'poPrice' => $poPrice,
                'invoicePrice' => $invoicePrice,
                'variance' => $variance,
                'variancePercent' => round($variancePercent, 2),
            ];
        }

        $totalPoAmount = $context->totals['totalPoAmountCents'];
        $totalVariancePercent = $totalPoAmount > 0 ? ($totalVariance / $totalPoAmount) * 100 : 0.0;

        return [
            'varianceAmountCents' => $totalVariance,
            'variancePercent' => round($totalVariancePercent, 2),
            'lineVariances' => $lineVariances,
        ];
    }

    /**
     * Build journal lines for goods receipt accrual.
     *
     * @param array<int, array{
     *     productId: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     totalCents: int
     * }> $lineItems
     */
    private function buildGoodsReceiptJournalLines(array $lineItems, string $tenantId): array
    {
        $lines = [];
        $totalCents = 0;

        // Aggregate by product (for inventory account determination)
        foreach ($lineItems as $line) {
            $lineTotalCents = $line['totalCents'] ?? (int) ($line['quantity'] * $line['unitPriceCents']);
            $totalCents += $lineTotalCents;

            // DR Inventory Asset
            $lines[] = [
                'accountCode' => $this->getInventoryAccount($tenantId, $line['productId']),
                'debit' => $lineTotalCents,
                'credit' => 0,
                'description' => sprintf('Inventory receipt: %s x %.2f', $line['productId'], $line['quantity']),
            ];
        }

        // CR GR-IR Clearing (single line for total)
        $lines[] = [
            'accountCode' => $this->getGrIrClearingAccount($tenantId),
            'debit' => 0,
            'credit' => $totalCents,
            'description' => 'GR-IR Clearing',
        ];

        return $lines;
    }

    /**
     * Calculate total from line items.
     */
    private function calculateTotalFromLines(array $lineItems): int
    {
        $total = 0;
        foreach ($lineItems as $line) {
            $total += $line['totalCents'] ?? (int) ($line['quantity'] * $line['unitPriceCents']);
        }
        return $total;
    }

    /**
     * Get inventory account for product.
     *
     * In a real implementation, this would look up the GL account
     * from Chart of Accounts based on product category.
     */
    private function getInventoryAccount(string $tenantId, string $productId): string
    {
        // Default inventory asset account
        // TODO: Integrate with ChartOfAccount package for proper account lookup
        return 'INVENTORY-ASSET';
    }

    /**
     * Get GR-IR clearing account.
     */
    private function getGrIrClearingAccount(string $tenantId): string
    {
        // Standard GR-IR clearing account
        return 'GR-IR-CLEARING';
    }

    /**
     * Get accounts payable account for vendor.
     */
    private function getAccountsPayableAccount(string $tenantId, string $vendorId): string
    {
        // Default AP account - could be vendor-specific
        return 'ACCOUNTS-PAYABLE';
    }
}
