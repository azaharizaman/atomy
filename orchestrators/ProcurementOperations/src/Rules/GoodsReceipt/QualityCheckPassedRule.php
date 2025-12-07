<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\GoodsReceipt;

use Nexus\ProcurementOperations\DTOs\GoodsReceiptContext;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that all items have passed quality checks.
 *
 * This rule checks:
 * - Quality status is set for all line items
 * - No items are in 'rejected' or 'failed' quality status
 * - Items pending quality check may be flagged depending on config
 */
final readonly class QualityCheckPassedRule implements RuleInterface
{
    private const RULE_NAME = 'quality_check_passed';

    /**
     * @param bool $allowPending Whether to allow items with 'pending' quality status
     * @param array<string> $failedStatuses Quality statuses that indicate failure
     */
    public function __construct(
        private bool $allowPending = true,
        private array $failedStatuses = ['rejected', 'failed', 'quarantine'],
    ) {}

    public function getName(): string
    {
        return self::RULE_NAME;
    }

    /**
     * Check if all line items have passed quality checks.
     *
     * @param GoodsReceiptContext $context
     */
    public function check(object $context): RuleResult
    {
        if (!$context instanceof GoodsReceiptContext) {
            return RuleResult::fail(
                self::RULE_NAME,
                'Invalid context type for quality check rule',
                ['expected' => GoodsReceiptContext::class]
            );
        }

        $failedItems = [];
        $pendingItems = [];

        foreach ($context->lineItems as $index => $line) {
            $qualityStatus = strtolower($line['qualityStatus'] ?? '');

            // Check for failed statuses
            if (in_array($qualityStatus, $this->failedStatuses, true)) {
                $failedItems[] = [
                    'lineIndex' => $index,
                    'lineId' => $line['lineId'] ?? 'unknown',
                    'productId' => $line['productId'] ?? 'unknown',
                    'qualityStatus' => $qualityStatus,
                    'message' => sprintf(
                        "Line %d (Product %s): Quality check failed with status '%s'",
                        $index,
                        $line['productId'] ?? 'unknown',
                        $qualityStatus
                    ),
                ];
            }

            // Track pending items
            if ($qualityStatus === 'pending' || $qualityStatus === '') {
                $pendingItems[] = [
                    'lineIndex' => $index,
                    'lineId' => $line['lineId'] ?? 'unknown',
                    'productId' => $line['productId'] ?? 'unknown',
                ];
            }
        }

        // Fail if any items have failed quality checks
        if (count($failedItems) > 0) {
            return RuleResult::fail(
                self::RULE_NAME,
                sprintf('%d item(s) failed quality check', count($failedItems)),
                [
                    'failedItems' => $failedItems,
                    'pendingItems' => $pendingItems,
                ]
            );
        }

        // Check pending items if not allowed
        if (!$this->allowPending && count($pendingItems) > 0) {
            return RuleResult::fail(
                self::RULE_NAME,
                sprintf('%d item(s) have pending quality checks', count($pendingItems)),
                [
                    'pendingItems' => $pendingItems,
                    'allowPending' => $this->allowPending,
                ]
            );
        }

        return RuleResult::pass(
            self::RULE_NAME,
            'All items passed quality check',
            [
                'totalItems' => count($context->lineItems),
                'pendingItems' => count($pendingItems),
                'allowPending' => $this->allowPending,
            ]
        );
    }
}
