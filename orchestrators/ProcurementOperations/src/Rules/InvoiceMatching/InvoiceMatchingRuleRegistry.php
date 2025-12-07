<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\InvoiceMatching;

use Nexus\ProcurementOperations\DTOs\ThreeWayMatchContext;
use Nexus\ProcurementOperations\Exceptions\MatchingException;
use Nexus\ProcurementOperations\Rules\RuleResult;

/**
 * Registry for invoice matching validation rules.
 *
 * Composes and executes all invoice matching rules in sequence:
 * 1. PriceMatchRule - Invoice prices vs PO prices
 * 2. QuantityMatchRule - Invoice quantities vs GR quantities
 * 3. ToleranceThresholdRule - Overall variance thresholds
 *
 * Provides methods for:
 * - Batch validation (validate all rules)
 * - Early-exit validation (stop on first failure)
 * - Summary reporting
 * - Auto-approval determination
 */
final readonly class InvoiceMatchingRuleRegistry
{
    /**
     * @param array<PriceMatchRule|QuantityMatchRule|ToleranceThresholdRule> $rules
     */
    public function __construct(
        private PriceMatchRule $priceMatchRule,
        private QuantityMatchRule $quantityMatchRule,
        private ToleranceThresholdRule $toleranceThresholdRule,
    ) {}

    /**
     * Create registry with default tolerance configuration.
     */
    public static function withTolerances(
        float $priceTolerancePercent = 0.0,
        float $quantityTolerancePercent = 0.0,
        float $amountTolerancePercent = 0.0,
        bool $threeWayMatch = true,
    ): self {
        return new self(
            priceMatchRule: new PriceMatchRule($priceTolerancePercent),
            quantityMatchRule: new QuantityMatchRule($quantityTolerancePercent, $threeWayMatch),
            toleranceThresholdRule: new ToleranceThresholdRule(
                $priceTolerancePercent,
                $quantityTolerancePercent,
                $amountTolerancePercent,
            ),
        );
    }

    /**
     * Validate all rules and return results.
     *
     * @return array<string, RuleResult>
     */
    public function validate(ThreeWayMatchContext $context): array
    {
        return [
            'price_match' => $this->priceMatchRule->check($context),
            'quantity_match' => $this->quantityMatchRule->check($context),
            'tolerance_threshold' => $this->toleranceThresholdRule->check($context),
        ];
    }

    /**
     * Validate all rules and throw on first failure.
     *
     * @throws MatchingException If any rule fails
     */
    public function validateOrFail(ThreeWayMatchContext $context): void
    {
        $results = $this->validate($context);
        $failures = [];

        foreach ($results as $ruleName => $result) {
            if ($result->failed()) {
                $failures[$ruleName] = [
                    'message' => $result->message,
                    'context' => $result->context,
                ];
            }
        }

        if (!empty($failures)) {
            throw MatchingException::validationFailed(
                $context->vendorBillId,
                $failures,
            );
        }
    }

    /**
     * Check if all rules pass.
     */
    public function allPass(ThreeWayMatchContext $context): bool
    {
        $results = $this->validate($context);

        foreach ($results as $result) {
            if ($result->failed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get summary of validation results.
     *
     * @return array{
     *     allPassed: bool,
     *     passedCount: int,
     *     failedCount: int,
     *     results: array<string, array{passed: bool, message: string}>,
     *     canAutoApprove: bool,
     *     requiredApprovalLevel: string|null
     * }
     */
    public function getSummary(ThreeWayMatchContext $context): array
    {
        $results = $this->validate($context);
        $passedCount = 0;
        $failedCount = 0;
        $formattedResults = [];

        foreach ($results as $ruleName => $result) {
            if ($result->passed) {
                $passedCount++;
            } else {
                $failedCount++;
            }

            $formattedResults[$ruleName] = [
                'passed' => $result->passed,
                'message' => $result->message,
                'context' => $result->context,
            ];
        }

        // Determine approval requirements
        $classification = $this->toleranceThresholdRule->classifyVariances($context);

        return [
            'allPassed' => $failedCount === 0,
            'passedCount' => $passedCount,
            'failedCount' => $failedCount,
            'results' => $formattedResults,
            'canAutoApprove' => !$classification['requiresApproval'],
            'requiredApprovalLevel' => $classification['approvalLevel'],
        ];
    }

    /**
     * Check if automatic approval is possible.
     */
    public function canAutoApprove(ThreeWayMatchContext $context): bool
    {
        if (!$this->allPass($context)) {
            return false;
        }

        return $this->toleranceThresholdRule->canAutoApprove($context);
    }

    /**
     * Get the price match rule for individual checks.
     */
    public function getPriceMatchRule(): PriceMatchRule
    {
        return $this->priceMatchRule;
    }

    /**
     * Get the quantity match rule for individual checks.
     */
    public function getQuantityMatchRule(): QuantityMatchRule
    {
        return $this->quantityMatchRule;
    }

    /**
     * Get the tolerance threshold rule for individual checks.
     */
    public function getToleranceThresholdRule(): ToleranceThresholdRule
    {
        return $this->toleranceThresholdRule;
    }

    /**
     * Get all failed rule names.
     *
     * @return array<string>
     */
    public function getFailedRules(ThreeWayMatchContext $context): array
    {
        $results = $this->validate($context);
        $failed = [];

        foreach ($results as $ruleName => $result) {
            if ($result->failed()) {
                $failed[] = $ruleName;
            }
        }

        return $failed;
    }

    /**
     * Get variance data for reporting.
     *
     * @return array{
     *     priceVariancePercent: float,
     *     quantityVariancePercent: float,
     *     amountVariancePercent: float,
     *     withinPriceTolerance: bool,
     *     withinQuantityTolerance: bool,
     *     withinAmountTolerance: bool,
     *     overallMatch: bool
     * }
     */
    public function getVarianceReport(ThreeWayMatchContext $context): array
    {
        $priceResult = $this->priceMatchRule->check($context);
        $quantityResult = $this->quantityMatchRule->check($context);
        $toleranceResult = $this->toleranceThresholdRule->check($context);

        return [
            'priceVariancePercent' => $context->calculatePriceVariancePercent(),
            'quantityVariancePercent' => $context->calculateQuantityVariancePercent(),
            'amountVariancePercent' => $this->calculateAmountVariance($context),
            'withinPriceTolerance' => $priceResult->passed,
            'withinQuantityTolerance' => $quantityResult->passed,
            'withinAmountTolerance' => $toleranceResult->passed,
            'overallMatch' => $this->allPass($context),
        ];
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
}
