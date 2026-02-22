<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Request DTO for bank reconciliation operations.
 *
 * Used to reconcile bank statements with internal records,
 * supporting automatic matching for Tier 2+ implementations.
 *
 * @since 1.0.0
 */
final readonly class BankReconciliationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $bankAccountId Bank account to reconcile
     * @param string $periodId Accounting period for reconciliation
     * @param bool $autoMatch Enable automatic transaction matching (Tier 2+)
     * @param array<string, mixed> $matchingRules Custom matching rules for auto-match
     */
    public function __construct(
        public string $tenantId,
        public string $bankAccountId,
        public string $periodId,
        public bool $autoMatch = true,
        public array $matchingRules = [],
    ) {}
}
