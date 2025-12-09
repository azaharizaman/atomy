<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyRequest;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyResult;

/**
 * Contract for the Spend Policy Engine.
 *
 * Evaluates procurement transactions against configured spend policies.
 */
interface SpendPolicyEngineInterface
{
    /**
     * Evaluate a procurement transaction against all applicable policies.
     *
     * @param SpendPolicyRequest $request The transaction to evaluate
     * @return SpendPolicyResult The evaluation result
     */
    public function evaluate(SpendPolicyRequest $request): SpendPolicyResult;

    /**
     * Check if a specific policy type is enabled for the tenant.
     *
     * @param string $tenantId Tenant identifier
     * @param string $policyType Policy type to check
     * @return bool True if the policy is enabled
     */
    public function isPolicyEnabled(string $tenantId, string $policyType): bool;

    /**
     * Get all enabled policies for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return array<string> List of enabled policy types
     */
    public function getEnabledPolicies(string $tenantId): array;
}
