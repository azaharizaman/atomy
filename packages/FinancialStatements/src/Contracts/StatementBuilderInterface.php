<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

use Nexus\FinancialStatements\Enums\StatementType;

/**
 * Contract for building financial statements.
 *
 * Implementation requires external Finance GL data - typically implemented
 * by the orchestrator using Nexus\Finance package.
 */
interface StatementBuilderInterface
{
    /**
     * Build a financial statement of the specified type.
     *
     * @param StatementType $type The type of statement to build
     * @param array<string, mixed> $parameters Build parameters
     * @return FinancialStatementInterface
     */
    public function build(StatementType $type, array $parameters = []): FinancialStatementInterface;
}
