<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\GoodsReceipt;

use Nexus\ProcurementOperations\DTOs\GoodsReceiptContext;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that received quantities are within tolerance of PO quantities.
 *
 * This rule checks:
 * - Receipt quantity does not exceed outstanding PO quantity beyond tolerance
 * - Over-receipt is flagged if beyond configurable threshold
 * - Under-receipt is allowed (partial receipts are valid)
 */
final readonly class QuantityToleranceRule implements RuleInterface
{
    private const RULE_NAME = 'quantity_tolerance';

    /**
     * @param float $overReceiptTolerancePercent Maximum allowed over-receipt percentage (default 5%)
     */
    public function __construct(
        private float $overReceiptTolerancePercent = 5.0,
    ) {}

    public function getName(): string
    {
        return self::RULE_NAME;
    }

    /**
     * Check if receipt quantities are within tolerance.
     *
     * @param GoodsReceiptContext|RecordGoodsReceiptRequest $context
     */
    public function check(object $context): RuleResult
    {
        if ($context instanceof RecordGoodsReceiptRequest) {
            return $this->checkRequest($context);
        }

        if ($context instanceof GoodsReceiptContext) {
            return $this->checkContext($context);
        }

        return RuleResult::fail(
            self::RULE_NAME,
            'Invalid context type for quantity tolerance check',
            ['expected' => [GoodsReceiptContext::class, RecordGoodsReceiptRequest::class]]
        );
    }

    /**
     * Check quantities from a receipt request against PO outstanding quantities.
     */
    private function checkRequest(RecordGoodsReceiptRequest $request): RuleResult
    {
        $violations = [];

        foreach ($request->lineItems as $index => $line) {
            $quantityReceived = $line['quantityReceived'] ?? 0.0;
            $poQuantity = $line['poQuantity'] ?? 0.0;
            $alreadyReceived = $line['alreadyReceived'] ?? 0.0;

            $outstanding = max(0, $poQuantity - $alreadyReceived);

            if ($outstanding <= 0 && $quantityReceived > 0) {
                $violations[] = [
                    'lineIndex' => $index,
                    'poLineId' => $line['poLineId'] ?? 'unknown',
                    'issue' => 'no_outstanding',
                    'message' => "Line {$index}: No outstanding quantity, cannot receive more",
                ];
                continue;
            }

            // Calculate maximum allowed with tolerance
            $maxAllowed = $outstanding * (1 + $this->overReceiptTolerancePercent / 100);

            if ($quantityReceived > $maxAllowed) {
                $overReceiptPercent = (($quantityReceived - $outstanding) / $outstanding) * 100;
                $violations[] = [
                    'lineIndex' => $index,
                    'poLineId' => $line['poLineId'] ?? 'unknown',
                    'issue' => 'over_receipt',
                    'quantityReceived' => $quantityReceived,
                    'maxAllowed' => $maxAllowed,
                    'outstanding' => $outstanding,
                    'overReceiptPercent' => round($overReceiptPercent, 2),
                    'tolerancePercent' => $this->overReceiptTolerancePercent,
                    'message' => sprintf(
                        "Line %d: Over-receipt of %.2f%% exceeds tolerance of %.2f%%",
                        $index,
                        $overReceiptPercent,
                        $this->overReceiptTolerancePercent
                    ),
                ];
            }
        }

        if (count($violations) > 0) {
            return RuleResult::fail(
                self::RULE_NAME,
                sprintf('%d line(s) exceed quantity tolerance', count($violations)),
                ['violations' => $violations]
            );
        }

        return RuleResult::pass(
            self::RULE_NAME,
            'All quantities within tolerance',
            ['tolerancePercent' => $this->overReceiptTolerancePercent]
        );
    }

    /**
     * Check quantities from existing context.
     */
    private function checkContext(GoodsReceiptContext $context): RuleResult
    {
        if ($context->purchaseOrderInfo === null) {
            return RuleResult::fail(
                self::RULE_NAME,
                'Cannot validate quantity tolerance: PO info not available'
            );
        }

        $totalReceived = $context->getTotalQuantityReceived();
        $totalOrdered = $context->purchaseOrderInfo['totalOrderedQuantity'];
        $alreadyReceived = $context->purchaseOrderInfo['totalReceivedQuantity'] - $totalReceived;

        $outstanding = max(0, $totalOrdered - $alreadyReceived);
        $maxAllowed = $outstanding * (1 + $this->overReceiptTolerancePercent / 100);

        if ($totalReceived > $maxAllowed && $outstanding > 0) {
            $overReceiptPercent = (($totalReceived - $outstanding) / $outstanding) * 100;

            return RuleResult::fail(
                self::RULE_NAME,
                sprintf(
                    'Total receipt quantity exceeds tolerance: %.2f received, %.2f allowed (%.2f%% tolerance)',
                    $totalReceived,
                    $maxAllowed,
                    $this->overReceiptTolerancePercent
                ),
                [
                    'totalReceived' => $totalReceived,
                    'outstanding' => $outstanding,
                    'maxAllowed' => $maxAllowed,
                    'overReceiptPercent' => round($overReceiptPercent, 2),
                    'tolerancePercent' => $this->overReceiptTolerancePercent,
                ]
            );
        }

        return RuleResult::pass(
            self::RULE_NAME,
            'Receipt quantity within tolerance',
            [
                'totalReceived' => $totalReceived,
                'outstanding' => $outstanding,
                'tolerancePercent' => $this->overReceiptTolerancePercent,
            ]
        );
    }
}
