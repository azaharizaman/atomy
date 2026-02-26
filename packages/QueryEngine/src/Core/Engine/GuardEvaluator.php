<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Core\Engine;

use Nexus\QueryEngine\Contracts\AnalyticsContextInterface;
use Nexus\QueryEngine\Exceptions\GuardConditionFailedException;

/**
 * Evaluates guard conditions before query execution
 */
final readonly class GuardEvaluator
{
    /**
     * Evaluate all guard conditions
     *
     * @param array<string, mixed> $guards
     * @param AnalyticsContextInterface $context
     * @return bool
     * @throws GuardConditionFailedException
     */
    public function evaluateAll(array $guards, AnalyticsContextInterface $context): bool
    {
        foreach ($guards as $guardName => $guardConfig) {
            if (!$this->evaluate($guardName, $guardConfig, $context)) {
                throw new GuardConditionFailedException(
                    (string) $guardName,
                    'Guard condition not met'
                );
            }
        }

        return true;
    }

    /**
     * Evaluate a single guard condition
     *
     * @param string $guardName
     * @param mixed $guardConfig
     * @param AnalyticsContextInterface $context
     * @return bool
     */
    private function evaluate(string $guardName, mixed $guardConfig, AnalyticsContextInterface $context): bool
    {
        // Implementation would vary based on guard types
        // Examples: role_required, tenant_match, time_window, etc.
        
        if (is_array($guardConfig)) {
            $type = $guardConfig['type'] ?? 'custom';

            return match ($type) {
                'role_required' => $this->evaluateRoleGuard($guardConfig, $context),
                'tenant_match' => $this->evaluateTenantGuard($guardConfig, $context),
                'time_window' => $this->evaluateTimeWindowGuard($guardConfig),
                default => true,
            };
        }

        return true;
    }

    /**
     * Evaluate role-based guard
     *
     * @param array<string, mixed> $config
     * @param AnalyticsContextInterface $context
     */
    private function evaluateRoleGuard(array $config, AnalyticsContextInterface $context): bool
    {
        $requiredRoles = $config['roles'] ?? [];
        $userRoles = $context->getUserRoles();

        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return empty($requiredRoles);
    }

    /**
     * Evaluate tenant isolation guard
     *
     * @param array<string, mixed> $config
     * @param AnalyticsContextInterface $context
     */
    private function evaluateTenantGuard(array $config, AnalyticsContextInterface $context): bool
    {
        $requiredTenant = $config['tenant_id'] ?? null;
        $currentTenant = $context->getTenantId();

        if ($requiredTenant === null) {
            return true;
        }

        return $requiredTenant === $currentTenant;
    }

    /**
     * Evaluate time window guard
     *
     * @param array<string, mixed> $config
     */
    private function evaluateTimeWindowGuard(array $config): bool
    {
        $startTime = $config['start'] ?? null;
        $endTime = $config['end'] ?? null;
        $now = new \DateTimeImmutable();

        if ($startTime && $now < new \DateTimeImmutable($startTime)) {
            return false;
        }

        if ($endTime && $now > new \DateTimeImmutable($endTime)) {
            return false;
        }

        return true;
    }
}
