<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

use Nexus\FinanceOperations\Contracts\RuleContextInterface;

final readonly class RuleContext implements RuleContextInterface
{
    private string $tenantId;

    /**
     * @param array<string> $costCenterIds
     * @param array<string> $transactionTypes
     */
    public function __construct(
        string $tenantId,
        private ?string $budgetId = null,
        private ?string $amount = null,
        private ?string $costCenterId = null,
        private array $costCenterIds = [],
        private ?string $periodId = null,
        private ?string $subledgerType = null,
        private array $transactionTypes = [],
    ) {
        $trimmedTenantId = trim($tenantId);
        if ($trimmedTenantId === '') {
            throw new \InvalidArgumentException('Tenant ID cannot be empty');
        }
        $this->tenantId = $trimmedTenantId;
    }

    public static function forBudgetAvailability(
        string $tenantId,
        string $budgetId,
        string $amount,
        ?string $costCenterId = null,
    ): self {
        return new self(
            tenantId: $tenantId,
            budgetId: $budgetId,
            amount: $amount,
            costCenterId: $costCenterId,
        );
    }

    /**
     * @param array<string> $costCenterIds
     */
    public static function forCostCenterValidation(string $tenantId, array $costCenterIds): self
    {
        return new self(
            tenantId: $tenantId,
            costCenterIds: $costCenterIds,
        );
    }

    public static function forPeriodValidation(string $tenantId, string $periodId): self
    {
        return new self(
            tenantId: $tenantId,
            periodId: $periodId,
        );
    }

    public static function forSubledgerClosure(
        string $tenantId,
        string $periodId,
        string $subledgerType,
    ): self {
        return new self(
            tenantId: $tenantId,
            periodId: $periodId,
            subledgerType: $subledgerType,
        );
    }

    /**
     * @param array<string> $transactionTypes
     */
    public static function forGlAccountMappingValidation(
        string $tenantId,
        string $subledgerType,
        array $transactionTypes,
    ): self {
        return new self(
            tenantId: $tenantId,
            subledgerType: $subledgerType,
            transactionTypes: $transactionTypes,
        );
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getBudgetId(): ?string
    {
        return $this->budgetId;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getCostCenterId(): ?string
    {
        return $this->costCenterId;
    }

    /**
     * @return array<string>
     */
    public function getCostCenterIds(): array
    {
        return $this->costCenterIds;
    }

    public function getPeriodId(): ?string
    {
        return $this->periodId;
    }

    public function getSubledgerType(): ?string
    {
        return $this->subledgerType;
    }

    /**
     * @return array<string>
     */
    public function getTransactionTypes(): array
    {
        return $this->transactionTypes;
    }
}
