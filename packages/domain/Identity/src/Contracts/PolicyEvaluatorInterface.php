<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

/**
 * Policy evaluator interface
 * 
 * Handles attribute-based access control (ABAC) authorization
 */
interface PolicyEvaluatorInterface
{
    /**
     * Evaluate a policy for a user and resource
     * 
     * @param UserInterface $user User requesting access
     * @param string $action Action being performed (e.g., "edit", "delete")
     * @param mixed $resource Resource being accessed
     * @param array<string, mixed> $context Additional context data
     * @return bool True if policy allows access
     */
    public function evaluate(
        UserInterface $user,
        string $action,
        mixed $resource,
        array $context = []
    ): bool;

    /**
     * Register a custom policy
     * 
     * @param string $name Policy name
     * @param callable $policy Policy evaluation function
     */
    public function registerPolicy(string $name, callable $policy): void;

    /**
     * Check if a policy exists
     */
    public function hasPolicy(string $name): bool;

    /**
     * Get all registered policies
     * 
     * @return array<string, callable>
     */
    public function getPolicies(): array;
}
