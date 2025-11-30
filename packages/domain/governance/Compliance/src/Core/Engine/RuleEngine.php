<?php

declare(strict_types=1);

namespace Nexus\Compliance\Core\Engine;

use Nexus\Compliance\Core\Contracts\RuleEngineInterface;
use Psr\Log\LoggerInterface;

/**
 * Core rule evaluation engine for compliance rules.
 */
final class RuleEngine implements RuleEngineInterface
{
    /**
     * @var array<string, callable> Custom evaluators by rule type
     */
    private array $evaluators = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
        $this->registerDefaultEvaluators();
    }

    public function evaluate(array $rule, array $context): bool
    {
        $ruleType = $rule['type'] ?? 'unknown';

        $this->logger->debug("Evaluating compliance rule", [
            'rule_type' => $ruleType,
            'rule_name' => $rule['name'] ?? 'unnamed',
        ]);

        if (!isset($this->evaluators[$ruleType])) {
            $this->logger->warning("Unknown rule type", ['rule_type' => $ruleType]);
            return false;
        }

        $evaluator = $this->evaluators[$ruleType];
        $result = $evaluator($rule, $context);

        $this->logger->debug("Rule evaluation result", [
            'rule_type' => $ruleType,
            'result' => $result ? 'pass' : 'fail',
        ]);

        return $result;
    }

    public function evaluateMultiple(array $rules, array $context): array
    {
        $failures = [];

        foreach ($rules as $rule) {
            if (!$this->evaluate($rule, $context)) {
                $failures[] = $rule['name'] ?? 'unnamed_rule';
            }
        }

        return $failures;
    }

    public function registerEvaluator(string $ruleType, callable $evaluator): void
    {
        $this->logger->info("Registering custom evaluator", ['rule_type' => $ruleType]);
        $this->evaluators[$ruleType] = $evaluator;
    }

    /**
     * Register default rule evaluators.
     */
    private function registerDefaultEvaluators(): void
    {
        // Equality check
        $this->evaluators['equals'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            $expected = $rule['value'] ?? null;
            $actual = $context[$field] ?? null;
            return $actual === $expected;
        };

        // Not equals check
        $this->evaluators['not_equals'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            $expected = $rule['value'] ?? null;
            $actual = $context[$field] ?? null;
            return $actual !== $expected;
        };

        // Field exists check
        $this->evaluators['field_exists'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            return isset($context[$field]);
        };

        // Field not empty check
        $this->evaluators['not_empty'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            return !empty($context[$field]);
        };

        // Greater than check
        $this->evaluators['greater_than'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            $threshold = $rule['value'] ?? 0;
            $actual = $context[$field] ?? 0;
            return $actual > $threshold;
        };

        // Less than check
        $this->evaluators['less_than'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            $threshold = $rule['value'] ?? 0;
            $actual = $context[$field] ?? 0;
            return $actual < $threshold;
        };

        // In array check
        $this->evaluators['in_array'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            $allowedValues = $rule['values'] ?? [];
            $actual = $context[$field] ?? null;
            return in_array($actual, $allowedValues, true);
        };

        // Boolean check
        $this->evaluators['is_true'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            return ($context[$field] ?? false) === true;
        };

        $this->evaluators['is_false'] = function (array $rule, array $context): bool {
            $field = $rule['field'] ?? null;
            return ($context[$field] ?? true) === false;
        };
    }
}
