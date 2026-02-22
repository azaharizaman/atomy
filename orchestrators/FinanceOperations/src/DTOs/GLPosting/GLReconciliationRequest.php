<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Request DTO for general ledger reconciliation.
 *
 * Used to reconcile subledger balances with GL control
 * accounts for a specific period.
 *
 * @since 1.0.0
 */
final readonly class GLReconciliationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for reconciliation
     * @param string $subledgerType Subledger type: 'receivable', 'payable', 'asset'
     * @param bool $autoAdjust Automatically create adjustment entries
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $subledgerType,
        public bool $autoAdjust = false,
    ) {}
}
