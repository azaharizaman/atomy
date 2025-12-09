<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SpendPolicy;

use Nexus\Common\ValueObjects\Money;

/**
 * Context DTO for spend policy evaluation.
 *
 * Aggregates all data needed for policy rule evaluation.
 */
final readonly class SpendPolicyContext
{
    /**
     * @param SpendPolicyRequest $request Original request
     * @param Money $categorySpendYtd Year-to-date spend in the category
     * @param Money|null $categoryLimit Category spend limit (if defined)
     * @param Money $vendorSpendYtd Year-to-date spend with vendor
     * @param Money|null $vendorLimit Vendor spend limit (if defined)
     * @param Money $departmentSpendYtd Year-to-date spend for department
     * @param Money|null $departmentBudget Department budget (if defined)
     * @param bool $vendorIsPreferred Whether vendor is preferred for category
     * @param bool $hasActiveContract Whether there's an active contract for the category
     * @param string|null $activeContractId Active contract ID (if any)
     * @param Money|null $contractRemaining Contract remaining value
     * @param bool $budgetAvailable Whether budget is available
     * @param array<string, mixed> $policySettings Tenant-specific policy settings
     */
    public function __construct(
        public SpendPolicyRequest $request,
        public Money $categorySpendYtd,
        public ?Money $categoryLimit,
        public Money $vendorSpendYtd,
        public ?Money $vendorLimit,
        public Money $departmentSpendYtd,
        public ?Money $departmentBudget,
        public bool $vendorIsPreferred = false,
        public bool $hasActiveContract = false,
        public ?string $activeContractId = null,
        public ?Money $contractRemaining = null,
        public bool $budgetAvailable = true,
        public array $policySettings = [],
    ) {}

    /**
     * Get the projected category spend after this transaction.
     */
    public function getProjectedCategorySpend(): Money
    {
        return $this->categorySpendYtd->add($this->request->amount);
    }

    /**
     * Get the projected vendor spend after this transaction.
     */
    public function getProjectedVendorSpend(): Money
    {
        return $this->vendorSpendYtd->add($this->request->amount);
    }

    /**
     * Get the projected department spend after this transaction.
     */
    public function getProjectedDepartmentSpend(): Money
    {
        return $this->departmentSpendYtd->add($this->request->amount);
    }

    /**
     * Check if category limit would be exceeded.
     */
    public function wouldExceedCategoryLimit(): bool
    {
        if ($this->categoryLimit === null) {
            return false;
        }

        return $this->getProjectedCategorySpend()->isGreaterThan($this->categoryLimit);
    }

    /**
     * Check if vendor limit would be exceeded.
     */
    public function wouldExceedVendorLimit(): bool
    {
        if ($this->vendorLimit === null) {
            return false;
        }

        return $this->getProjectedVendorSpend()->isGreaterThan($this->vendorLimit);
    }

    /**
     * Check if department budget would be exceeded.
     */
    public function wouldExceedDepartmentBudget(): bool
    {
        if ($this->departmentBudget === null) {
            return false;
        }

        return $this->getProjectedDepartmentSpend()->isGreaterThan($this->departmentBudget);
    }

    /**
     * Check if contract spend would be exceeded.
     */
    public function wouldExceedContractValue(): bool
    {
        if ($this->contractRemaining === null) {
            return false;
        }

        return $this->request->amount->isGreaterThan($this->contractRemaining);
    }

    /**
     * Get a policy setting value.
     */
    public function getPolicySetting(string $key, mixed $default = null): mixed
    {
        return $this->policySettings[$key] ?? $default;
    }

    /**
     * Check if a specific policy is enabled.
     */
    public function isPolicyEnabled(string $policyType): bool
    {
        $enabled = $this->policySettings['enabled_policies'] ?? [];
        return in_array($policyType, $enabled, true);
    }
}
