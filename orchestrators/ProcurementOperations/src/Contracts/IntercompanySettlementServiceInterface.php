<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\IntercompanySettlementData;
use Nexus\ProcurementOperations\DTOs\Financial\NetSettlementResult;

/**
 * Contract for intercompany settlement calculations.
 *
 * Handles netting, currency translation, and settlement processing
 * for transactions between related entities.
 */
interface IntercompanySettlementServiceInterface
{
    /**
     * Calculate net settlement between two entities.
     *
     * @param string $fromEntityId Source entity
     * @param string $toEntityId Target entity
     * @param array<IntercompanySettlementData> $receivables Amounts owed TO fromEntity BY toEntity
     * @param array<IntercompanySettlementData> $payables Amounts owed BY fromEntity TO toEntity
     * @return NetSettlementResult Netting result with direction
     */
    public function calculateNetSettlement(
        string $fromEntityId,
        string $toEntityId,
        array $receivables,
        array $payables,
    ): NetSettlementResult;

    /**
     * Translate amounts to common settlement currency.
     *
     * @param Money $amount Amount to translate
     * @param string $fromCurrency Source currency
     * @param string $toCurrency Target currency
     * @param \DateTimeImmutable $effectiveDate Rate effective date
     * @return Money Translated amount
     */
    public function translateCurrency(
        Money $amount,
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $effectiveDate,
    ): Money;

    /**
     * Get pending intercompany transactions for netting.
     *
     * @param string $entityId Entity to query
     * @return array<IntercompanySettlementData> Pending transactions
     */
    public function getPendingTransactions(string $entityId): array;

    /**
     * Record settlement in both entities' ledgers.
     *
     * @param NetSettlementResult $settlement Settlement to record
     * @param string $settlementReference Unique reference
     * @return string Settlement ID
     */
    public function recordSettlement(
        NetSettlementResult $settlement,
        string $settlementReference,
    ): string;

    /**
     * Generate elimination entries for consolidation.
     *
     * @param string $parentEntityId Parent/consolidating entity
     * @param string $periodId Consolidation period
     * @return array<array{debit: array, credit: array}> Elimination entries
     */
    public function generateEliminationEntries(
        string $parentEntityId,
        string $periodId,
    ): array;

    /**
     * Validate intercompany balance agreement.
     *
     * @param string $entityAId First entity
     * @param string $entityBId Second entity
     * @return array{balanced: bool, variance: Money, details: array}
     */
    public function validateBalanceAgreement(
        string $entityAId,
        string $entityBId,
    ): array;
}
