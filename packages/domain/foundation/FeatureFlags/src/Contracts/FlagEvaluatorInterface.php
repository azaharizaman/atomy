<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

/**
 * Evaluator interface for determining flag state based on strategy.
 *
 * Implementations handle the core evaluation logic for all flag strategies:
 * - Override precedence (FORCE_OFF/FORCE_ON)
 * - Enabled state check
 * - Strategy-specific evaluation (SYSTEM_WIDE, PERCENTAGE_ROLLOUT, etc.)
 */
interface FlagEvaluatorInterface
{
    /**
     * Evaluate a single flag definition.
     *
     * Evaluation flow:
     * 1. Check override (FORCE_OFF → false, FORCE_ON → true)
     * 2. Check enabled state (if false → false)
     * 3. Apply strategy-specific logic
     *
     * @param FlagDefinitionInterface $flag The flag to evaluate
     * @param EvaluationContext $context The evaluation context with user/tenant data
     * @return bool True if the flag should be enabled, false otherwise
     * @throws \Nexus\FeatureFlags\Exceptions\InvalidContextException If required context data is missing
     * @throws \Nexus\FeatureFlags\Exceptions\CustomEvaluatorException If CUSTOM evaluator fails
     */
    public function evaluate(FlagDefinitionInterface $flag, EvaluationContext $context): bool;

    /**
     * Evaluate multiple flag definitions (bulk operation).
     *
     * More efficient than calling evaluate() multiple times as it can
     * reuse context data and optimize strategy-specific logic.
     *
     * @param array<FlagDefinitionInterface> $flags Array of flags to evaluate
     * @param EvaluationContext $context The evaluation context
     * @return array<string, bool> Map of flag name => evaluation result
     * @throws \Nexus\FeatureFlags\Exceptions\InvalidContextException If required context data is missing
     * @throws \Nexus\FeatureFlags\Exceptions\CustomEvaluatorException If CUSTOM evaluator fails
     */
    public function evaluateMany(array $flags, EvaluationContext $context): array;
}
