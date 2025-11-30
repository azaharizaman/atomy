<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for condition expression evaluators.
 *
 * Evaluates conditional expressions for transitions and gateways.
 */
interface ConditionEvaluatorInterface
{
    /**
     * Evaluate a condition expression against workflow context.
     *
     * @param string $expression The condition expression to evaluate
     * @param array<string, mixed> $context Workflow data and variables
     * @return bool True if condition is met
     * @throws \Nexus\Workflow\Exceptions\InvalidConditionExpressionException
     */
    public function evaluate(string $expression, array $context): bool;

    /**
     * Validate expression syntax.
     *
     * @throws \Nexus\Workflow\Exceptions\InvalidConditionExpressionException
     */
    public function validate(string $expression): void;

    /**
     * Get supported operators.
     *
     * @return string[] List of supported operators (==, !=, >, <, AND, OR, etc.)
     */
    public function getSupportedOperators(): array;
}
