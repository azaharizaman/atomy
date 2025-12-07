<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\Contracts\ThreeWayMatchingServiceInterface;
use Nexus\ProcurementOperations\DTOs\MatchingResult;
use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Rules\InvoiceMatching\InvoiceMatchingRuleRegistry;
use Nexus\ProcurementOperations\Rules\InvoiceMatching\PriceMatchRule;
use Nexus\ProcurementOperations\Rules\InvoiceMatching\QuantityMatchRule;
use Nexus\ProcurementOperations\Rules\InvoiceMatching\ToleranceThresholdRule;

/**
 * Pure calculation service for three-way matching.
 *
 * Performs invoice matching against purchase orders and goods receipts:
 * - Price validation (Invoice vs PO)
 * - Quantity validation (Invoice vs GR for 3-way, vs PO for 2-way)
 * - Variance calculation and threshold checking
 *
 * No side effects - only computes match results.
 * GL posting and status updates are handled by the Coordinator and Listeners.
 */
final readonly class ThreeWayMatchingService implements ThreeWayMatchingServiceInterface
{
    /**
     * Perform three-way match calculation.
     *
     * Compares:
     * - Invoice quantities vs GR quantities (what was received)
     * - Invoice prices vs PO prices (what was agreed)
     * - Invoice amounts vs PO/GR values
     *
     * @param ThreeWayMatchContext $context Aggregated data from PO, GR, Invoice
     * @param float $priceTolerancePercent Maximum allowed price variance (%)
     * @param float $quantityTolerancePercent Maximum allowed quantity variance (%)
     */
    public function calculateMatch(
        ThreeWayMatchContext $context,
        float $priceTolerancePercent,
        float $quantityTolerancePercent
    ): MatchingResult {
        // Create rule registry with specified tolerances
        $ruleRegistry = InvoiceMatchingRuleRegistry::withTolerances(
            priceTolerancePercent: $priceTolerancePercent,
            quantityTolerancePercent: $quantityTolerancePercent,
            amountTolerancePercent: max($priceTolerancePercent, $quantityTolerancePercent),
            threeWayMatch: true,
        );

        // Get validation summary
        $summary = $ruleRegistry->getSummary($context);

        // Calculate variances
        $priceVariance = $context->calculatePriceVariancePercent();
        $quantityVariance = $context->calculateQuantityVariancePercent();

        if ($summary['allPassed']) {
            return MatchingResult::matched(
                vendorBillId: $context->vendorBillId,
                purchaseOrderId: $context->purchaseOrderId,
                priceVariancePercent: $priceVariance,
                quantityVariancePercent: $quantityVariance,
                message: sprintf(
                    'Three-way match successful. Price variance: %.2f%%, Quantity variance: %.2f%%',
                    $priceVariance,
                    $quantityVariance,
                ),
            );
        }

        // Build failure details
        $variances = $this->buildVarianceDetails($context, $summary);
        $failedRules = $ruleRegistry->getFailedRules($context);

        return MatchingResult::failed(
            vendorBillId: $context->vendorBillId,
            purchaseOrderId: $context->purchaseOrderId,
            failureReason: $this->buildFailureReason($failedRules, $summary),
            priceVariancePercent: $priceVariance,
            quantityVariancePercent: $quantityVariance,
            variances: $variances,
        );
    }

    /**
     * Perform two-way match calculation (PO vs Invoice only).
     *
     * Used when goods receipt is not required or not yet available.
     * Compares only invoice against purchase order.
     *
     * @param ThreeWayMatchContext $context Context with PO and Invoice (GR may be empty)
     * @param float $priceTolerancePercent Maximum allowed price variance (%)
     */
    public function calculateTwoWayMatch(
        ThreeWayMatchContext $context,
        float $priceTolerancePercent
    ): MatchingResult {
        // Create rule registry for two-way matching
        $ruleRegistry = InvoiceMatchingRuleRegistry::withTolerances(
            priceTolerancePercent: $priceTolerancePercent,
            quantityTolerancePercent: $priceTolerancePercent, // Use price tolerance for qty in 2-way
            amountTolerancePercent: $priceTolerancePercent,
            threeWayMatch: false, // Compare invoice qty vs PO qty instead of GR qty
        );

        // Get validation summary
        $summary = $ruleRegistry->getSummary($context);

        // Calculate variances (for 2-way, quantity is vs PO)
        $priceVariance = $context->calculatePriceVariancePercent();
        $quantityVariance = $this->calculateTwoWayQuantityVariance($context);

        if ($summary['allPassed']) {
            return MatchingResult::matched(
                vendorBillId: $context->vendorBillId,
                purchaseOrderId: $context->purchaseOrderId,
                priceVariancePercent: $priceVariance,
                quantityVariancePercent: $quantityVariance,
                message: sprintf(
                    'Two-way match successful. Price variance: %.2f%%, Quantity variance: %.2f%%',
                    $priceVariance,
                    $quantityVariance,
                ),
            );
        }

        // Build failure details
        $variances = $this->buildVarianceDetails($context, $summary);
        $failedRules = $ruleRegistry->getFailedRules($context);

        return MatchingResult::failed(
            vendorBillId: $context->vendorBillId,
            purchaseOrderId: $context->purchaseOrderId,
            failureReason: $this->buildFailureReason($failedRules, $summary),
            priceVariancePercent: $priceVariance,
            quantityVariancePercent: $quantityVariance,
            variances: $variances,
        );
    }

    /**
     * Calculate variance percentages.
     *
     * @return array{
     *     priceVariancePercent: float,
     *     quantityVariancePercent: float,
     *     amountVariancePercent: float,
     *     lineVariances: array<string, array{
     *         priceVariance: float,
     *         quantityVariance: float,
     *         withinTolerance: bool
     *     }>
     * }
     */
    public function calculateVariances(ThreeWayMatchContext $context): array
    {
        $lineVariances = [];

        foreach ($context->lineComparison as $index => $line) {
            $lineId = $line['lineId'];

            $priceVariance = $this->calculateLinePriceVariance($line);
            $quantityVariance = $this->calculateLineQuantityVariance($line);

            $lineVariances[$lineId] = [
                'priceVariance' => $priceVariance,
                'quantityVariance' => $quantityVariance,
                'withinTolerance' => true, // Default, actual tolerance check happens in rules
            ];
        }

        return [
            'priceVariancePercent' => $context->calculatePriceVariancePercent(),
            'quantityVariancePercent' => $context->calculateQuantityVariancePercent(),
            'amountVariancePercent' => $this->calculateAmountVariance($context),
            'lineVariances' => $lineVariances,
        ];
    }

    /**
     * Build detailed variance breakdown for reporting.
     *
     * @param array{results: array<string, array{passed: bool, message: string, data: array}>} $summary
     * @return array<string, array{type: string, field: string, expected: mixed, actual: mixed, variancePercent: float}>
     */
    private function buildVarianceDetails(ThreeWayMatchContext $context, array $summary): array
    {
        $variances = [];

        // Price variance
        $variances['price'] = [
            'type' => 'price',
            'field' => 'totalAmountCents',
            'expected' => $context->totals['totalPoAmountCents'],
            'actual' => $context->totals['totalInvoiceAmountCents'],
            'variancePercent' => $context->calculatePriceVariancePercent(),
        ];

        // Quantity variance (Invoice vs GR)
        $variances['quantity'] = [
            'type' => 'quantity',
            'field' => 'totalQuantity',
            'expected' => $context->totals['totalGrQuantity'],
            'actual' => $context->totals['totalInvoiceQuantity'],
            'variancePercent' => $context->calculateQuantityVariancePercent(),
        ];

        // Amount variance
        $variances['amount'] = [
            'type' => 'amount',
            'field' => 'totalValue',
            'expected' => $context->totals['totalGrValueCents'],
            'actual' => $context->totals['totalInvoiceAmountCents'],
            'variancePercent' => $this->calculateAmountVariance($context),
        ];

        return $variances;
    }

    /**
     * Build human-readable failure reason.
     *
     * @param array<string> $failedRules
     * @param array{results: array<string, array{passed: bool, message: string}>} $summary
     */
    private function buildFailureReason(array $failedRules, array $summary): string
    {
        $reasons = [];

        foreach ($failedRules as $ruleName) {
            if (isset($summary['results'][$ruleName])) {
                $reasons[] = $summary['results'][$ruleName]['message'];
            }
        }

        if (empty($reasons)) {
            return 'Match failed due to variance exceeding tolerance';
        }

        return implode('; ', $reasons);
    }

    /**
     * Calculate two-way quantity variance (Invoice vs PO).
     */
    private function calculateTwoWayQuantityVariance(ThreeWayMatchContext $context): float
    {
        $poQty = $context->totals['totalPoQuantity'];
        $invoiceQty = $context->totals['totalInvoiceQuantity'];

        if ($poQty === 0.0) {
            return $invoiceQty === 0.0 ? 0.0 : 100.0;
        }

        return abs($invoiceQty - $poQty) / $poQty * 100;
    }

    /**
     * Calculate total amount variance percentage.
     */
    private function calculateAmountVariance(ThreeWayMatchContext $context): float
    {
        $grValue = $context->totals['totalGrValueCents'];
        $invoiceAmount = $context->totals['totalInvoiceAmountCents'];

        if ($grValue === 0) {
            return $invoiceAmount === 0 ? 0.0 : 100.0;
        }

        return abs($invoiceAmount - $grValue) / $grValue * 100;
    }

    /**
     * Calculate line item price variance percentage.
     *
     * @param array{poUnitPriceCents: int, invoiceUnitPriceCents: int} $line
     */
    private function calculateLinePriceVariance(array $line): float
    {
        $poPrice = $line['poUnitPriceCents'];
        $invoicePrice = $line['invoiceUnitPriceCents'];

        if ($poPrice === 0) {
            return $invoicePrice === 0 ? 0.0 : 100.0;
        }

        return abs($invoicePrice - $poPrice) / $poPrice * 100;
    }

    /**
     * Calculate line item quantity variance percentage.
     *
     * @param array{grQuantity: float, invoiceQuantity: float} $line
     */
    private function calculateLineQuantityVariance(array $line): float
    {
        $grQty = $line['grQuantity'];
        $invoiceQty = $line['invoiceQuantity'];

        if ($grQty === 0.0) {
            return $invoiceQty === 0.0 ? 0.0 : 100.0;
        }

        return abs($invoiceQty - $grQty) / $grQty * 100;
    }
}
