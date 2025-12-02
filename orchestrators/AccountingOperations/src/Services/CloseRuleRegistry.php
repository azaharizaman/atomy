<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Services;

use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;

/**
 * Registry for period close rules.
 */
final class CloseRuleRegistry
{
    /**
     * @var array<string, CloseRuleInterface>
     */
    private array $rules = [];

    /**
     * Register a close rule.
     */
    public function register(string $name, CloseRuleInterface $rule): void
    {
        $this->rules[$name] = $rule;
    }

    /**
     * Get a registered rule by name.
     */
    public function get(string $name): ?CloseRuleInterface
    {
        return $this->rules[$name] ?? null;
    }

    /**
     * Get all registered rules.
     *
     * @return array<string, CloseRuleInterface>
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * Check if a rule is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    /**
     * Remove a registered rule.
     */
    public function remove(string $name): void
    {
        unset($this->rules[$name]);
    }

    /**
     * Get rule names.
     *
     * @return array<string>
     */
    public function getNames(): array
    {
        return array_keys($this->rules);
    }
}
