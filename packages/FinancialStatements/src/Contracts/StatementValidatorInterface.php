<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

use Nexus\FinancialStatements\ValueObjects\LineItem;

/**
 * Contract for validating financial statements.
 */
interface StatementValidatorInterface
{
    /**
     * Validate a financial statement.
     *
     * @param FinancialStatementInterface $statement The statement to validate
     * @return array<string, mixed> Validation result with errors if any
     */
    public function validate(FinancialStatementInterface $statement): array;

    /**
     * Validate a single line item.
     *
     * @param LineItem $lineItem The line item to validate
     * @return bool True if valid
     */
    public function validateLineItem(LineItem $lineItem): bool;
}
