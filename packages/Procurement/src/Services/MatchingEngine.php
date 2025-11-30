<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;
use Psr\Log\LoggerInterface;

/**
 * Three-way matching engine for procurement.
 * 
 * Matches Purchase Order → Goods Receipt → Supplier Invoice (via Nexus\Payable).
 * 
 * Performance target: < 500ms for 100-line bills (PER-PRO-0327).
 */
final readonly class MatchingEngine
{
    public function __construct(
        private LoggerInterface $logger,
        private float $quantityTolerancePercent = 5.0,
        private float $priceTolerancePercent = 5.0
    ) {
    }

    /**
     * Perform three-way match: PO → GRN → Invoice Line.
     *
     * This method is called by Nexus\Payable when processing supplier invoices.
     *
     * @param PurchaseOrderLineInterface $poLine
     * @param GoodsReceiptLineInterface $grnLine
     * @param array{
     *   quantity: float,
     *   unit_price: float,
     *   line_total: float
     * } $invoiceLineData
     * @return array{
     *   matched: bool,
     *   discrepancies: array<string, array{po_value: mixed, grn_value: mixed, invoice_value: mixed, variance_percent: float}>,
     *   recommendation: string
     * }
     */
    public function performThreeWayMatch(
        PurchaseOrderLineInterface $poLine,
        GoodsReceiptLineInterface $grnLine,
        array $invoiceLineData
    ): array {
        $startTime = microtime(true);

        $discrepancies = [];
        $matched = true;

        // Match 1: Verify PO line and GRN line are related
        if ($poLine->getLineReference() !== $grnLine->getPoLineReference()) {
            $this->logger->warning('Three-way match: PO and GRN line reference mismatch', [
                'po_line_ref' => $poLine->getLineReference(),
                'grn_line_ref' => $grnLine->getPoLineReference(),
            ]);

            return [
                'matched' => false,
                'discrepancies' => ['line_reference' => [
                    'po_value' => $poLine->getLineReference(),
                    'grn_value' => $grnLine->getPoLineReference(),
                    'invoice_value' => null,
                    'variance_percent' => 0.0,
                ]],
                'recommendation' => 'REJECT: PO and GRN line references do not match.',
            ];
        }

        // Match 2: Quantity - Invoice vs GRN
        $quantityVariance = $this->calculateVariance(
            $grnLine->getQuantity(),
            $invoiceLineData['quantity']
        );

        if ($quantityVariance > $this->quantityTolerancePercent) {
            $matched = false;
            $discrepancies['quantity'] = [
                'po_value' => $poLine->getQuantity(),
                'grn_value' => $grnLine->getQuantity(),
                'invoice_value' => $invoiceLineData['quantity'],
                'variance_percent' => $quantityVariance,
            ];
        }

        // Match 3: Unit Price - Invoice vs PO
        $priceVariance = $this->calculateVariance(
            $poLine->getUnitPrice(),
            $invoiceLineData['unit_price']
        );

        if ($priceVariance > $this->priceTolerancePercent) {
            $matched = false;
            $discrepancies['unit_price'] = [
                'po_value' => $poLine->getUnitPrice(),
                'grn_value' => null,
                'invoice_value' => $invoiceLineData['unit_price'],
                'variance_percent' => $priceVariance,
            ];
        }

        // Calculate expected total based on GRN quantity and PO price
        $expectedTotal = $grnLine->getQuantity() * $poLine->getUnitPrice();
        $totalVariance = $this->calculateVariance($expectedTotal, $invoiceLineData['line_total']);

        if ($totalVariance > max($this->quantityTolerancePercent, $this->priceTolerancePercent)) {
            $matched = false;
            $discrepancies['line_total'] = [
                'po_value' => $poLine->getQuantity() * $poLine->getUnitPrice(),
                'grn_value' => $grnLine->getQuantity() * $poLine->getUnitPrice(),
                'invoice_value' => $invoiceLineData['line_total'],
                'variance_percent' => $totalVariance,
            ];
        }

        $elapsedMs = (microtime(true) - $startTime) * 1000;

        $this->logger->info('Three-way match completed', [
            'po_line_ref' => $poLine->getLineReference(),
            'matched' => $matched,
            'discrepancy_count' => count($discrepancies),
            'elapsed_ms' => $elapsedMs,
        ]);

        $recommendation = $this->generateRecommendation($matched, $discrepancies);

        return [
            'matched' => $matched,
            'discrepancies' => $discrepancies,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Batch three-way matching for multiple invoice lines.
     *
     * Optimized for performance (PER-PRO-0327: < 500ms for 100 lines).
     *
     * @param array<array{po_line: PurchaseOrderLineInterface, grn_line: GoodsReceiptLineInterface, invoice_line: array}> $matchSet
     * @return array{
     *   overall_matched: bool,
     *   total_lines: int,
     *   matched_lines: int,
     *   discrepancy_lines: int,
     *   line_results: array<int, array>,
     *   elapsed_ms: float
     * }
     */
    public function performBatchMatch(array $matchSet): array
    {
        $startTime = microtime(true);

        $totalLines = count($matchSet);
        $matchedLines = 0;
        $discrepancyLines = 0;
        $lineResults = [];

        foreach ($matchSet as $index => $match) {
            $result = $this->performThreeWayMatch(
                $match['po_line'],
                $match['grn_line'],
                $match['invoice_line']
            );

            $lineResults[$index] = $result;

            if ($result['matched']) {
                $matchedLines++;
            } else {
                $discrepancyLines++;
            }
        }

        $elapsedMs = (microtime(true) - $startTime) * 1000;

        $this->logger->info('Batch three-way match completed', [
            'total_lines' => $totalLines,
            'matched_lines' => $matchedLines,
            'discrepancy_lines' => $discrepancyLines,
            'elapsed_ms' => $elapsedMs,
        ]);

        return [
            'overall_matched' => $discrepancyLines === 0,
            'total_lines' => $totalLines,
            'matched_lines' => $matchedLines,
            'discrepancy_lines' => $discrepancyLines,
            'line_results' => $lineResults,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    /**
     * Calculate variance percentage between two values.
     *
     * @param float $expected
     * @param float $actual
     * @return float Variance as percentage
     */
    private function calculateVariance(float $expected, float $actual): float
    {
        if ($expected == 0) {
            return $actual == 0 ? 0.0 : 100.0;
        }

        return abs(($actual - $expected) / $expected) * 100;
    }

    /**
     * Generate recommendation based on match results.
     *
     * @param bool $matched
     * @param array<string, mixed> $discrepancies
     * @return string
     */
    private function generateRecommendation(bool $matched, array $discrepancies): string
    {
        if ($matched) {
            return 'APPROVE: All values match within tolerance.';
        }

        $reasons = [];

        if (isset($discrepancies['quantity'])) {
            $reasons[] = sprintf(
                'Quantity variance: %.2f%%',
                $discrepancies['quantity']['variance_percent']
            );
        }

        if (isset($discrepancies['unit_price'])) {
            $reasons[] = sprintf(
                'Unit price variance: %.2f%%',
                $discrepancies['unit_price']['variance_percent']
            );
        }

        if (isset($discrepancies['line_total'])) {
            $reasons[] = sprintf(
                'Line total variance: %.2f%%',
                $discrepancies['line_total']['variance_percent']
            );
        }

        return 'REVIEW REQUIRED: ' . implode('; ', $reasons);
    }
}
