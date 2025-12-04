<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

use Nexus\Identity\Contracts\UserInterface;

/**
 * Policy builder helper
 * 
 * Provides fluent API for creating authorization policies.
 * 
 * Example:
 * ```php
 * $policy = Policy::define('hrm.leave.apply_on_behalf')
 *     ->description('User can apply leave on behalf of employees in same department')
 *     ->check(function(UserInterface $user, string $action, mixed $resource, array $context) {
 *         // Your authorization logic here
 *         return true;
 *     });
 * ```
 */
final class Policy
{
    private string $name;
    private string $description = '';
    
    /**
     * @var callable|null
     */
    private $evaluator = null;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Define a new policy
     */
    public static function define(string $name): self
    {
        return new self($name);
    }

    /**
     * Set policy description
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set policy evaluation logic
     * 
     * @param callable $evaluator Signature: fn(UserInterface $user, string $action, mixed $resource, array $context): bool
     */
    public function check(callable $evaluator): self
    {
        $this->evaluator = $evaluator;
        return $this;
    }

    /**
     * Get policy name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get policy description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the evaluator callable
     */
    public function getEvaluator(): callable
    {
        if ($this->evaluator === null) {
            throw new \LogicException("Policy '{$this->name}' has no evaluator defined. Call check() to set evaluation logic.");
        }

        return $this->evaluator;
    }

    /**
     * Convert to array for registration
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'evaluator' => $this->getEvaluator(),
        ];
    }
}
