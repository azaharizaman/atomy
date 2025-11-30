<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

/**
 * Custom evaluator interface for CUSTOM strategy flags.
 *
 * Implementations MUST be stateless. All required data should be passed
 * via the EvaluationContext's customAttributes array.
 *
 * Phase 1: Evaluators are instantiated via ReflectionClass without dependencies.
 * Phase 2: Will support dependency injection via container.
 *
 * @example
 * class PremiumMalaysianUsersEvaluator implements CustomEvaluatorInterface
 * {
 *     public function evaluate(EvaluationContext $context): bool
 *     {
 *         $plan = $context->customAttributes['plan'] ?? null;
 *         $country = $context->customAttributes['country'] ?? null;
 *         
 *         return $plan === 'premium' && $country === 'MY';
 *     }
 * }
 */
interface CustomEvaluatorInterface
{
    /**
     * Evaluate whether the flag should be enabled for the given context.
     *
     * This method MUST be stateless. All required data should be passed
     * via $context->customAttributes.
     *
     * @param EvaluationContext $context The evaluation context with user/tenant/custom data
     * @return bool True if the flag should be enabled, false otherwise
     * @throws \Exception If evaluation fails (will be caught and logged)
     */
    public function evaluate(EvaluationContext $context): bool;
}
