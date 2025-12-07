<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\InvoiceMatching;

use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that invoice prices match purchase order prices within tolerance.
 *
 * Checks both:
 * - Total invoice amount vs total PO amount
 * - Individual line item prices
 *
 * Price variance is calculated as: |Invoice Price - PO Price| / PO Price * 100
 */
final readonly class PriceMatchRule implements RuleInterface
{
    private const DEFAULT_TOLERANCE_PERCENT = 0.0;

    public function __construct(
        private float $tolerancePercent = self::DEFAULT_TOLERANCE_PERCENT,
    ) {}

    public function getName(): string
    {
        return 'price_match';
    }

    /**
     * Check if invoice prices match PO prices within tolerance.
     *
     * @param ThreeWayMatchContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof ThreeWayMatchContext) {
            return RuleResult::fail(
                $this->getName(),
                'Invalid context type for price match rule',
            );
        }

        $variances = [];
        $maxVariance = 0.0;
        $allWithinTolerance = true;

        // Check total amount variance
        $totalVariance = $this->calculateTotalVariance($context);
        $maxVariance = max($maxVariance, $totalVariance);

        if ($totalVariance > $this->tolerancePercent) {
            $allWithinTolerance = false;
            $variances['total'] = [
                'poAmountCents' => $context->totals['totalPoAmountCents'],
                'invoiceAmountCents' => $context->totals['totalInvoiceAmountCents'],
                'variancePercent' => $totalVariance,
                'tolerancePercent' => $this->tolerancePercent,
            ];
        }

        // Check line item price variances
        foreach ($context->lineComparison as $index => $line) {
            $lineVariance = $this->calculateLineVariance($line);

            if ($lineVariance > $this->tolerancePercent) {
                $allWithinTolerance = false;
                $variances["line_{$index}"] = [
                    'lineId' => $line['lineId'],
                    'productId' => $line['productId'],
                    'poUnitPriceCents' => $line['poUnitPriceCents'],
                    'invoiceUnitPriceCents' => $line['invoiceUnitPriceCents'],
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
                    'Price match passed. Maximum variance: %.2f%% (tolerance: %.2f%%)',
                    $maxVariance,
                    $this->tolerancePercent,
                ),
                [
                    'maxVariancePercent' => $maxVariance,
                    'tolerancePercent' => $this->tolerancePercent,
                ],
            );
        }

        return RuleResult::fail(
            $this->getName(),
            sprintf(
                'Price variance %.2f%% exceeds tolerance %.2f%%',
                $maxVariance,
                $this->tolerancePercent,
            ),
            [
                'maxVariancePercent' => $maxVariance,
                'tolerancePercent' => $this->tolerancePercent,
                'variances' => $variances,
            ],
        );
    }

    /**
     * Calculate total amount variance percentage.
     */
    private function calculateTotalVariance(ThreeWayMatchContext $context): float
    {
        $poAmount = $context->totals['totalPoAmountCents'];
        $invoiceAmount = $context->totals['totalInvoiceAmountCents'];

        if ($poAmount === 0) {
            return $invoiceAmount === 0 ? 0.0 : 100.0;
        }

        return abs($invoiceAmount - $poAmount) / $poAmount * 100;
    }

    /**
     * Calculate line item price variance percentage.
     *
     * @param array{poUnitPriceCents: int, invoiceUnitPriceCents: int} $line
     */
    private function calculateLineVariance(array $line): float
    {
        $poPrice = $line['poUnitPriceCents'];
        $invoicePrice = $line['invoiceUnitPriceCents'];

        if ($poPrice === 0) {
            return $invoicePrice === 0 ? 0.0 : 100.0;
        }

        return abs($invoicePrice - $poPrice) / $poPrice * 100;
    }
}
