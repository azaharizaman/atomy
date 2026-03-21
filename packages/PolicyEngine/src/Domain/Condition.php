<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

use Nexus\PolicyEngine\Enums\ConditionOperator;

final readonly class Condition
{
    public function __construct(
        public string $field,
        public ConditionOperator $operator,
        public mixed $value = null,
    ) {
        if (trim($this->field) === '') {
            throw new \InvalidArgumentException('Condition field cannot be empty.');
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function matches(array $context): bool
    {
        $exists = array_key_exists($this->field, $context);
        $actual = $exists ? $context[$this->field] : null;

        return match ($this->operator) {
            ConditionOperator::Exists => $exists,
            ConditionOperator::Equals => $actual === $this->value,
            ConditionOperator::NotEquals => $actual !== $this->value,
            ConditionOperator::In => is_array($this->value) && in_array($actual, $this->value, true),
            ConditionOperator::NotIn => is_array($this->value) && !in_array($actual, $this->value, true),
            ConditionOperator::Contains => is_array($actual) && in_array($this->value, $actual, true),
            ConditionOperator::GreaterThan => $this->asFloat($actual) > $this->asFloat($this->value),
            ConditionOperator::GreaterThanOrEquals => $this->asFloat($actual) >= $this->asFloat($this->value),
            ConditionOperator::LessThan => $this->asFloat($actual) < $this->asFloat($this->value),
            ConditionOperator::LessThanOrEquals => $this->asFloat($actual) <= $this->asFloat($this->value),
            ConditionOperator::Between => $this->isBetween($actual, $this->value),
        };
    }

    private function asFloat(mixed $value): float
    {
        if (!is_int($value) && !is_float($value)) {
            return NAN;
        }

        return (float) $value;
    }

    private function isBetween(mixed $actual, mixed $range): bool
    {
        if (!is_array($range) || count($range) !== 2) {
            return false;
        }
        $min = $this->asFloat($range[0]);
        $max = $this->asFloat($range[1]);
        $val = $this->asFloat($actual);
        if (is_nan($min) || is_nan($max) || is_nan($val)) {
            return false;
        }

        return $val >= $min && $val <= $max;
    }
}
