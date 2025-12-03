<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\HumanResourceOperations\Contracts\LeaveRuleInterface;

/**
 * Registry for leave validation rules.
 */
final class LeaveRuleRegistry
{
    /**
     * @var array<LeaveRuleInterface>
     */
    private array $rules = [];

    public function register(LeaveRuleInterface $rule): void
    {
        $this->rules[$rule->getName()] = $rule;
    }

    /**
     * @return array<LeaveRuleInterface>
     */
    public function all(): array
    {
        return array_values($this->rules);
    }

    public function get(string $name): ?LeaveRuleInterface
    {
        return $this->rules[$name] ?? null;
    }
}
