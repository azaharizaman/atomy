<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\GLAccountMappingQueryInterface;
use Nexus\FinanceOperations\Contracts\GLAccountMappingRuleViewInterface;
use Nexus\FinanceOperations\Contracts\GLAccountQueryInterface;
use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\Contracts\RuleContextInterface;
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
final readonly class GLAccountMappingRule implements RuleInterface
{
    /**
     * @param GLAccountQueryInterface $chartOfAccountQuery Account query for account validation
     * @param GLAccountMappingQueryInterface $mappingRepository Mapping lookup for subledger transactions
     */
    public function __construct(
        private GLAccountQueryInterface $chartOfAccountQuery,
        private GLAccountMappingQueryInterface $mappingRepository,
    ) {}

    /**
     * @inheritDoc
     *
     * @param RuleContextInterface $context Context containing tenantId, subledgerType, and transactionTypes
     * @return RuleResult The rule check result
     */
    public function check(RuleContextInterface $context): RuleResult
    {
        $tenantId = $context->getTenantId();
        $subledgerType = trim((string) $context->getSubledgerType());
        $transactionTypes = $context->getTransactionTypes();

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
                $mapping->getGLAccountCode()
            );

            if ($account === null) {
                $violations[] = [
                    'type' => 'invalid_account',
                    'transaction_type' => $txType,
                    'account_code' => $mapping->getGLAccountCode(),
                    'message' => sprintf(
                        'Mapped GL account "%s" does not exist',
                        $mapping->getGLAccountCode()
                    ),
                ];
                continue;
            }

            if (!$account->isActive()) {
                $violations[] = [
                    'type' => 'inactive_account',
                    'transaction_type' => $txType,
                    'account_code' => $mapping->getGLAccountCode(),
                    'message' => sprintf(
                        'Mapped GL account "%s" is inactive',
                        $mapping->getGLAccountCode()
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
     * @param array<GLAccountMappingRuleViewInterface> $mappings List of mapping objects
     * @param string $txType Transaction type to find
     * @return GLAccountMappingRuleViewInterface|null The mapping or null if not found
     */
    private function findMapping(array $mappings, string $txType): ?GLAccountMappingRuleViewInterface
    {
        foreach ($mappings as $mapping) {
            if ($mapping->getTransactionType() === $txType) {
                return $mapping;
            }
        }
        return null;
    }
}