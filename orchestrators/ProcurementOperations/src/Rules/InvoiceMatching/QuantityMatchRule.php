<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\InvoiceMatching;

use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that invoice quantities match goods receipt quantities within tolerance.
 *
 * For three-way matching, invoice quantity should match GR quantity (what was received).
 * For two-way matching, invoice quantity should match PO quantity.
 *
 * Quantity variance is calculated as: |Invoice Qty - GR Qty| / GR Qty * 100
 */
final readonly class QuantityMatchRule implements RuleInterface
{
    private const DEFAULT_TOLERANCE_PERCENT = 0.0;

    public function __construct(
        private float $tolerancePercent = self::DEFAULT_TOLERANCE_PERCENT,
        private bool $threeWayMatch = true,
    ) {}

    public function getName(): string
    {
        return 'quantity_match';
    }

    /**
     * Check if invoice quantities match GR/PO quantities within tolerance.
     *
     * @param ThreeWayMatchContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof ThreeWayMatchContext) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type for quantity match rule',
            );
        }

        $variances = [];
        $maxVariance = 0.0;
        $allWithinTolerance = true;

        // Determine reference quantity (GR for 3-way, PO for 2-way)
        $referenceKey = $this->threeWayMatch ? 'totalGrQuantity' : 'totalPoQuantity';
        $referenceLabel = $this->threeWayMatch ? 'GR' : 'PO';

        // Check total quantity variance
        $totalVariance = $this->calculateTotalVariance($context, $referenceKey);
        $maxVariance = max($maxVariance, $totalVariance);

        if ($totalVariance > $this->tolerancePercent) {
            $allWithinTolerance = false;
            $variances['total'] = [
                "{$referenceLabel}Quantity" => $context->totals[$referenceKey],
                'invoiceQuantity' => $context->totals['totalInvoiceQuantity'],
                'variancePercent' => $totalVariance,
                'tolerancePercent' => $this->tolerancePercent,
            ];
        }

        // Check line item quantity variances
        foreach ($context->lineComparison as $index => $line) {
            $lineVariance = $this->calculateLineVariance($line, $referenceLabel);

            if ($lineVariance > $this->tolerancePercent) {
                $allWithinTolerance = false;
                $variances["line_{$index}"] = [
                    'lineId' => $line['lineId'],
                    'productId' => $line['productId'],
                    "{$referenceLabel}Quantity" => $this->threeWayMatch ? $line['grQuantity'] : $line['poQuantity'],
                    'invoiceQuantity' => $line['invoiceQuantity'],
                    'variancePercent' => $lineVariance,
                    'tolerancePercent' => $this->tolerancePercent,
                ];
            }

            $maxVariance = max($maxVariance, $lineVariance);
        }

        if ($allWithinTolerance) {
            return RuleResult::pass(
                $this->getName(),
                sprintf(
                    'Quantity match passed (%s-way). Maximum variance: %.2f%% (tolerance: %.2f%%)',
                    $this->threeWayMatch ? '3' : '2',
                    $maxVariance,
                    $this->tolerancePercent,
                ),
                [
                    'matchType' => $this->threeWayMatch ? 'three_way' : 'two_way',
                    'maxVariancePercent' => $maxVariance,
                    'tolerancePercent' => $this->tolerancePercent,
                ],
            );
        }

        return RuleResult::fail(
            $this->getName(),
            sprintf(
                'Quantity variance %.2f%% exceeds tolerance %.2f%% (vs %s)',
                $maxVariance,
                $this->tolerancePercent,
                $referenceLabel,
            ),
            [
                'matchType' => $this->threeWayMatch ? 'three_way' : 'two_way',
                'maxVariancePercent' => $maxVariance,
                'tolerancePercent' => $this->tolerancePercent,
                'variances' => $variances,
            ],
        );
    }

    /**
     * Calculate total quantity variance percentage.
     */
    private function calculateTotalVariance(ThreeWayMatchContext $context, string $referenceKey): float
    {
        $referenceQty = $context->totals[$referenceKey];
        $invoiceQty = $context->totals['totalInvoiceQuantity'];

        if ($referenceQty === 0.0) {
            return $invoiceQty === 0.0 ? 0.0 : 100.0;
        }

        return abs($invoiceQty - $referenceQty) / $referenceQty * 100;
    }

    /**
     * Calculate line item quantity variance percentage.
     *
     * @param array{poQuantity: float, grQuantity: float, invoiceQuantity: float} $line
     */
    private function calculateLineVariance(array $line, string $referenceLabel): float
    {
        $referenceQty = $referenceLabel === 'GR' ? $line['grQuantity'] : $line['poQuantity'];
        $invoiceQty = $line['invoiceQuantity'];

        if ($referenceQty === 0.0) {
            return $invoiceQty === 0.0 ? 0.0 : 100.0;
        }

        return abs($invoiceQty - $referenceQty) / $referenceQty * 100;
    }
}
