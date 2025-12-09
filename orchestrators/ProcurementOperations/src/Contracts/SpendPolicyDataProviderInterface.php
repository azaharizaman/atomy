<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyRequest;
use Nexus\Setting\Contracts\SettingsManagerInterface;

/**
 * Data provider for spend policy evaluation.
 *
 * Aggregates data from multiple sources needed for policy rule evaluation.
 */
interface SpendPolicyDataProviderInterface
{
    /**
     * Build the evaluation context for a spend policy request.
     *
     * @param SpendPolicyRequest $request The policy evaluation request
     * @return SpendPolicyContext The aggregated context for rule evaluation
     */
    public function buildContext(SpendPolicyRequest $request): SpendPolicyContext;

    /**
     * Get year-to-date spend for a category.
     *
     * @param string $tenantId Tenant identifier
     * @param string $categoryId Category identifier
     * @param int $year Fiscal year
     * @return Money Total spend amount
     */
    public function getCategorySpendYtd(string $tenantId, string $categoryId, int $year): Money;

    /**
     * Get year-to-date spend with a vendor.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param int $year Fiscal year
     * @return Money Total spend amount
     */
    public function getVendorSpendYtd(string $tenantId, string $vendorId, int $year): Money;

    /**
     * Get year-to-date spend for a department.
     *
     * @param string $tenantId Tenant identifier
     * @param string $departmentId Department identifier
     * @param int $year Fiscal year
     * @return Money Total spend amount
     */
    public function getDepartmentSpendYtd(string $tenantId, string $departmentId, int $year): Money;

    /**
     * Get the spend limit for a category.
     *
     * @param string $tenantId Tenant identifier
     * @param string $categoryId Category identifier
     * @return Money|null Spend limit or null if not defined
     */
    public function getCategoryLimit(string $tenantId, string $categoryId): ?Money;

    /**
     * Get the spend limit for a vendor.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @return Money|null Spend limit or null if not defined
     */
    public function getVendorLimit(string $tenantId, string $vendorId): ?Money;

    /**
     * Get the budget for a department.
     *
     * @param string $tenantId Tenant identifier
     * @param string $departmentId Department identifier
     * @return Money|null Budget amount or null if not defined
     */
    public function getDepartmentBudget(string $tenantId, string $departmentId): ?Money;

    /**
     * Check if vendor is preferred for the category.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $categoryId Category identifier
     * @return bool True if vendor is preferred
     */
    public function isPreferredVendor(string $tenantId, string $vendorId, string $categoryId): bool;

    /**
     * Get active contract for the category/vendor combination.
     *
     * @param string $tenantId Tenant identifier
     * @param string $categoryId Category identifier
     * @param string|null $vendorId Vendor identifier
     * @return array{id: string, remaining: Money}|null Contract info or null
     */
    public function getActiveContract(string $tenantId, string $categoryId, ?string $vendorId): ?array;

    /**
     * Get policy settings for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return array<string, mixed> Policy settings
     */
    public function getPolicySettings(string $tenantId): array;
}
