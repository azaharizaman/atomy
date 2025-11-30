<?php

declare(strict_types=1);

namespace Nexus\Compliance\Core\Contracts;

/**
 * Interface for the compliance rule engine.
 * 
 * The rule engine evaluates compliance rules against transactions and configurations.
 */
interface RuleEngineInterface
{
    /**
     * Evaluate a rule against provided context.
     *
     * @param array<string, mixed> $rule The rule definition
     * @param array<string, mixed> $context The evaluation context
     * @return bool True if the rule passes
     */
    public function evaluate(array $rule, array $context): bool;

    /**
     * Evaluate multiple rules against provided context.
     *
     * @param array<array<string, mixed>> $rules Array of rule definitions
     * @param array<string, mixed> $context The evaluation context
     * @return array<string> Array of failed rule names (empty if all pass)
     */
    public function evaluateMultiple(array $rules, array $context): array;

    /**
     * Register a custom rule evaluator.
     *
     * @param string $ruleType The rule type identifier
     * @param callable $evaluator The evaluator function
     * @return void
     */
    public function registerEvaluator(string $ruleType, callable $evaluator): void;
}
