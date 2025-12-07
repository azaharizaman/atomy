<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules\GoodsReceipt;

use Nexus\ProcurementOperations\Rules\RuleInterface;
use Nexus\ProcurementOperations\Rules\RuleResult;
use Nexus\ProcurementOperations\Exceptions\GoodsReceiptException;

/**
 * Registry for goods receipt validation rules.
 *
 * Composes multiple rules and executes them in sequence.
 * Implements the Rule Engine pattern from Advanced Orchestrator Pattern v1.1.
 *
 * Usage:
 * - Coordinators inject this registry instead of individual rules
 * - Validate with all rules, or stop on first failure
 * - Collect all validation results for detailed error reporting
 */
final readonly class GoodsReceiptRuleRegistry
{
    /**
     * @param QuantityToleranceRule $quantityTolerance
     * @param QualityCheckPassedRule $qualityCheck
     * @param ExpiryDateValidRule $expiryDate
     * @param array<RuleInterface> $additionalRules Optional custom rules
     */
    public function __construct(
        private QuantityToleranceRule $quantityTolerance,
        private QualityCheckPassedRule $qualityCheck,
        private ExpiryDateValidRule $expiryDate,
        private array $additionalRules = [],
    ) {}

    /**
     * Validate context against all rules.
     *
     * @param object $context The context to validate
     * @param bool $stopOnFirstFailure If true, stops validation after first failure
     * @return array<RuleResult> All validation results
     */
    public function validate(object $context, bool $stopOnFirstFailure = false): array
    {
        $results = [];

        foreach ($this->getRules() as $rule) {
            $result = $rule->check($context);
            $results[] = $result;

            if ($stopOnFirstFailure && $result->failed()) {
                break;
            }
        }

        return $results;
    }

    /**
     * Validate and throw exception if any rule fails.
     *
     * @param object $context The context to validate
     * @throws GoodsReceiptException If any validation rule fails
     */
    public function validateOrFail(object $context): void
    {
        $results = $this->validate($context);
        $failures = array_filter($results, fn(RuleResult $r) => $r->failed());

        if (count($failures) > 0) {
            $errors = [];
            foreach ($failures as $failure) {
                $errors[$failure->ruleName] = $failure->getMessage() ?? 'Validation failed';
            }

            throw GoodsReceiptException::validationFailed($errors);
        }
    }

    /**
     * Check if all rules pass.
     *
     * @param object $context The context to validate
     * @return bool True if all rules pass
     */
    public function allPass(object $context): bool
    {
        foreach ($this->getRules() as $rule) {
            if ($rule->check($context)->failed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all registered rules.
     *
     * @return array<RuleInterface>
     */
    public function getRules(): array
    {
        return array_merge(
            [
                $this->quantityTolerance,
                $this->qualityCheck,
                $this->expiryDate,
            ],
            $this->additionalRules
        );
    }

    /**
     * Get failed rules only.
     *
     * @param object $context The context to validate
     * @return array<RuleResult> Only the failed results
     */
    public function getFailures(object $context): array
    {
        $results = $this->validate($context);
        return array_filter($results, fn(RuleResult $r) => $r->failed());
    }

    /**
     * Get a summary of validation results.
     *
     * @param object $context The context to validate
     * @return array{
     *     passed: int,
     *     failed: int,
     *     total: int,
     *     failureMessages: array<string>
     * }
     */
    public function getSummary(object $context): array
    {
        $results = $this->validate($context);
        $failures = array_filter($results, fn(RuleResult $r) => $r->failed());

        return [
            'passed' => count($results) - count($failures),
            'failed' => count($failures),
            'total' => count($results),
            'failureMessages' => array_map(
                fn(RuleResult $r) => $r->getMessage() ?? 'Unknown failure',
                $failures
            ),
        ];
    }
}
