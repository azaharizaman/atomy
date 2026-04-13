<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

use Nexus\FinanceOperations\Contracts\RuleContextInterface;
use Nexus\FinanceOperations\Exceptions\InvalidRuleContextException;

/**
 * Context for rule evaluation.
 *
 * This DTO captures all necessary state for evaluating rules,
 * ensuring consistency across different rule types.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Immutability for state consistency
 * - Specific factory methods for different contexts
 * - Explicit parameter validation and normalization
 *
 * @since 1.0.0
 */
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
            throw InvalidRuleContextException::invalidParameter('tenantId', 'Tenant ID cannot be empty');
        }
        $this->tenantId = $trimmedTenantId;
        
        // Ensure arrays are sequentially indexed
        $this->costCenterIds = array_values($costCenterIds);
        $this->transactionTypes = array_values($transactionTypes);
    }

    /**
     * Factory for budget availability validation context.
     *
     * @param string $tenantId Tenant identifier
     * @param string $budgetId Budget identifier
     * @param string $amount Requested amount
     * @param string|null $costCenterId Optional cost center filter
     */
    public static function forBudgetAvailability(
        string $tenantId,
        string $budgetId,
        string $amount,
        ?string $costCenterId = null,
    ): self {
        $trimmedBudgetId = trim($budgetId);
        $trimmedAmount = trim($amount);
        $trimmedCostCenterId = $costCenterId !== null ? trim($costCenterId) : null;
        
        if ($trimmedBudgetId === '') {
            throw InvalidRuleContextException::invalidParameter('budgetId', 'Budget ID cannot be empty');
        }
        if ($trimmedAmount === '') {
            throw InvalidRuleContextException::invalidParameter('amount', 'Amount cannot be empty');
        }
        
        // Blank-as-missing guideline: treat empty trimmed string as null
        if ($trimmedCostCenterId === '') {
            $trimmedCostCenterId = null;
        }

        return new self(
            tenantId: $tenantId,
            budgetId: $trimmedBudgetId,
            amount: $trimmedAmount,
            costCenterId: $trimmedCostCenterId,
        );
    }

    /**
     * Factory for multiple cost center validation context.
     *
     * @param string $tenantId Tenant identifier
     * @param array<string> $costCenterIds List of cost center IDs to validate
     */
    public static function forCostCenterValidation(string $tenantId, array $costCenterIds): self
    {
        // Normalize: trim each ID and filter out blanks
        $normalizedIds = array_filter(
            array_map(fn($id) => trim((string)$id), $costCenterIds),
            fn($id) => $id !== ''
        );

        return new self(
            tenantId: $tenantId,
            costCenterIds: array_values($normalizedIds),
        );
    }

    /**
     * Factory for period validation context.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     */
    public static function forPeriodValidation(string $tenantId, string $periodId): self
    {
        $trimmedPeriodId = trim($periodId);
        if ($trimmedPeriodId === '') {
            throw InvalidRuleContextException::invalidParameter('periodId', 'Period ID cannot be empty');
        }
        return new self(
            tenantId: $tenantId,
            periodId: $trimmedPeriodId,
        );
    }

    /**
     * Factory for subledger closure validation context.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @param string $subledgerType Subledger type
     */
    public static function forSubledgerClosure(
        string $tenantId,
        string $periodId,
        string $subledgerType,
    ): self {
        $trimmedPeriodId = trim($periodId);
        $trimmedSubledgerType = trim($subledgerType);
        
        if ($trimmedPeriodId === '') {
            throw InvalidRuleContextException::invalidParameter('periodId', 'Period ID cannot be empty');
        }
        if ($trimmedSubledgerType === '') {
            throw InvalidRuleContextException::invalidParameter('subledgerType', 'Subledger type cannot be empty');
        }
        
        return new self(
            tenantId: $tenantId,
            periodId: $trimmedPeriodId,
            subledgerType: $trimmedSubledgerType,
        );
    }

    /**
     * Factory for GL account mapping validation context.
     *
     * @param string $tenantId Tenant identifier
     * @param string $subledgerType Subledger type identifier
     * @param array<string> $transactionTypes List of transaction types
     */
    public static function forGlAccountMappingValidation(
        string $tenantId,
        string $subledgerType,
        array $transactionTypes,
    ): self {
        $trimmedSubledgerType = trim($subledgerType);
        if ($trimmedSubledgerType === '') {
            throw InvalidRuleContextException::invalidParameter('subledgerType', 'Subledger type cannot be empty');
        }

        // Normalize transaction types: trim and filter blanks
        $normalizedTypes = array_filter(
            array_map(fn($type) => trim((string)$type), $transactionTypes),
            fn($type) => $type !== ''
        );

        return new self(
            tenantId: $tenantId,
            subledgerType: $trimmedSubledgerType,
            transactionTypes: array_values($normalizedTypes),
        );
    }

    /**
     * @inheritDoc
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * @inheritDoc
     */
    public function getBudgetId(): ?string
    {
        return $this->budgetId;
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @inheritDoc
     */
    public function getCostCenterId(): ?string
    {
        return $this->costCenterId;
    }

    /**
     * @inheritDoc
     */
    public function getCostCenterIds(): array
    {
        return $this->costCenterIds;
    }

    /**
     * @inheritDoc
     */
    public function getPeriodId(): ?string
    {
        return $this->periodId;
    }

    /**
     * @inheritDoc
     */
    public function getSubledgerType(): ?string
    {
        return $this->subledgerType;
    }

    /**
     * @inheritDoc
     */
    public function getTransactionTypes(): array
    {
        return $this->transactionTypes;
    }
}
