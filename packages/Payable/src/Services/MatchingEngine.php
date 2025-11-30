<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\ThreeWayMatcherInterface;
use Nexus\Payable\Contracts\VendorBillInterface;
use Nexus\Payable\Contracts\VendorInterface;
use Nexus\Payable\Contracts\MatchingResultInterface;
use Nexus\Payable\Contracts\MatchingToleranceInterface;
use Nexus\Payable\Contracts\VendorBillLineInterface;
use Nexus\Payable\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Payable\Contracts\GoodsReceivedRepositoryInterface;
use Nexus\Payable\Enums\MatchingStatus;
use Nexus\Payable\Exceptions\MatchingFailedException;
use Nexus\Payable\ValueObjects\MatchingTolerance;
use Psr\Log\LoggerInterface;

/**
 * 3-way matching engine.
 *
 * Validates vendor bills against PO and GRN with per-vendor tolerances.
 */
final class MatchingEngine implements ThreeWayMatcherInterface
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly GoodsReceivedRepositoryInterface $goodsReceivedRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function match(VendorBillInterface $bill, VendorInterface $vendor): MatchingResultInterface
    {
        $this->logger->info("Starting 3-way match for bill {$bill->getId()}");

        // Get vendor-specific tolerance
        $tolerance = new MatchingTolerance(
            $vendor->getQtyTolerancePercent(),
            $vendor->getPriceTolerancePercent()
        );

        $lineResults = [];
        $variances = [];

        foreach ($bill->getLines() as $billLine) {
            try {
                $lineResult = $this->matchLine($billLine, $tolerance);
                $lineResults[] = $lineResult;

                if (!$lineResult->isMatched()) {
                    $variances[] = [
                        'line_number' => $lineResult->getLineNumber(),
                        'qty_variance' => $lineResult->getQtyVariancePercent(),
                        'price_variance' => $lineResult->getPriceVariancePercent(),
                        'reason' => $lineResult->getVarianceReason(),
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->error("Line matching failed for bill {$bill->getId()} line {$billLine->getLineNumber()}: {$e->getMessage()}");
                throw MatchingFailedException::forBill($bill->getId(), $e->getMessage());
            }
        }

        // Determine overall matching status
        $allMatched = array_reduce($lineResults, fn($carry, $line) => $carry && $line->isMatched(), true);
        $withinTolerance = array_reduce($lineResults, fn($carry, $line) => $carry && $line->isQtyWithinTolerance() && $line->isPriceWithinTolerance(), true);

        if ($allMatched) {
            $status = MatchingStatus::MATCHED;
        } elseif ($withinTolerance) {
            $status = MatchingStatus::MATCHED; // Within tolerance = auto-match
        } elseif (!empty($variances)) {
            $status = MatchingStatus::VARIANCE_REVIEW;
        } else {
            $status = MatchingStatus::FAILED;
        }

        $result = new MatchingResult($status, $lineResults, $variances);

        $this->logger->info("3-way match completed for bill {$bill->getId()}: {$status->value}");

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function matchLine(VendorBillLineInterface $billLine, MatchingToleranceInterface $tolerance): LineMatchingResult
    {
        // Get PO line reference
        $poLineRef = $billLine->getPoLineReference();
        if (!$poLineRef) {
            throw new \RuntimeException("Bill line {$billLine->getLineNumber()} missing PO reference");
        }

        // Get GRN line reference
        $grnLineRef = $billLine->getGrnLineReference();
        if (!$grnLineRef) {
            throw new \RuntimeException("Bill line {$billLine->getLineNumber()} missing GRN reference");
        }

        // Fetch PO line (from Nexus\Procurement)
        $poLine = $this->purchaseOrderRepository->findLineByReference($poLineRef);
        if (!$poLine) {
            throw new \RuntimeException("PO line '{$poLineRef}' not found");
        }

        // Fetch GRN line (from Nexus\Inventory)
        $grnLine = $this->goodsReceivedRepository->findLineByReference($grnLineRef);
        if (!$grnLine) {
            throw new \RuntimeException("GRN line '{$grnLineRef}' not found");
        }

        // Extract quantities and prices
        $poQuantity = $poLine->getQuantity();
        $grnQuantity = $grnLine->getQuantity();
        $billQuantity = $billLine->getQuantity();
        $poUnitPrice = $poLine->getUnitPrice();
        $billUnitPrice = $billLine->getUnitPrice();

        // Calculate quantity variance (bill vs GRN)
        $qtyVariancePercent = $this->calculateVariancePercent($billQuantity, $grnQuantity);
        $qtyWithinTolerance = $tolerance->isQtyWithinTolerance($qtyVariancePercent);

        // Calculate price variance (bill vs PO)
        $priceVariancePercent = $this->calculateVariancePercent($billUnitPrice, $poUnitPrice);
        $priceWithinTolerance = $tolerance->isPriceWithinTolerance($priceVariancePercent);

        // Determine if line is matched
        $matched = $qtyWithinTolerance && $priceWithinTolerance;

        // Build variance reason
        $varianceReason = null;
        if (!$matched) {
            $reasons = [];
            if (!$qtyWithinTolerance) {
                $reasons[] = "Qty variance {$qtyVariancePercent}% exceeds tolerance";
            }
            if (!$priceWithinTolerance) {
                $reasons[] = "Price variance {$priceVariancePercent}% exceeds tolerance";
            }
            $varianceReason = implode('; ', $reasons);
        }

        return new LineMatchingResult(
            lineNumber: $billLine->getLineNumber(),
            matched: $matched,
            qtyVariancePercent: $qtyVariancePercent,
            priceVariancePercent: $priceVariancePercent,
            qtyWithinTolerance: $qtyWithinTolerance,
            priceWithinTolerance: $priceWithinTolerance,
            poQuantity: $poQuantity,
            grnQuantity: $grnQuantity,
            billQuantity: $billQuantity,
            poUnitPrice: $poUnitPrice,
            billUnitPrice: $billUnitPrice,
            varianceReason: $varianceReason
        );
    }

    /**
     * {@inheritdoc}
     */
    public function canAutoMatch(MatchingResultInterface $result): bool
    {
        return $result->isWithinTolerance();
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchingStatus(VendorBillInterface $bill): string
    {
        // Return current matching status from bill
        return $bill->getMatchingStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function overrideVariance(string $billId, string $reason, string $approvedBy): void
    {
        $this->logger->warning("Variance override for bill {$billId} by {$approvedBy}: {$reason}");
        
        // This would typically update bill matching status to OVERRIDDEN
        // Implementation depends on repository pattern
    }

    /**
     * Calculate variance percentage.
     *
     * @param float $actual Actual value (from bill)
     * @param float $expected Expected value (from PO/GRN)
     * @return float Variance percentage
     */
    private function calculateVariancePercent(float $actual, float $expected): float
    {
        if ($expected == 0.0) {
            return $actual == 0.0 ? 0.0 : 100.0;
        }

        return (($actual - $expected) / $expected) * 100.0;
    }
}
