<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\Contracts\HiringRuleInterface;

/**
 * Registry for hiring validation rules.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Centralized rule management
 * - Composable validation logic
 * - Readonly with constructor injection
 */
final readonly class HiringRuleRegistry
{
    /**
     * @param array<HiringRuleInterface> $rules
     */
    public function __construct(
        private array $rules
    ) {}

    /**
     * @return array<HiringRuleInterface>
     */
    public function all(): array
    {
        return $this->rules;
    }

    public function get(string $name): ?HiringRuleInterface
    {
        foreach ($this->rules as $rule) {
            if ($rule->getName() === $name) {
                return $rule;
            }
        }
        return null;
    }
}
