<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Events;

use DateTimeInterface;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount;

/**
 * Asset Revalued Event
 *
 * Dispatched when an asset has been revalued (IFRS IAS 16).
 * Severity: HIGH
 *
 * @package Nexus\FixedAssetDepreciation\Events
 */
final readonly class AssetRevaluedEvent
{
    public function __construct(
        public string $revaluationId,
        public string $assetId,
        public string $tenantId,
        public RevaluationType $revaluationType,
        public RevaluationAmount $revaluationAmount,
        public float $previousCost,
        public float $newCost,
        public float $previousNetBookValue,
        public float $newNetBookValue,
        public ?string $glAccountId,
        public string $reason,
        public DateTimeInterface $revaluationDate,
        public bool $postedToGL = false,
        public array $context = [],
    ) {}

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getRevaluationAmount(): float
    {
        return $this->revaluationAmount->amount;
    }

    public function isIncrement(): bool
    {
        return $this->revaluationType === RevaluationType::INCREMENT;
    }

    public function getEquityImpact(): float
    {
        return $this->revaluationAmount->getEquityAdjustment();
    }

    public function getIncomeStatementImpact(): float
    {
        return $this->revaluationAmount->getIncomeStatementImpact();
    }

    public function toArray(): array
    {
        return [
            'revaluation_id' => $this->revaluationId,
            'asset_id' => $this->assetId,
            'tenant_id' => $this->tenantId,
            'revaluation_type' => $this->revaluationType->value,
            'revaluation_amount' => $this->revaluationAmount->amount,
            'currency' => $this->revaluationAmount->currency,
            'previous_cost' => $this->previousCost,
            'new_cost' => $this->newCost,
            'previous_net_book_value' => $this->previousNetBookValue,
            'new_net_book_value' => $this->newNetBookValue,
            'gl_account_id' => $this->glAccountId,
            'reason' => $this->reason,
            'revaluation_date' => $this->revaluationDate->format('Y-m-d'),
            'posted_to_gl' => $this->postedToGL,
            'equity_impact' => $this->getEquityImpact(),
            'income_statement_impact' => $this->getIncomeStatementImpact(),
            'context' => $this->context,
        ];
    }
}
