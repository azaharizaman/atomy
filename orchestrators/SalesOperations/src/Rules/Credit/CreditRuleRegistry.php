<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules\Credit;

use Nexus\SalesOperations\DTOs\CreditCheckRequest;
use Nexus\SalesOperations\Rules\RuleInterface;
use Nexus\SalesOperations\Rules\RuleRegistryInterface;
use Nexus\SalesOperations\Rules\RuleResult;

final readonly class CreditRuleRegistry implements RuleRegistryInterface
{
    /**
     * @param array<string, RuleInterface> $rules
     */
    public function __construct(
        private array $rules = []
    ) {}

    public function validate(CreditCheckRequest $context): CreditValidationResult
    {
        $results = [];
        $allPassed = true;

        foreach ($this->rules as $rule) {
            $result = $rule->check($context);
            $results[$rule->getName()] = $result;

            if ($result->failed()) {
                $allPassed = false;
            }
        }

        return new CreditValidationResult(
            passed: $allPassed,
            results: $results
        );
    }

    public function register(RuleInterface $rule): void
    {
        // Note: Since this class is readonly, registration creates a new instance
        // Use addRule() for immutable registration that returns a new instance
    }

    public function get(string $name): ?RuleInterface
    {
        return $this->rules[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    public function all(): array
    {
        return $this->rules;
    }

    public function addRule(RuleInterface $rule): self
    {
        $rules = $this->rules;
        $rules[$rule->getName()] = $rule;

        return new self($rules);
    }
}
