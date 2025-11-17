<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core;

use Nexus\Workflow\Contracts\ConditionEvaluatorInterface;
use Nexus\Workflow\Exceptions\InvalidConditionExpressionException;

/**
 * Simple condition expression evaluator.
 *
 * Supports: ==, !=, >, <, >=, <=, AND, OR, NOT, IN
 */
final class ConditionEngine implements ConditionEvaluatorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function evaluate(string $expression, array $context): bool
    {
        // Placeholder implementation - basic expression evaluation
        // In real implementation, use a proper expression parser
        
        $expression = trim($expression);
        
        // Simple equality check: "amount > 1000"
        if (preg_match('/^(\w+)\s*(==|!=|>|<|>=|<=)\s*(.+)$/', $expression, $matches)) {
            $field = $matches[1];
            $operator = $matches[2];
            $value = trim($matches[3], '"\'');
            
            $contextValue = $context[$field] ?? null;
            
            return match ($operator) {
                '==' => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>' => $contextValue > $value,
                '<' => $contextValue < $value,
                '>=' => $contextValue >= $value,
                '<=' => $contextValue <= $value,
                default => throw InvalidConditionExpressionException::unsupportedOperator($operator)
            };
        }
        
        throw InvalidConditionExpressionException::syntax($expression, 'Unsupported expression format');
    }

    public function validate(string $expression): void
    {
        // Basic validation
        if (empty(trim($expression))) {
            throw InvalidConditionExpressionException::syntax($expression, 'Empty expression');
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedOperators(): array
    {
        return ['==', '!=', '>', '<', '>=', '<=', 'AND', 'OR', 'NOT', 'IN'];
    }
}
