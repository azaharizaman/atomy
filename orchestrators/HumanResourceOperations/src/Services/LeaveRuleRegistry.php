<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\Contracts\LeaveRuleInterface;

/**
 * Registry for leave validation rules.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Readonly with constructor injection
 * - Composable validation logic
 */
final readonly class LeaveRuleRegistry
{
    /**
     * @param array<LeaveRuleInterface> $rules
     */
    public function __construct(
        private array $rules
    ) {}

    /**
     * @return array<LeaveRuleInterface>
     */
    public function all(): array
    {
        return $this->rules;
    }

    public function get(string $name): ?LeaveRuleInterface
    {
        foreach ($this->rules as $rule) {
            if ($rule->getName() === $name) {
                return $rule;
            }
        }
        return null;
    }
}
