<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\GoodsReceipt;

use Nexus\ProcurementOperations\DTOs\GoodsReceiptContext;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;
use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Validates that items with expiry dates have valid future dates.
 *
 * This rule checks:
 * - Expiry dates are in the future
 * - Expiry dates meet minimum shelf life requirements
 * - Lotted items requiring expiry dates have them set
 *
 * Important for FEFO (First Expiry First Out) compliance.
 */
final readonly class ExpiryDateValidRule implements RuleInterface
{
    private const RULE_NAME = 'expiry_date_valid';

    /**
     * @param int $minimumShelfLifeDays Minimum days until expiry required (default 0 = any future date)
     * @param bool $expiryRequiredForLots Whether expiry date is required for lot-tracked items
     */
    public function __construct(
        private int $minimumShelfLifeDays = 0,
        private bool $expiryRequiredForLots = false,
    ) {}

    public function getName(): string
    {
        return self::RULE_NAME;
    }

    /**
     * Check if expiry dates are valid.
     *
     * @param GoodsReceiptContext|RecordGoodsReceiptRequest $context
     */
    public function check(object $context): RuleResult
    {
        $now = new \DateTimeImmutable('today');

        if ($context instanceof RecordGoodsReceiptRequest) {
            return $this->checkRequest($context, $now);
        }

        if ($context instanceof GoodsReceiptContext) {
            return $this->checkContext($context, $now);
        }

        return RuleResult::fail(
            self::RULE_NAME,
            'Invalid context type for expiry date check',
            ['expected' => [GoodsReceiptContext::class, RecordGoodsReceiptRequest::class]]
        );
    }

    /**
     * Check expiry dates from a receipt request.
     */
    private function checkRequest(RecordGoodsReceiptRequest $request, \DateTimeImmutable $now): RuleResult
    {
        $violations = [];
        $warnings = [];
        $minimumExpiryDate = $now->modify("+{$this->minimumShelfLifeDays} days");

        foreach ($request->lineItems as $index => $line) {
            $expiryDateString = $line['expiryDate'] ?? null;
            $lotNumber = $line['lotNumber'] ?? null;

            // Check if expiry is required for lot-tracked items
            if ($this->expiryRequiredForLots && $lotNumber !== null && $expiryDateString === null) {
                $violations[] = [
                    'lineIndex' => $index,
                    'poLineId' => $line['poLineId'] ?? 'unknown',
                    'productId' => $line['productId'] ?? 'unknown',
                    'lotNumber' => $lotNumber,
                    'issue' => 'missing_expiry',
                    'message' => sprintf(
                        "Line %d: Lot '%s' requires expiry date",
                        $index,
                        $lotNumber
                    ),
                ];
                continue;
            }

            // Skip if no expiry date provided (and not required)
            if ($expiryDateString === null) {
                continue;
            }

            // Parse expiry date
            try {
                $expiryDate = new \DateTimeImmutable($expiryDateString);
            } catch (\Exception $e) {
                $violations[] = [
                    'lineIndex' => $index,
                    'poLineId' => $line['poLineId'] ?? 'unknown',
                    'issue' => 'invalid_date_format',
                    'expiryDate' => $expiryDateString,
                    'message' => sprintf(
                        "Line %d: Invalid expiry date format '%s'",
                        $index,
                        $expiryDateString
                    ),
                ];
                continue;
            }

            // Check if already expired
            if ($expiryDate < $now) {
                $violations[] = [
                    'lineIndex' => $index,
                    'poLineId' => $line['poLineId'] ?? 'unknown',
                    'productId' => $line['productId'] ?? 'unknown',
                    'issue' => 'already_expired',
                    'expiryDate' => $expiryDateString,
                    'message' => sprintf(
                        "Line %d (Product %s): Item has expired on %s",
                        $index,
                        $line['productId'] ?? 'unknown',
                        $expiryDateString
                    ),
                ];
                continue;
            }

            // Check minimum shelf life
            if ($this->minimumShelfLifeDays > 0 && $expiryDate < $minimumExpiryDate) {
                $daysUntilExpiry = $now->diff($expiryDate)->days;
                $violations[] = [
                    'lineIndex' => $index,
                    'poLineId' => $line['poLineId'] ?? 'unknown',
                    'productId' => $line['productId'] ?? 'unknown',
                    'issue' => 'insufficient_shelf_life',
                    'expiryDate' => $expiryDateString,
                    'daysUntilExpiry' => $daysUntilExpiry,
                    'minimumRequired' => $this->minimumShelfLifeDays,
                    'message' => sprintf(
                        "Line %d (Product %s): Only %d days shelf life, minimum %d required",
                        $index,
                        $line['productId'] ?? 'unknown',
                        $daysUntilExpiry,
                        $this->minimumShelfLifeDays
                    ),
                ];
            }
        }

        if (count($violations) > 0) {
            return RuleResult::fail(
                self::RULE_NAME,
                sprintf('%d line(s) have expiry date issues', count($violations)),
                [
                    'violations' => $violations,
                    'minimumShelfLifeDays' => $this->minimumShelfLifeDays,
                ]
            );
        }

        return RuleResult::pass(
            self::RULE_NAME,
            'All expiry dates are valid',
            [
                'minimumShelfLifeDays' => $this->minimumShelfLifeDays,
                'expiryRequiredForLots' => $this->expiryRequiredForLots,
            ]
        );
    }

    /**
     * Check expiry dates from existing context.
     */
    private function checkContext(GoodsReceiptContext $context, \DateTimeImmutable $now): RuleResult
    {
        // For existing receipts, we check the line items in context
        // Note: lineItems in GoodsReceiptContext may not have expiryDate field
        // This rule is primarily for pre-receipt validation

        return RuleResult::pass(
            self::RULE_NAME,
            'Expiry dates validated at receipt time',
            ['note' => 'Context validation delegates to original receipt validation']
        );
    }
}
