<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\BudgetQueryInterface;
use Nexus\ProcurementOperations\Contracts\ContractQueryInterface;
use Nexus\ProcurementOperations\Contracts\SpendPolicyDataProviderInterface;
use Nexus\ProcurementOperations\Contracts\SpendQueryInterface;
use Nexus\ProcurementOperations\Contracts\VendorQueryInterface;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyContext;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyRequest;
use Nexus\Setting\Contracts\SettingsManagerInterface;

/**
 * Data provider for spend policy evaluation.
 *
 * Aggregates data from settings and other sources for policy rule evaluation.
 * This is a skeleton implementation - consumers must implement the query methods
 * by integrating with their procurement data stores.
 */
final readonly class SpendPolicyDataProvider implements SpendPolicyDataProviderInterface
{
    private const string POLICY_SETTINGS_KEY = 'procurement.spend_policies';
    private const string CATEGORY_LIMITS_KEY = 'procurement.spend_policies.category_limits';
    private const string VENDOR_LIMITS_KEY = 'procurement.spend_policies.vendor_limits';

    public function __construct(
        private SettingsManagerInterface $settings,
        private SpendQueryInterface $spendQuery,
        private VendorQueryInterface $vendorQuery,
        private ContractQueryInterface $contractQuery,
        private BudgetQueryInterface $budgetQuery,
    ) {}

    /**
     * @inheritDoc
     */
    public function buildContext(SpendPolicyRequest $request): SpendPolicyContext
    {
        $year = (int) $request->getTransactionDate()->format('Y');
        $currency = $request->amount->getCurrency();

        // Get spending data
        $categorySpendYtd = $this->getCategorySpendYtd($request->tenantId, $request->categoryId, $year);
        $vendorSpendYtd = $request->hasVendor()
            ? $this->getVendorSpendYtd($request->tenantId, $request->vendorId, $year)
            : Money::zero($currency);
        $departmentSpendYtd = $request->hasDepartment()
            ? $this->getDepartmentSpendYtd($request->tenantId, $request->departmentId, $year)
            : Money::zero($currency);

        // Get limits
        $categoryLimit = $this->getCategoryLimit($request->tenantId, $request->categoryId);
        $vendorLimit = $request->hasVendor()
            ? $this->getVendorLimit($request->tenantId, $request->vendorId)
            : null;
        $departmentBudget = $request->hasDepartment()
            ? $this->getDepartmentBudget($request->tenantId, $request->departmentId)
            : null;

        // Get vendor/contract info
        $vendorIsPreferred = $request->hasVendor()
            ? $this->isPreferredVendor($request->tenantId, $request->vendorId, $request->categoryId)
            : false;
        $contract = $this->getActiveContract($request->tenantId, $request->categoryId, $request->vendorId);
        $hasActiveContract = $contract !== null;
        $activeContractId = $contract['id'] ?? null;
        $contractRemaining = $contract['remaining'] ?? null;

        // Get budget availability
        $budgetAvailable = $departmentBudget === null
            || $departmentSpendYtd->add($request->amount)->isLessThanOrEqual($departmentBudget);

        // Get policy settings
        $policySettings = $this->getPolicySettings($request->tenantId);

        return new SpendPolicyContext(
            request: $request,
            categorySpendYtd: $categorySpendYtd,
            categoryLimit: $categoryLimit,
            vendorSpendYtd: $vendorSpendYtd,
            vendorLimit: $vendorLimit,
            departmentSpendYtd: $departmentSpendYtd,
            departmentBudget: $departmentBudget,
            vendorIsPreferred: $vendorIsPreferred,
            hasActiveContract: $hasActiveContract,
            activeContractId: $activeContractId,
            contractRemaining: $contractRemaining,
            budgetAvailable: $budgetAvailable,
            policySettings: $policySettings,
        );
    }

    /**
     * @inheritDoc
     */
    public function getCategorySpendYtd(string $tenantId, string $categoryId, int $year): Money
    {
        return $this->spendQuery->getCategorySpendYtd($tenantId, $categoryId, $year);
    }

    /**
     * @inheritDoc
     */
    public function getVendorSpendYtd(string $tenantId, string $vendorId, int $year): Money
    {
        return $this->spendQuery->getVendorSpendYtd($tenantId, $vendorId, $year);
    }

    /**
     * @inheritDoc
     */
    public function getDepartmentSpendYtd(string $tenantId, string $departmentId, int $year): Money
    {
        return $this->spendQuery->getDepartmentSpendYtd($tenantId, $departmentId, $year);
    }

    /**
     * @inheritDoc
     */
    public function getCategoryLimit(string $tenantId, string $categoryId): ?Money
    {
        $limits = $this->settings->get(self::CATEGORY_LIMITS_KEY, []);
        $limit = $limits[$categoryId] ?? null;

        if ($limit === null) {
            return null;
        }

        return new Money((int) $limit['amount_cents'], $limit['currency'] ?? 'USD');
    }

    /**
     * @inheritDoc
     */
    public function getVendorLimit(string $tenantId, string $vendorId): ?Money
    {
        $limits = $this->settings->get(self::VENDOR_LIMITS_KEY, []);
        $limit = $limits[$vendorId] ?? null;

        if ($limit === null) {
            return null;
        }

        return new Money((int) $limit['amount_cents'], $limit['currency'] ?? 'USD');
    }

    /**
     * @inheritDoc
     */
    public function getDepartmentBudget(string $tenantId, string $departmentId): ?Money
    {
        return $this->budgetQuery->getDepartmentBudget($tenantId, $departmentId);
    }

    /**
     * @inheritDoc
     */
    public function isPreferredVendor(string $tenantId, string $vendorId, string $categoryId): bool
    {
        return $this->vendorQuery->isPreferredForCategory($tenantId, $vendorId, $categoryId);
    }

    /**
     * @inheritDoc
     */
    public function getActiveContract(string $tenantId, string $categoryId, ?string $vendorId): ?array
    {
        return $this->contractQuery->getActiveContract($tenantId, $categoryId, $vendorId);
    }

    /**
     * @inheritDoc
     */
    public function getPolicySettings(string $tenantId): array
    {
        return $this->settings->get(self::POLICY_SETTINGS_KEY, [
            'enabled_policies' => [
                'category_limit',
                'vendor_limit',
                'preferred_vendor',
                'maverick_spend',
            ],
            'maverick_spend_threshold_percent' => 10,
            'auto_override_for_executives' => false,
        ]);
    }
}
