<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Core\Engine;

use DateTimeImmutable;
use Nexus\QueryEngine\Contracts\AnalyticsContextInterface;
use Nexus\QueryEngine\Contracts\ClockInterface;
use Nexus\QueryEngine\Exceptions\GuardConditionFailedException;

/**
 * Evaluates guard conditions before query execution
 */
final readonly class GuardEvaluator
{
    public function __construct(
        private ClockInterface $clock
    ) {
    }

    public static function withDefaultClock(): self
    {
        return new self(new DefaultClock());
    }

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
            if (!$this->evaluate((string) $guardName, $guardConfig, $context)) {
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
        if (!is_array($guardConfig)) {
            return false;
        }

        $type = $guardConfig['type'] ?? $guardName;
        if (!is_string($type) || $type === '') {
            return false;
        }

        return match ($type) {
            'role_required' => $this->evaluateRoleGuard($guardConfig, $context),
            'tenant_match' => $this->evaluateTenantGuard($guardConfig, $context),
            'time_window' => $this->evaluateTimeWindowGuard($guardConfig),
            default => false,
        };
    }

    /**
     * Evaluate role-based guard
     *
     * @param array<string, mixed> $config
     * @param AnalyticsContextInterface $context
     */
    private function evaluateRoleGuard(array $config, AnalyticsContextInterface $context): bool
    {
        $requiredRoles = $config['roles'] ?? null;
        if (!is_array($requiredRoles) || $requiredRoles === []) {
            return false;
        }

        $validRoles = [];
        foreach ($requiredRoles as $role) {
            if (!is_string($role) || $role === '') {
                return false;
            }
            $validRoles[] = $role;
        }

        if ($validRoles === []) {
            return false;
        }

        $userRoles = $context->getUserRoles();

        foreach ($validRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
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
        if (!is_string($requiredTenant) || $requiredTenant === '') {
            return false;
        }

        $currentTenant = $context->getTenantId();
        if (!is_string($currentTenant) || $currentTenant === '') {
            return false;
        }

        return hash_equals($requiredTenant, $currentTenant);
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

        if ($startTime === null && $endTime === null) {
            return false;
        }

        if (($startTime !== null && (!is_string($startTime) || $startTime === ''))
            || ($endTime !== null && (!is_string($endTime) || $endTime === ''))
        ) {
            return false;
        }

        $startDateTime = $startTime !== null ? $this->parseGuardDateTime($startTime) : null;
        $endDateTime = $endTime !== null ? $this->parseGuardDateTime($endTime) : null;

        if (($startTime !== null && $startDateTime === null)
            || ($endTime !== null && $endDateTime === null)
        ) {
            return false;
        }

        if ($startDateTime !== null && $endDateTime !== null && $startDateTime > $endDateTime) {
            return false;
        }

        $now = $this->clock->now();

        if ($startDateTime !== null && $now < $startDateTime) {
            return false;
        }

        if ($endDateTime !== null && $now > $endDateTime) {
            return false;
        }

        return true;
    }

    private function parseGuardDateTime(string $value): ?DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}