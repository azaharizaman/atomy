<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

final readonly class ConditionGroup
{
    /**
     * @param list<Condition> $conditions
     */
    public function __construct(
        public string $mode,
        public array $conditions,
    ) {
        $m = strtolower(trim($this->mode));
        if (!in_array($m, ['all', 'any'], true)) {
            throw new \InvalidArgumentException('ConditionGroup mode must be "all" or "any".');
        }
        if ($this->conditions === []) {
            throw new \InvalidArgumentException('ConditionGroup must contain at least one condition.');
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function matches(array $context): bool
    {
        $mode = strtolower(trim($this->mode));
        if ($mode === 'all') {
            foreach ($this->conditions as $condition) {
                if (!$condition->matches($context)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($this->conditions as $condition) {
            if ($condition->matches($context)) {
                return true;
            }
        }

        return false;
    }
}
