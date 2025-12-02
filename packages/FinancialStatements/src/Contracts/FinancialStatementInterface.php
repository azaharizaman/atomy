<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Base contract for all financial statements.
 */
interface FinancialStatementInterface
{
    /**
     * Get the statement metadata.
     */
    public function getMetadata(): StatementMetadata;

    /**
     * Get all sections of the statement.
     *
     * @return array<string, mixed>
     */
    public function getSections(): array;

    /**
     * Validate the statement structure and balances.
     */
    public function isValid(): bool;
}
