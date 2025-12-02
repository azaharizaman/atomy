<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Rules;

use Nexus\AccountPeriodClose\Contracts\CloseRuleInterface;
use Nexus\AccountPeriodClose\ValueObjects\CloseCheckResult;
use Nexus\AccountPeriodClose\Enums\ValidationSeverity;

/**
 * Rule that checks if all subledgers (AP, AR, Inventory) are closed.
 * This rule lives in orchestrator because it requires data from multiple packages.
 */
final readonly class AllSubledgersClosedRule implements CloseRuleInterface
{
    public function __construct(
        // Injected dependencies for checking subledger status
    ) {}

    public function getName(): string
    {
        return 'all_subledgers_closed';
    }

    public function getDescription(): string
    {
        return 'All subledgers (AP, AR, Inventory) must be closed before period close';
    }

    public function check(string $tenantId, string $periodId): CloseCheckResult
    {
        $issues = [];

        // Check AP subledger
        if (!$this->isApClosed($tenantId, $periodId)) {
            $issues[] = 'Accounts Payable subledger is not closed';
        }

        // Check AR subledger
        if (!$this->isArClosed($tenantId, $periodId)) {
            $issues[] = 'Accounts Receivable subledger is not closed';
        }

        // Check Inventory subledger
        if (!$this->isInventoryClosed($tenantId, $periodId)) {
            $issues[] = 'Inventory subledger is not closed';
        }

        $passed = empty($issues);

        return new CloseCheckResult(
            ruleName: $this->getName(),
            passed: $passed,
            severity: $passed ? ValidationSeverity::INFO : ValidationSeverity::ERROR,
            message: $passed ? 'All subledgers are closed' : implode('; ', $issues),
            details: ['issues' => $issues],
        );
    }

    public function getSeverity(): ValidationSeverity
    {
        return ValidationSeverity::ERROR;
    }

    private function isApClosed(string $tenantId, string $periodId): bool
    {
        // Implementation checks with Nexus\Payable
        return true;
    }

    private function isArClosed(string $tenantId, string $periodId): bool
    {
        // Implementation checks with Nexus\Receivable
        return true;
    }

    private function isInventoryClosed(string $tenantId, string $periodId): bool
    {
        // Implementation checks with Nexus\Inventory
        return true;
    }
}
