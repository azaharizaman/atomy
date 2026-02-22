<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

use Nexus\FinanceOperations\Enums\SubledgerType;

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
     * @param SubledgerType $subledgerType Subledger type enum
     * @param bool $autoAdjust Automatically create adjustment entries
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public SubledgerType $subledgerType,
        public bool $autoAdjust = false,
    ) {}
}
