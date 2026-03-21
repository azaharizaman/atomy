<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Domain;

final readonly class ConditionGroup
{
    public string $mode;

    /**
     * @param list<Condition> $conditions
     */
    public function __construct(
        string $mode,
        public array $conditions,
    ) {
        $this->mode = strtolower(trim($mode));
        if (!in_array($this->mode, ['all', 'any'], true)) {
            throw new \InvalidArgumentException('ConditionGroup mode must be "all" or "any".');
        }
        if ($this->conditions === []) {
            throw new \InvalidArgumentException('ConditionGroup must contain at least one condition.');
        }
        foreach ($this->conditions as $index => $condition) {
            if (!$condition instanceof Condition) {
                throw new \InvalidArgumentException(sprintf(
                    'ConditionGroup conditions[%d] must be an instance of %s.',
                    $index,
                    Condition::class
                ));
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function matches(array $context): bool
    {
        if ($this->mode === 'all') {
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
