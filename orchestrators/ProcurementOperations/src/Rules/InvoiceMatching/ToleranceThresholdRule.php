<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\InvoiceMatching;

use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that all variances fall within configured tolerance thresholds.
 *
 * This rule performs a comprehensive check of all variance types:
 * - Price variance (Invoice vs PO)
 * - Quantity variance (Invoice vs GR or PO)
 * - Amount variance (Total Invoice vs Total PO)
 *
 * Used to determine if automatic approval is possible or manual review required.
 */
final readonly class ToleranceThresholdRule implements RuleInterface
{
    private const DEFAULT_PRICE_TOLERANCE = 0.0;
    private const DEFAULT_QUANTITY_TOLERANCE = 0.0;
    private const DEFAULT_AMOUNT_TOLERANCE = 0.0;

    public function __construct(
        private float $priceTolerancePercent = self::DEFAULT_PRICE_TOLERANCE,
        private float $quantityTolerancePercent = self::DEFAULT_QUANTITY_TOLERANCE,
        private float $amountTolerancePercent = self::DEFAULT_AMOUNT_TOLERANCE,
    ) {}

    public function getName(): string
    {
        return 'tolerance_threshold';
    }

    /**
     * Check if all variances are within configured thresholds.
     *
     * @param ThreeWayMatchContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof ThreeWayMatchContext) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type for tolerance threshold rule',
            );
        }

        $violations = [];

        // Check price variance
        $priceVariance = $context->calculatePriceVariancePercent();
        if ($priceVariance > $this->priceTolerancePercent) {
            $violations['price'] = [
                'variance' => $priceVariance,
                'tolerance' => $this->priceTolerancePercent,
                'exceeded_by' => $priceVariance - $this->priceTolerancePercent,
            ];
        }

        // Check quantity variance
        $quantityVariance = $context->calculateQuantityVariancePercent();
        if ($quantityVariance > $this->quantityTolerancePercent) {
            $violations['quantity'] = [
                'variance' => $quantityVariance,
                'tolerance' => $this->quantityTolerancePercent,
                'exceeded_by' => $quantityVariance - $this->quantityTolerancePercent,
            ];
        }

        // Check total amount variance
        $amountVariance = $this->calculateAmountVariance($context);
        if ($amountVariance > $this->amountTolerancePercent) {
            $violations['amount'] = [
                'variance' => $amountVariance,
                'tolerance' => $this->amountTolerancePercent,
                'exceeded_by' => $amountVariance - $this->amountTolerancePercent,
            ];
        }

        if (empty($violations)) {
            return RuleResult::pass(
                $this->getName(),
                sprintf(
                    'All variances within tolerance. Price: %.2f%% (max %.2f%%), Qty: %.2f%% (max %.2f%%), Amt: %.2f%% (max %.2f%%)',
                    $priceVariance,
                    $this->priceTolerancePercent,
                    $quantityVariance,
                    $this->quantityTolerancePercent,
                    $amountVariance,
                    $this->amountTolerancePercent,
                ),
                [
                    'priceVariance' => $priceVariance,
                    'quantityVariance' => $quantityVariance,
                    'amountVariance' => $amountVariance,
                    'thresholds' => [
                        'price' => $this->priceTolerancePercent,
                        'quantity' => $this->quantityTolerancePercent,
                        'amount' => $this->amountTolerancePercent,
                    ],
                ],
            );
        }

        $failedChecks = array_keys($violations);
        return RuleResult::fail(
            $this->getName(),
            sprintf(
                'Tolerance exceeded for: %s. Manual approval required.',
                implode(', ', $failedChecks),
            ),
            [
                'priceVariance' => $priceVariance,
                'quantityVariance' => $quantityVariance,
                'amountVariance' => $amountVariance,
                'violations' => $violations,
                'thresholds' => [
                    'price' => $this->priceTolerancePercent,
                    'quantity' => $this->quantityTolerancePercent,
                    'amount' => $this->amountTolerancePercent,
                ],
            ],
        );
    }

    /**
     * Calculate total amount variance percentage.
     */
    private function calculateAmountVariance(ThreeWayMatchContext $context): float
    {
        $poAmount = $context->totals['totalPoAmountCents'];
        $invoiceAmount = $context->totals['totalInvoiceAmountCents'];

        if ($poAmount === 0) {
            return $invoiceAmount === 0 ? 0.0 : 100.0;
        }

        return abs($invoiceAmount - $poAmount) / $poAmount * 100;
    }

    /**
     * Determine if automatic approval is possible.
     */
    public function canAutoApprove(ThreeWayMatchContext $context): bool
    {
        return $this->check($context)->passed;
    }

    /**
     * Get variance classification for reporting.
     *
     * @return array{
     *     classification: string,
     *     requiresApproval: bool,
     *     approvalLevel: string|null
     * }
     */
    public function classifyVariances(ThreeWayMatchContext $context): array
    {
        $result = $this->check($context);

        if ($result->passed) {
            return [
                'classification' => 'within_tolerance',
                'requiresApproval' => false,
                'approvalLevel' => null,
            ];
        }

        // Determine approval level based on variance severity
        $contextData = $result->context;
        $violations = $contextData['violations'] ?? [];
        $maxExceeded = 0.0;

        foreach ($violations as $violation) {
            $maxExceeded = max($maxExceeded, $violation['exceeded_by'] ?? 0.0);
        }

        // Classify by severity
        if ($maxExceeded > 10.0) {
            return [
                'classification' => 'critical_variance',
                'requiresApproval' => true,
                'approvalLevel' => 'finance_manager',
            ];
        }

        if ($maxExceeded > 5.0) {
            return [
                'classification' => 'significant_variance',
                'requiresApproval' => true,
                'approvalLevel' => 'supervisor',
            ];
        }

        return [
            'classification' => 'minor_variance',
            'requiresApproval' => true,
            'approvalLevel' => 'buyer',
        ];
    }
}
