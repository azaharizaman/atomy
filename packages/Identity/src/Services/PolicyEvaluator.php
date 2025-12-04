<?php

declare(strict_types=1);

namespace Nexus\Identity\Services;

use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Policy evaluator service
 * 
 * Handles attribute-based access control (ABAC) by evaluating custom
 * policies that can check context, relationships, and complex business rules.
 * 
 * Policies are callables with signature:
 * fn(UserInterface $user, string $action, mixed $resource, array $context): bool
 */
final class PolicyEvaluator implements PolicyEvaluatorInterface
{
    /**
     * @var array<string, callable>
     */
    private array $policies = [];

    public function __construct(
        private readonly PermissionCheckerInterface $permissionChecker,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {}

    public function evaluate(
        UserInterface $user,
        string $action,
        mixed $resource,
        array $context = []
    ): bool {
        // Build policy key from action
        $policyKey = $this->buildPolicyKey($action, $context);

        // Log evaluation attempt
        $this->logger->debug('Evaluating policy', [
            'user_id' => $user->getId(),
            'action' => $action,
            'policy_key' => $policyKey,
            'has_policy' => $this->hasPolicy($policyKey),
        ]);

        // If no custom policy registered, fall back to permission check
        if (!$this->hasPolicy($policyKey)) {
            $this->logger->debug('No custom policy found, checking basic permission', [
                'permission' => $action,
            ]);

            return $this->permissionChecker->hasPermission($user, $action);
        }

        // Execute custom policy
        $policy = $this->policies[$policyKey];
        
        try {
            $result = $policy($user, $action, $resource, $context);

            $this->logger->info('Policy evaluated', [
                'user_id' => $user->getId(),
                'action' => $action,
                'policy_key' => $policyKey,
                'result' => $result ? 'ALLOWED' : 'DENIED',
            ]);

            return $result;

        } catch (\Throwable $e) {
            $this->logger->error('Policy evaluation failed', [
                'user_id' => $user->getId(),
                'action' => $action,
                'policy_key' => $policyKey,
                'error' => $e->getMessage(),
            ]);

            // Fail secure: deny access on policy error
            return false;
        }
    }

    public function registerPolicy(string $name, callable $policy): void
    {
        if ($this->hasPolicy($name)) {
            $this->logger->warning('Overwriting existing policy', [
                'policy_name' => $name,
            ]);
        }

        $this->policies[$name] = $policy;

        $this->logger->info('Policy registered', [
            'policy_name' => $name,
        ]);
    }

    public function hasPolicy(string $name): bool
    {
        return isset($this->policies[$name]);
    }

    public function getPolicies(): array
    {
        return $this->policies;
    }

    /**
     * Build policy key from action and context
     * 
     * Allows context-specific policies like "hrm.leave.apply_on_behalf"
     * or resource-specific like "invoice.delete"
     */
    private function buildPolicyKey(string $action, array $context): string
    {
        // For now, use action as-is
        // Can be extended to include resource type from context
        return $action;
    }
}
