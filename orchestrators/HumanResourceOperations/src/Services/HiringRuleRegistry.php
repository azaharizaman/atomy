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
 */
final class HiringRuleRegistry
{
    /**
     * @var array<HiringRuleInterface>
     */
    private array $rules = [];

    public function register(HiringRuleInterface $rule): void
    {
        $this->rules[$rule->getName()] = $rule;
    }

    /**
     * @return array<HiringRuleInterface>
     */
    public function all(): array
    {
        return array_values($this->rules);
    }

    public function get(string $name): ?HiringRuleInterface
    {
        return $this->rules[$name] ?? null;
    }
}
