<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Typed context contract for finance rule evaluation.
 *
 * Rules read only the fields they need and ignore unrelated fields.
 */
interface RuleContextInterface
{
    public function getTenantId(): string;

    public function getBudgetId(): ?string;

    public function getAmount(): ?string;

    /**
     * Get the primary/legacy cost center ID (if single cost center context).
     *
     * @return string|null The primary cost center ID or null if not applicable
     */
    public function getCostCenterId(): ?string;

    /**
     * Get all applicable cost center IDs for validation.
     *
     * Note: If getCostCenterId() returns a value, it should be included
     * in this array for consistency.
     *
     * @return array<string> All cost center IDs
     */
    public function getCostCenterIds(): array;

    public function getPeriodId(): ?string;

    public function getSubledgerType(): ?string;

    /**
     * @return array<string>
     */
    public function getTransactionTypes(): array;
}
