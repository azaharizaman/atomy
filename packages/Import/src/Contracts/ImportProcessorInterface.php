<?php

declare(strict_types=1);

namespace Nexus\Import\Contracts;

use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ImportMode;
use Nexus\Import\ValueObjects\ImportStrategy;
use Nexus\Import\ValueObjects\ImportResult;

/**
 * Import processor contract
 * 
 * Orchestrates the import process with strategy enforcement.
 */
interface ImportProcessorInterface
{
    /**
     * Process import with specified strategy
     * 
     * The processor enforces the transaction strategy:
     * - TRANSACTIONAL: Single transaction for entire import
     * - BATCH: Multiple transactions (one per batch)
     * - STREAM: No transaction wrapping (individual commits)
     * 
     * @param ImportDefinition $definition Validated import definition
     * @param ImportHandlerInterface $handler Domain-specific handler
     * @param array<\Nexus\Import\ValueObjects\FieldMapping> $mappings Field mappings
     * @param ImportMode $mode Import mode (CREATE, UPDATE, UPSERT, etc.)
     * @param ImportStrategy $strategy Execution strategy
     * @param TransactionManagerInterface|null $transactionManager Transaction manager (null for STREAM)
     * @param array<\Nexus\Import\ValueObjects\ValidationRule> $validationRules Validation rules
     * @return ImportResult Execution result with row counts and errors
     */
    public function process(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ImportStrategy $strategy,
        ?TransactionManagerInterface $transactionManager = null,
        array $validationRules = []
    ): ImportResult;
}
