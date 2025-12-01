<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Services;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\Contracts\StatementValidatorInterface;
use Nexus\FinancialStatements\Entities\BalanceSheet;
use Nexus\FinancialStatements\Exceptions\StatementImbalanceException;
use Nexus\FinancialStatements\ValueObjects\LineItem;

/**
 * Pure validation logic for financial statements.
 */
final readonly class StatementValidator implements StatementValidatorInterface
{
    public function validate(FinancialStatementInterface $statement): array
    {
        $errors = [];

        if ($statement instanceof BalanceSheet) {
            if (!$statement->isValid()) {
                $errors['balance'] = 'Balance sheet does not balance (Assets ≠ Liabilities + Equity)';
            }
        }

        foreach ($statement->getSections() as $sectionName => $section) {
            if (is_array($section)) {
                foreach ($section as $index => $item) {
                    if (is_array($item)) {
                        $lineItem = new LineItem(
                            $item['account_code'] ?? '',
                            $item['account_name'] ?? '',
                            (float) ($item['amount'] ?? 0),
                            $item['description'] ?? null
                        );
                        if (!$this->validateLineItem($lineItem)) {
                            $errors["{$sectionName}.{$index}"] = 'Invalid line item';
                        }
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function validateLineItem(LineItem $lineItem): bool
    {
        if (empty($lineItem->getAccountCode())) {
            return false;
        }

        if (empty($lineItem->getAccountName())) {
            return false;
        }

        return true;
    }
}
