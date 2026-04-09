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
    public string $tenantId;

    public string $periodId;

    public SubledgerType $subledgerType;

    public bool $autoAdjust;

    /**
     * @param SubledgerType|string $subledgerType Subledger type enum or legacy string value
     */
    public function __construct(
        string $tenantId,
        string $periodId,
        SubledgerType|string $subledgerType,
        bool $autoAdjust = false,
    ) {
        $this->tenantId = $tenantId;
        $this->periodId = $periodId;
        $this->subledgerType = is_string($subledgerType)
            ? SubledgerType::fromString($subledgerType)
            : $subledgerType;
        $this->autoAdjust = $autoAdjust;
    }
}
