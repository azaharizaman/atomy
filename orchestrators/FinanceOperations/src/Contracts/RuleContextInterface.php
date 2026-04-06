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

    public function getCostCenterId(): ?string;

    /**
     * @return array<string>
     */
    public function getCostCenterIds(): array;

    public function getPeriodId(): ?string;

    public function getSubledgerType(): ?string;

    /**
     * @return array<string>
     */
    public function getTransactionTypes(): array;
}
