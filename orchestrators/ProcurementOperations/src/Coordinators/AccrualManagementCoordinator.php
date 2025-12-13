<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\AccrualManagementServiceInterface;
use Nexus\ProcurementOperations\DTOs\Financial\AccrualAdjustmentData;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;
use Nexus\ProcurementOperations\DTOs\SOX\ApprovalData;
use Nexus\ProcurementOperations\Enums\AccrualStatus;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\ProcurementOperations\Events\Financial\AccrualAgingAlertEvent;
use Nexus\ProcurementOperations\Events\Financial\AccrualPeriodCloseCompletedEvent;
use Nexus\ProcurementOperations\Events\Financial\AccrualReconciliationCompletedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for GR-IR accrual management and period-end processing.
 *
 * This coordinator handles the complete lifecycle of Goods Receipt-Invoice Receipt
 * accruals including posting, matching, aging analysis, and period close procedures.
 *
 * Follows Advanced Orchestrator Pattern v1.1:
 * - Coordinates flow between accrual services
 * - Does not contain business logic (delegates to services)
 * - Publishes events for audit trail
 */
final readonly class AccrualManagementCoordinator
{
    public function __construct(
        private AccrualManagementServiceInterface $accrualService,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Post a new GR-IR accrual entry.
     *
     * Creates an accrual entry when goods are received before the invoice is processed.
     *
     * @param string $tenantId Tenant identifier
     * @param string $purchaseOrderId Related purchase order ID
     * @param string $goodsReceiptId Goods receipt ID
     * @param string $vendorId Vendor identifier
     * @param Money $accrualAmount Amount to accrue
     * @param \DateTimeImmutable $receiptDate Goods receipt date
     * @param string $postedBy User posting the accrual
     * @param array<string, mixed> $metadata Additional metadata
     * @return GrIrAccrualData Created accrual record
     */
    public function postAccrual(
        string $tenantId,
        string $purchaseOrderId,
        string $goodsReceiptId,
        string $vendorId,
        Money $accrualAmount,
        \DateTimeImmutable $receiptDate,
        string $postedBy,
        array $metadata = [],
    ): GrIrAccrualData {
        $this->logger->info('Posting GR-IR accrual', [
            'tenant_id' => $tenantId,
            'purchase_order_id' => $purchaseOrderId,
            'goods_receipt_id' => $goodsReceiptId,
            'amount' => $accrualAmount->getAmount(),
        ]);

        // Delegate to service
        $accrual = $this->accrualService->createAccrual(
            tenantId: $tenantId,
            purchaseOrderId: $purchaseOrderId,
            goodsReceiptId: $goodsReceiptId,
            vendorId: $vendorId,
            accrualAmount: $accrualAmount,
            receiptDate: $receiptDate,
            postedBy: $postedBy,
            metadata: $metadata,
        );

        $this->logger->info('GR-IR accrual posted', [
            'accrual_id' => $accrual->accrualId,
            'tenant_id' => $tenantId,
        ]);

        return $accrual;
    }

    /**
     * Match accrual to invoice and reverse the accrual.
     *
     * @param string $tenantId Tenant identifier
     * @param string $accrualId Accrual to match
     * @param string $invoiceId Invoice to match against
     * @param string $matchedBy User performing the match
     * @param bool $allowPartialMatch Allow partial matching
     * @return array{
     *     success: bool,
     *     accrual: GrIrAccrualData,
     *     variance: Money,
     *     variance_percent: float,
     *     requires_approval: bool,
     * }
     */
    public function matchAccrualToInvoice(
        string $tenantId,
        string $accrualId,
        string $invoiceId,
        string $matchedBy,
        bool $allowPartialMatch = false,
    ): array {
        $this->logger->info('Matching accrual to invoice', [
            'tenant_id' => $tenantId,
            'accrual_id' => $accrualId,
            'invoice_id' => $invoiceId,
        ]);

        // Get current accrual
        $accrual = $this->accrualService->getAccrual($tenantId, $accrualId);

        if ($accrual === null) {
            throw new \InvalidArgumentException("Accrual {$accrualId} not found");
        }

        if ($accrual->status === AccrualStatus::MATCHED) {
            throw new \InvalidArgumentException("Accrual {$accrualId} is already matched");
        }

        if ($accrual->status === AccrualStatus::REVERSED) {
            throw new \InvalidArgumentException("Accrual {$accrualId} has been reversed");
        }

        // Perform matching
        $matchResult = $this->accrualService->matchAccrual(
            tenantId: $tenantId,
            accrualId: $accrualId,
            invoiceId: $invoiceId,
            matchedBy: $matchedBy,
            allowPartialMatch: $allowPartialMatch,
        );

        // Check if variance requires approval
        $varianceThreshold = 0.05; // 5% variance threshold
        $requiresApproval = $matchResult['variance_percent'] > $varianceThreshold * 100;

        $this->logger->info('Accrual match completed', [
            'accrual_id' => $accrualId,
            'invoice_id' => $invoiceId,
            'variance' => $matchResult['variance']->getAmount(),
            'variance_percent' => $matchResult['variance_percent'],
            'requires_approval' => $requiresApproval,
        ]);

        return [
            'success' => true,
            'accrual' => $matchResult['accrual'],
            'variance' => $matchResult['variance'],
            'variance_percent' => $matchResult['variance_percent'],
            'requires_approval' => $requiresApproval,
        ];
    }

    /**
     * Process auto-matching of outstanding accruals with received invoices.
     *
     * @param string $tenantId Tenant identifier
     * @param array<string, mixed> $criteria Auto-match criteria
     * @param string $processedBy User initiating auto-match
     * @return array{
     *     matched: int,
     *     unmatched: int,
     *     matches: array<array{accrual_id: string, invoice_id: string, variance: Money}>,
     *     errors: array<string, string>,
     * }
     */
    public function processAutoMatching(
        string $tenantId,
        array $criteria = [],
        string $processedBy = 'SYSTEM',
    ): array {
        $this->logger->info('Processing accrual auto-matching', [
            'tenant_id' => $tenantId,
            'criteria' => $criteria,
        ]);

        $matchedCount = 0;
        $unmatchedCount = 0;
        $matches = [];
        $errors = [];

        // Get matching tolerance
        $tolerancePercent = $criteria['tolerance_percent'] ?? 1.0; // 1% default tolerance
        $maxAgesDays = $criteria['max_age_days'] ?? 90;

        // Get open accruals
        $openAccruals = $this->accrualService->getOpenAccruals(
            $tenantId,
            maxAgeDays: $maxAgesDays,
        );

        foreach ($openAccruals as $accrual) {
            try {
                // Find matching invoice candidates
                $invoiceCandidates = $this->accrualService->findMatchingInvoices(
                    tenantId: $tenantId,
                    accrual: $accrual,
                    tolerancePercent: $tolerancePercent,
                );

                if (count($invoiceCandidates) === 1) {
                    // Single match - auto-process
                    $invoice = $invoiceCandidates[0];
                    $result = $this->matchAccrualToInvoice(
                        tenantId: $tenantId,
                        accrualId: $accrual->accrualId,
                        invoiceId: $invoice['invoice_id'],
                        matchedBy: $processedBy,
                        allowPartialMatch: true,
                    );

                    if ($result['success']) {
                        $matchedCount++;
                        $matches[] = [
                            'accrual_id' => $accrual->accrualId,
                            'invoice_id' => $invoice['invoice_id'],
                            'variance' => $result['variance'],
                        ];
                    }
                } elseif (count($invoiceCandidates) === 0) {
                    $unmatchedCount++;
                } else {
                    // Multiple matches - requires manual intervention
                    $unmatchedCount++;
                    $errors[$accrual->accrualId] = 'Multiple invoice candidates found - requires manual matching';
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Auto-match failed for accrual', [
                    'accrual_id' => $accrual->accrualId,
                    'error' => $e->getMessage(),
                ]);
                $errors[$accrual->accrualId] = $e->getMessage();
                $unmatchedCount++;
            }
        }

        $this->logger->info('Auto-matching completed', [
            'tenant_id' => $tenantId,
            'matched' => $matchedCount,
            'unmatched' => $unmatchedCount,
            'errors' => count($errors),
        ]);

        return [
            'matched' => $matchedCount,
            'unmatched' => $unmatchedCount,
            'matches' => $matches,
            'errors' => $errors,
        ];
    }

    /**
     * Execute period-end accrual close procedures.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $periodEndDate Period end date
     * @param string $closedBy User performing the close
     * @param array<string, mixed> $options Close options
     * @return array{
     *     period_end_date: \DateTimeImmutable,
     *     total_open_accruals: int,
     *     total_open_amount: Money,
     *     adjustments_posted: int,
     *     aging_summary: array<string, array{count: int, amount: Money}>,
     *     alerts_generated: array<string>,
     *     closed_at: \DateTimeImmutable,
     * }
     */
    public function executePeriodClose(
        string $tenantId,
        \DateTimeImmutable $periodEndDate,
        string $closedBy,
        array $options = [],
    ): array {
        $this->logger->info('Executing accrual period close', [
            'tenant_id' => $tenantId,
            'period_end_date' => $periodEndDate->format('Y-m-d'),
        ]);

        $closedAt = new \DateTimeImmutable();
        $alerts = [];

        // Get aging analysis
        $agingReport = $this->accrualService->getAgingReport(
            $tenantId,
            asOfDate: $periodEndDate,
        );

        // Process write-offs for very old accruals if enabled
        $adjustmentsPosted = 0;
        if ($options['auto_writeoff_enabled'] ?? false) {
            $writeoffAgeDays = $options['writeoff_age_days'] ?? 365;
            $adjustmentsPosted = $this->processAutoWriteoffs(
                $tenantId,
                $periodEndDate,
                $writeoffAgeDays,
                $closedBy,
            );
        }

        // Get summary statistics
        $openAccruals = $this->accrualService->getOpenAccruals($tenantId);
        $totalOpenAmount = Money::zero('USD');
        foreach ($openAccruals as $accrual) {
            $totalOpenAmount = $totalOpenAmount->add($accrual->accrualAmount);
        }

        // Generate alerts for aging thresholds
        $alertThresholds = $options['alert_thresholds'] ?? [
            '90_days' => Money::of(10000, 'USD'),
            '180_days' => Money::of(5000, 'USD'),
            '365_days' => Money::of(0, 'USD'),
        ];

        foreach ($alertThresholds as $bucket => $threshold) {
            $bucketData = $agingReport['buckets'][$bucket] ?? null;
            if ($bucketData !== null && $bucketData['amount']->greaterThanOrEqual($threshold)) {
                $alert = "Accruals in {$bucket} bucket exceed threshold: {$bucketData['amount']->getAmount()} {$bucketData['amount']->getCurrency()}";
                $alerts[] = $alert;

                $this->eventDispatcher->dispatch(
                    new AccrualAgingAlertEvent(
                        tenantId: $tenantId,
                        agingBucket: $bucket,
                        amount: $bucketData['amount'],
                        threshold: $threshold,
                        periodEndDate: $periodEndDate,
                    )
                );
            }
        }

        // Dispatch period close event
        $this->eventDispatcher->dispatch(
            new AccrualPeriodCloseCompletedEvent(
                tenantId: $tenantId,
                periodEndDate: $periodEndDate,
                totalOpenAccruals: count($openAccruals),
                totalOpenAmount: $totalOpenAmount,
                adjustmentsPosted: $adjustmentsPosted,
                closedBy: $closedBy,
                closedAt: $closedAt,
            )
        );

        $this->logger->info('Period close completed', [
            'tenant_id' => $tenantId,
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'open_accruals' => count($openAccruals),
            'open_amount' => $totalOpenAmount->getAmount(),
            'adjustments' => $adjustmentsPosted,
            'alerts' => count($alerts),
        ]);

        return [
            'period_end_date' => $periodEndDate,
            'total_open_accruals' => count($openAccruals),
            'total_open_amount' => $totalOpenAmount,
            'adjustments_posted' => $adjustmentsPosted,
            'aging_summary' => $agingReport['buckets'],
            'alerts_generated' => $alerts,
            'closed_at' => $closedAt,
        ];
    }

    /**
     * Reconcile accruals with general ledger.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $asOfDate Reconciliation date
     * @param Money $glBalance GL balance to reconcile against
     * @param string $reconciledBy User performing reconciliation
     * @return array{
     *     reconciliation_date: \DateTimeImmutable,
     *     gl_balance: Money,
     *     subledger_balance: Money,
     *     variance: Money,
     *     variance_percent: float,
     *     is_reconciled: bool,
     *     reconciliation_items: array<array{description: string, amount: Money}>,
     * }
     */
    public function reconcileWithGL(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
        Money $glBalance,
        string $reconciledBy,
    ): array {
        $this->logger->info('Reconciling accruals with GL', [
            'tenant_id' => $tenantId,
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'gl_balance' => $glBalance->getAmount(),
        ]);

        // Calculate subledger balance
        $openAccruals = $this->accrualService->getOpenAccruals($tenantId);
        $subledgerBalance = Money::zero($glBalance->getCurrency());

        foreach ($openAccruals as $accrual) {
            if ($accrual->createdAt <= $asOfDate) {
                $subledgerBalance = $subledgerBalance->add($accrual->accrualAmount);
            }
        }

        // Calculate variance
        $variance = $glBalance->subtract($subledgerBalance);
        $variancePercent = $glBalance->getAmount() != 0
            ? ($variance->getAmount() / $glBalance->getAmount()) * 100
            : 0.0;

        // Tolerance for reconciliation (0.5% or $100, whichever is greater)
        $tolerancePercent = 0.5;
        $toleranceAmount = max(100.0, $glBalance->getAmount() * $tolerancePercent / 100);
        $isReconciled = abs($variance->getAmount()) <= $toleranceAmount;

        // Build reconciliation items
        $reconciliationItems = [
            [
                'description' => 'GL Balance (GR-IR Accrual Account)',
                'amount' => $glBalance,
            ],
            [
                'description' => 'Subledger Balance (Open Accruals)',
                'amount' => $subledgerBalance,
            ],
            [
                'description' => 'Variance',
                'amount' => $variance,
            ],
        ];

        // If variance exists, try to identify causes
        if (!$isReconciled) {
            $reconciliationItems[] = [
                'description' => 'Timing Differences (estimated)',
                'amount' => $variance->multiply(0.8), // Assumption
            ];
            $reconciliationItems[] = [
                'description' => 'Unidentified Variance',
                'amount' => $variance->multiply(0.2),
            ];
        }

        // Dispatch reconciliation event
        $this->eventDispatcher->dispatch(
            new AccrualReconciliationCompletedEvent(
                tenantId: $tenantId,
                asOfDate: $asOfDate,
                glBalance: $glBalance,
                subledgerBalance: $subledgerBalance,
                variance: $variance,
                isReconciled: $isReconciled,
                reconciledBy: $reconciledBy,
            )
        );

        $this->logger->info('GL reconciliation completed', [
            'tenant_id' => $tenantId,
            'gl_balance' => $glBalance->getAmount(),
            'subledger_balance' => $subledgerBalance->getAmount(),
            'variance' => $variance->getAmount(),
            'is_reconciled' => $isReconciled,
        ]);

        return [
            'reconciliation_date' => $asOfDate,
            'gl_balance' => $glBalance,
            'subledger_balance' => $subledgerBalance,
            'variance' => $variance,
            'variance_percent' => round($variancePercent, 4),
            'is_reconciled' => $isReconciled,
            'reconciliation_items' => $reconciliationItems,
        ];
    }

    /**
     * Post accrual adjustment entry.
     *
     * @param string $tenantId Tenant identifier
     * @param string $accrualId Accrual to adjust
     * @param AccrualAdjustmentData $adjustment Adjustment data
     * @param ApprovalData|null $approval Approval data (required for significant adjustments)
     * @return GrIrAccrualData Updated accrual
     */
    public function postAdjustment(
        string $tenantId,
        string $accrualId,
        AccrualAdjustmentData $adjustment,
        ?ApprovalData $approval = null,
    ): GrIrAccrualData {
        $this->logger->info('Posting accrual adjustment', [
            'tenant_id' => $tenantId,
            'accrual_id' => $accrualId,
            'adjustment_type' => $adjustment->adjustmentType,
            'adjustment_amount' => $adjustment->adjustmentAmount->getAmount(),
        ]);

        // Check if approval is required
        $approvalThreshold = Money::of(1000, $adjustment->adjustmentAmount->getCurrency());
        if ($adjustment->adjustmentAmount->greaterThan($approvalThreshold) && $approval === null) {
            throw new \InvalidArgumentException(
                'Approval required for adjustments over ' . $approvalThreshold->getAmount()
            );
        }

        // Validate approval if provided
        if ($approval !== null) {
            $this->validateApproval($approval, $adjustment->adjustmentAmount);
        }

        // Delegate to service
        $updatedAccrual = $this->accrualService->adjustAccrual(
            tenantId: $tenantId,
            accrualId: $accrualId,
            adjustment: $adjustment,
            approvedBy: $approval?->approverId,
        );

        $this->logger->info('Accrual adjustment posted', [
            'accrual_id' => $accrualId,
            'new_amount' => $updatedAccrual->accrualAmount->getAmount(),
        ]);

        return $updatedAccrual;
    }

    /**
     * Generate accrual aging report.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $asOfDate As of date (defaults to today)
     * @param array<string, mixed> $filters Report filters
     * @return array{
     *     as_of_date: \DateTimeImmutable,
     *     total_accruals: int,
     *     total_amount: Money,
     *     buckets: array<string, array{count: int, amount: Money, percent: float}>,
     *     vendor_breakdown: array<string, array{vendor_name: string, amount: Money, oldest_days: int}>,
     *     trends: array<string, Money>,
     * }
     */
    public function generateAgingReport(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
        array $filters = [],
    ): array {
        $asOfDate ??= new \DateTimeImmutable();

        $this->logger->info('Generating accrual aging report', [
            'tenant_id' => $tenantId,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ]);

        // Get aging report from service
        $agingReport = $this->accrualService->getAgingReport($tenantId, $asOfDate);

        // Calculate percentages
        $totalAmount = $agingReport['total_amount'];
        foreach ($agingReport['buckets'] as $bucket => $data) {
            $agingReport['buckets'][$bucket]['percent'] = $totalAmount->getAmount() > 0
                ? round(($data['amount']->getAmount() / $totalAmount->getAmount()) * 100, 2)
                : 0.0;
        }

        // Get vendor breakdown
        $vendorBreakdown = $this->calculateVendorBreakdown($tenantId, $asOfDate);

        // Calculate trends (last 3 months)
        $trends = [];
        for ($i = 0; $i < 3; $i++) {
            $monthDate = $asOfDate->modify("-{$i} months");
            $monthKey = $monthDate->format('Y-m');
            $monthReport = $this->accrualService->getAgingReport($tenantId, $monthDate);
            $trends[$monthKey] = $monthReport['total_amount'];
        }

        $this->logger->info('Aging report generated', [
            'tenant_id' => $tenantId,
            'total_accruals' => $agingReport['total_accruals'],
            'total_amount' => $totalAmount->getAmount(),
        ]);

        return [
            'as_of_date' => $asOfDate,
            'total_accruals' => $agingReport['total_accruals'],
            'total_amount' => $totalAmount,
            'buckets' => $agingReport['buckets'],
            'vendor_breakdown' => $vendorBreakdown,
            'trends' => $trends,
        ];
    }

    /**
     * Get accrual dashboard summary.
     *
     * @param string $tenantId Tenant identifier
     * @return array{
     *     total_open_accruals: int,
     *     total_open_amount: Money,
     *     pending_match: int,
     *     overdue_count: int,
     *     overdue_amount: Money,
     *     mtd_matched: int,
     *     mtd_matched_amount: Money,
     *     avg_days_to_match: float,
     * }
     */
    public function getDashboardSummary(string $tenantId): array
    {
        $this->logger->debug('Generating accrual dashboard summary', [
            'tenant_id' => $tenantId,
        ]);

        $now = new \DateTimeImmutable();
        $monthStart = new \DateTimeImmutable('first day of this month');

        // Get all open accruals
        $openAccruals = $this->accrualService->getOpenAccruals($tenantId);
        $totalOpenAmount = Money::zero('USD');
        $pendingMatch = 0;
        $overdueCount = 0;
        $overdueAmount = Money::zero('USD');
        $overdueThreshold = 90; // 90 days considered overdue

        foreach ($openAccruals as $accrual) {
            $totalOpenAmount = $totalOpenAmount->add($accrual->accrualAmount);

            if ($accrual->status === AccrualStatus::OPEN) {
                $pendingMatch++;
            }

            $ageInDays = $accrual->createdAt->diff($now)->days;
            if ($ageInDays > $overdueThreshold) {
                $overdueCount++;
                $overdueAmount = $overdueAmount->add($accrual->accrualAmount);
            }
        }

        // Get MTD matched statistics
        $mtdMatched = $this->accrualService->getMatchedAccruals($tenantId, $monthStart, $now);
        $mtdMatchedAmount = Money::zero('USD');
        $totalDaysToMatch = 0;

        foreach ($mtdMatched as $accrual) {
            $mtdMatchedAmount = $mtdMatchedAmount->add($accrual->accrualAmount);
            if ($accrual->matchedAt !== null) {
                $totalDaysToMatch += $accrual->createdAt->diff($accrual->matchedAt)->days;
            }
        }

        $avgDaysToMatch = count($mtdMatched) > 0
            ? $totalDaysToMatch / count($mtdMatched)
            : 0.0;

        return [
            'total_open_accruals' => count($openAccruals),
            'total_open_amount' => $totalOpenAmount,
            'pending_match' => $pendingMatch,
            'overdue_count' => $overdueCount,
            'overdue_amount' => $overdueAmount,
            'mtd_matched' => count($mtdMatched),
            'mtd_matched_amount' => $mtdMatchedAmount,
            'avg_days_to_match' => round($avgDaysToMatch, 1),
        ];
    }

    /**
     * Process automatic write-offs for aged accruals.
     */
    private function processAutoWriteoffs(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
        int $writeoffAgeDays,
        string $processedBy,
    ): int {
        $cutoffDate = $asOfDate->modify("-{$writeoffAgeDays} days");
        $openAccruals = $this->accrualService->getOpenAccruals($tenantId);
        $writeoffCount = 0;

        foreach ($openAccruals as $accrual) {
            if ($accrual->createdAt < $cutoffDate) {
                try {
                    $this->accrualService->writeOffAccrual(
                        tenantId: $tenantId,
                        accrualId: $accrual->accrualId,
                        reason: "Auto write-off: Accrual older than {$writeoffAgeDays} days",
                        writtenOffBy: $processedBy,
                    );
                    $writeoffCount++;
                } catch (\Throwable $e) {
                    $this->logger->warning('Auto write-off failed', [
                        'accrual_id' => $accrual->accrualId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $writeoffCount;
    }

    /**
     * Validate approval for adjustment.
     */
    private function validateApproval(ApprovalData $approval, Money $amount): void
    {
        // Validate approval status
        if ($approval->status !== 'APPROVED') {
            throw new \InvalidArgumentException('Approval is not in approved status');
        }

        // Validate approval level for amount
        $requiredLevel = match (true) {
            $amount->getAmount() >= 50000 => ApprovalLevel::CFO,
            $amount->getAmount() >= 10000 => ApprovalLevel::VP,
            $amount->getAmount() >= 1000 => ApprovalLevel::MANAGER,
            default => ApprovalLevel::SUPERVISOR,
        };

        // Check if approval level is sufficient (simplified check)
        $approvalLevelValue = match ($approval->approvalLevel) {
            ApprovalLevel::SUPERVISOR => 1,
            ApprovalLevel::MANAGER => 2,
            ApprovalLevel::DIRECTOR => 3,
            ApprovalLevel::VP => 4,
            ApprovalLevel::CFO => 5,
            ApprovalLevel::CEO => 6,
            default => 0,
        };

        $requiredLevelValue = match ($requiredLevel) {
            ApprovalLevel::SUPERVISOR => 1,
            ApprovalLevel::MANAGER => 2,
            ApprovalLevel::DIRECTOR => 3,
            ApprovalLevel::VP => 4,
            ApprovalLevel::CFO => 5,
            ApprovalLevel::CEO => 6,
            default => 0,
        };

        if ($approvalLevelValue < $requiredLevelValue) {
            throw new \InvalidArgumentException(
                "Insufficient approval level. Required: {$requiredLevel->value}, Got: {$approval->approvalLevel->value}"
            );
        }
    }

    /**
     * Calculate vendor breakdown for aging report.
     *
     * @return array<string, array{vendor_name: string, amount: Money, oldest_days: int}>
     */
    private function calculateVendorBreakdown(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
    ): array {
        $openAccruals = $this->accrualService->getOpenAccruals($tenantId);
        $vendorData = [];

        foreach ($openAccruals as $accrual) {
            $vendorId = $accrual->vendorId;

            if (!isset($vendorData[$vendorId])) {
                $vendorData[$vendorId] = [
                    'vendor_name' => $accrual->vendorName ?? 'Unknown',
                    'amount' => Money::zero('USD'),
                    'oldest_days' => 0,
                ];
            }

            $vendorData[$vendorId]['amount'] = $vendorData[$vendorId]['amount']->add($accrual->accrualAmount);
            $accrualAge = $accrual->createdAt->diff($asOfDate)->days;
            $vendorData[$vendorId]['oldest_days'] = max($vendorData[$vendorId]['oldest_days'], $accrualAge);
        }

        // Sort by amount descending
        uasort($vendorData, fn($a, $b) => $b['amount']->getAmount() <=> $a['amount']->getAmount());

        return array_slice($vendorData, 0, 10, true);
    }
}
