<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\GLAccountMappingRuleInterface;
use Nexus\FinanceOperations\Contracts\GLAccountQueryInterface;
use Nexus\FinanceOperations\Contracts\GLMappingRepositoryInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\GLAccountMappingRuleContext;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate GL account mappings for subledger posting.
 *
 * This rule ensures that proper GL account mappings exist for
 * converting subledger transactions to journal entries.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: GL account mapping validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 4 for rule patterns
 * @since 1.0.0
 */
final readonly class GLAccountMappingRule implements GLAccountMappingRuleInterface
{
    public function __construct(
        private GLAccountQueryInterface $chartOfAccountQuery,
        private GLMappingRepositoryInterface $mappingRepository,
    ) {}

    /**
     * @inheritDoc
     *
     * @return RuleResult The rule check result
     */
    public function check(GLAccountMappingRuleContext $context): RuleResult
    {
        $tenantId = trim($context->tenantId);
        $subledgerType = $context->subledgerType->value;
        $transactionTypes = $context->transactionTypes;

        if ($tenantId === '') {
            return RuleResult::failed(
                $this->getName(),
                'Tenant ID is required for GL account mapping validation',
                ['missing_field' => 'tenantId']
            );
        }

        if (empty($subledgerType)) {
            return RuleResult::failed(
                $this->getName(),
                'Subledger type is required for GL account mapping validation',
                ['missing_field' => 'subledgerType']
            );
        }

        if (empty($transactionTypes)) {
            return RuleResult::failed(
                $this->getName(),
                'Transaction types are required for GL account mapping validation',
                ['missing_field' => 'transactionTypes']
            );
        }

        $violations = [];
        $mappings = $this->mappingRepository->getMappingsForSubledger(
            $tenantId,
            $subledgerType
        );

        foreach ($transactionTypes as $txType) {
            $mapping = $this->findMapping($mappings, $txType);

            if ($mapping === null) {
                $violations[] = [
                    'type' => 'missing_mapping',
                    'transaction_type' => $txType,
                    'message' => sprintf(
                        'No GL account mapping found for transaction type "%s"',
                        $txType
                    ),
                ];
                continue;
            }

            // Validate that the mapped account exists and is active
            $account = $this->chartOfAccountQuery->find(
                $tenantId,
                $this->getGLAccountCode($mapping)
            );

            if ($account === null) {
                $violations[] = [
                    'type' => 'invalid_account',
                    'transaction_type' => $txType,
                    'account_code' => $this->getGLAccountCode($mapping),
                    'message' => sprintf(
                        'Mapped GL account "%s" does not exist',
                        $this->getGLAccountCode($mapping)
                    ),
                ];
                continue;
            }

            if (!$this->isAccountActive($account)) {
                $violations[] = [
                    'type' => 'inactive_account',
                    'transaction_type' => $txType,
                    'account_code' => $this->getGLAccountCode($mapping),
                    'message' => sprintf(
                        'Mapped GL account "%s" is inactive',
                        $this->getGLAccountCode($mapping)
                    ),
                ];
            }
        }

        if (!empty($violations)) {
            return RuleResult::failed(
                $this->getName(),
                sprintf(
                    'GL account mapping validation failed with %d violations',
                    count($violations)
                ),
                $violations
            );
        }

        return RuleResult::passed($this->getName());
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'gl_account_mapping';
    }

    /**
     * Find mapping for a transaction type.
     *
     * @param array<object> $mappings List of mapping objects
     * @param string $txType Transaction type to find
     * @return object|null The mapping or null if not found
     */
    private function findMapping(array $mappings, string $txType): ?object
    {
        foreach ($mappings as $mapping) {
            if ($this->getTransactionType($mapping) === $txType) {
                return $mapping;
            }
        }
        return null;
    }

    /**
     * Get transaction type from mapping.
     *
     * @param object $mapping The mapping object
     * @return string The transaction type
     */
    private function getTransactionType(object $mapping): string
    {
        if (method_exists($mapping, 'getTransactionType')) {
            return $mapping->getTransactionType();
        }

        if (property_exists($mapping, 'transactionType')) {
            return $mapping->transactionType ?? '';
        }

        if (property_exists($mapping, 'transaction_type')) {
            return $mapping->transaction_type ?? '';
        }

        return '';
    }

    /**
     * Get GL account code from mapping.
     *
     * @param object $mapping The mapping object
     * @return string The GL account code
     */
    private function getGLAccountCode(object $mapping): string
    {
        if (method_exists($mapping, 'getGLAccountCode')) {
            return $mapping->getGLAccountCode();
        }

        if (method_exists($mapping, 'getAccountCode')) {
            return $mapping->getAccountCode();
        }

        if (property_exists($mapping, 'glAccountCode')) {
            return $mapping->glAccountCode ?? '';
        }

        if (property_exists($mapping, 'account_code')) {
            return $mapping->account_code ?? '';
        }

        return '';
    }

    /**
     * Check if the account is active.
     *
     * @param object $account The account object
     * @return bool True if the account is active
     */
    private function isAccountActive(object $account): bool
    {
        if (method_exists($account, 'isActive')) {
            return $account->isActive();
        }

        if (method_exists($account, 'getIsActive')) {
            return $account->getIsActive();
        }

        if (property_exists($account, 'isActive')) {
            return $account->isActive;
        }

        if (property_exists($account, 'is_active')) {
            return $account->is_active;
        }

        // Default to true if we cannot determine status
        return true;
    }
}