<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Contracts\AssetRevaluationInterface;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Entities\AssetRevaluation;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\RevaluationType;
use Nexus\FixedAssetDepreciation\Exceptions\RevaluationException;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
use Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount;
use Psr\EventDispatcher\EventDispatcherInterface;
use Nexus\FixedAssetDepreciation\Events\AssetRevaluedEvent;

/**
 * Asset Revaluation Service
 *
 * Handles IFRS IAS 16 compliant asset revaluation operations including
 * increment and decrement revaluations with proper equity adjustments.
 *
 * Tier: 2+ (Advanced and Enterprise)
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
readonly class AssetRevaluationService implements AssetRevaluationInterface
{
    public function __construct(
        private AssetDataProviderInterface $assetProvider,
        private DepreciationScheduleGenerator $scheduleGenerator,
        private DepreciationForecastService $forecastService,
        private EventDispatcherInterface $eventDispatcher,
        private int $currentTier = 1,
    ) {}

    /**
     * Revalue an asset.
     */
    public function revalue(
        string $assetId,
        float $newCost,
        float $newSalvageValue,
        RevaluationType $type,
        string $reason,
        ?\DateTimeInterface $revaluationDate = null,
        ?string $glAccountId = null
    ): AssetRevaluation {
        if ($this->currentTier < 2) {
            throw RevaluationException::tierNotAvailable($assetId);
        }

        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            throw RevaluationException::disposedAsset($assetId);
        }

        $previousCost = $this->assetProvider->getAssetCost($assetId);
        $previousSalvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $previousAccumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);

        if ($newSalvageValue > $newCost) {
            throw RevaluationException::invalidSalvageValue($assetId, $newSalvageValue, $newCost);
        }

        $previousBookValue = new BookValue(
            cost: $previousCost,
            salvageValue: $previousSalvageValue,
            accumulatedDepreciation: $previousAccumulatedDepreciation
        );

        $newBookValue = new BookValue(
            cost: $newCost,
            salvageValue: $newSalvageValue,
            accumulatedDepreciation: $previousAccumulatedDepreciation
        );

        $revaluationAmount = $type === RevaluationType::INCREMENT
            ? RevaluationAmount::createIncrement(
                $previousCost,
                $newCost,
                $previousSalvageValue,
                $newSalvageValue,
                $previousAccumulatedDepreciation,
                $asset['currency'] ?? 'USD'
            )
            : RevaluationAmount::createDecrement(
                $previousCost,
                $newCost,
                $previousSalvageValue,
                $newSalvageValue,
                $previousAccumulatedDepreciation,
                $asset['currency'] ?? 'USD'
            );

        $revaluation = new AssetRevaluation(
            id: $this->generateRevaluationId($assetId),
            assetId: $assetId,
            tenantId: $asset['tenant_id'] ?? '',
            revaluationDate: $revaluationDate ? DateTimeImmutable::createFromInterface($revaluationDate) : new DateTimeImmutable(),
            revaluationType: $type,
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            revaluationAmount: $revaluationAmount,
            glAccountId: $glAccountId,
            reason: $reason,
            createdAt: new DateTimeImmutable()
        );

        $this->assetProvider->updateAccumulatedDepreciation($assetId, $previousAccumulatedDepreciation);

        $this->eventDispatcher->dispatch(new AssetRevaluedEvent(
            revaluationId: $revaluation->id,
            assetId: $assetId,
            tenantId: $revaluation->tenantId,
            revaluationType: $type,
            revaluationAmount: $revaluationAmount,
            previousCost: $previousCost,
            newCost: $newCost,
            previousNetBookValue: $previousBookValue->getNetBookValue(),
            newNetBookValue: $newBookValue->getNetBookValue(),
            glAccountId: $glAccountId,
            reason: $reason,
            revaluationDate: $revaluation->revaluationDate
        ));

        return $revaluation;
    }

    /**
     * Reverse a previous revaluation.
     */
    public function reverse(string $revaluationId, string $reason): AssetRevaluation
    {
        // Find the original revaluation
        $originalRevaluation = $this->findById($revaluationId);
        
        if ($originalRevaluation === null) {
            throw new \InvalidArgumentException(
                sprintf('Revaluation not found: %s', $revaluationId)
            );
        }

        // Create a reversal by swapping the book values
        $previousBookValue = $originalRevaluation->newBookValue;
        $newBookValue = $originalRevaluation->previousBookValue;
        
        $revaluationAmount = $originalRevaluation->revaluationAmount->negate();
        
        $revaluation = new AssetRevaluation(
            id: $this->generateRevaluationId($originalRevaluation->assetId),
            assetId: $originalRevaluation->assetId,
            tenantId: $originalRevaluation->tenantId,
            revaluationDate: new DateTimeImmutable(),
            revaluationType: $originalRevaluation->revaluationType === RevaluationType::INCREMENT
                ? RevaluationType::DECREMENT
                : RevaluationType::INCREMENT,
            previousBookValue: $previousBookValue,
            newBookValue: $newBookValue,
            revaluationAmount: $revaluationAmount,
            glAccountId: $originalRevaluation->glAccountId,
            reason: 'Reversal: ' . $reason,
            createdAt: new DateTimeImmutable(),
            reversesRevaluationId: $revaluationId
        );

        return $revaluation;
    }

    /**
     * Calculate the impact of a proposed revaluation.
     */
    public function calculateImpact(
        string $assetId,
        float $proposedValue
    ): array {
        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            throw new \InvalidArgumentException(sprintf('Asset not found: %s', $assetId));
        }

        $previousCost = $this->assetProvider->getAssetCost($assetId);
        $previousSalvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        
        $previousNetBookValue = $previousCost - $accumulatedDepreciation;
        
        // Determine revaluation type
        $revaluationType = $proposedValue > $previousNetBookValue
            ? RevaluationType::INCREMENT
            : RevaluationType::DECREMENT;
        
        $revaluationAmount = $proposedValue - $previousNetBookValue;
        
        // Calculate impact on depreciation
        $previousDepreciableBase = $previousCost - $previousSalvageValue;
        $newDepreciableBase = max(0, $proposedValue - $previousSalvageValue);
        
        // Remaining months of useful life
        $depreciationPercentage = $accumulatedDepreciation / $previousDepreciableBase;
        $remainingMonths = (int) ceil($usefulLifeMonths * (1 - $depreciationPercentage));
        $remainingMonths = max(1, $remainingMonths);
        
        // Calculate old and new annual depreciation
        $previousAnnualDepreciation = $previousDepreciableBase / $usefulLifeMonths * 12;
        $newAnnualDepreciation = $newDepreciableBase / $remainingMonths * 12;
        
        $annualDepreciationChange = $newAnnualDepreciation - $previousAnnualDepreciation;
        
        // Calculate revaluation reserve impact (IFRS: increments go to equity)
        $revaluationReserveImpact = $revaluationType === RevaluationType::INCREMENT
            ? $revaluationAmount
            : 0.0;

        return [
            'previousValue' => $previousNetBookValue,
            'newValue' => $proposedValue,
            'revaluationAmount' => $revaluationAmount,
            'revaluationType' => $revaluationType,
            'depreciationImpact' => $newDepreciableBase - $previousDepreciableBase,
            'annualDepreciationChange' => $annualDepreciationChange,
            'revaluationReserveImpact' => $revaluationReserveImpact,
            'remainingMonths' => $remainingMonths,
            'previousAnnualDepreciation' => $previousAnnualDepreciation,
            'newAnnualDepreciation' => $newAnnualDepreciation,
        ];
    }

    /**
     * Get current book value after revaluation.
     */
    public function getCurrentBookValue(string $assetId): BookValue
    {
        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);

        return new BookValue(
            cost: $cost,
            salvageValue: $salvageValue,
            accumulatedDepreciation: $accumulatedDepreciation
        );
    }

    /**
     * Get revaluation history.
     */
    public function getHistory(string $assetId, array $options = []): array
    {
        // In a real implementation, this would query the persistence layer
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Post revaluation to GL (placeholder - would integrate with journal entry service).
     */
    public function postToGl(string $revaluationId): AssetRevaluation
    {
        $revaluation = $this->findById($revaluationId);
        
        if ($revaluation === null) {
            throw new \InvalidArgumentException(
                sprintf('Revaluation not found: %s', $revaluationId)
            );
        }

        // In a real implementation, this would create journal entries
        // For now, just return the revaluation as if it was posted
        return $revaluation->withPosting('JE-' . uniqid());
    }

    /**
     * Process full revaluation (IFRS model).
     */
    public function processFullRevaluation(
        string $assetId,
        float $fairValue,
        float $salvageValue,
        string $reason,
        string $revaluationReserveAccount,
        ?string $depreciationExpenseAccount = null
    ): AssetRevaluation {
        if ($this->currentTier < 2) {
            throw RevaluationException::tierNotAvailable($assetId);
        }

        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            throw RevaluationException::disposedAsset($assetId);
        }

        // Determine revaluation type based on current book value
        $currentBookValue = $this->getCurrentBookValue($assetId);
        $currentNetBookValue = $currentBookValue->getNetBookValue();
        
        $type = $fairValue > $currentNetBookValue
            ? RevaluationType::INCREMENT
            : RevaluationType::DECREMENT;

        // Perform the revaluation
        $revaluation = $this->revalue(
            $assetId,
            $fairValue,
            $salvageValue,
            $type,
            $reason,
            null,
            $revaluationReserveAccount
        );

        // Recalculate depreciation schedule after revaluation
        $this->recalculateDepreciation($assetId, $revaluation->id);

        return $revaluation;
    }

    /**
     * Recalculate depreciation after revaluation.
     */
    public function recalculateDepreciation(
        string $assetId,
        string $revaluationId
    ): void {
        $revaluation = $this->findById($revaluationId);
        
        if ($revaluation === null) {
            throw new \InvalidArgumentException(
                sprintf('Revaluation not found: %s', $revaluationId)
            );
        }

        // Get new parameters from revaluation
        $newCost = $revaluation->newBookValue->cost;
        $newSalvageValue = $revaluation->newBookValue->salvageValue;
        
        // Get useful life - in real implementation would need to track remaining life
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        
        // Calculate remaining months based on accumulated depreciation
        $accumulatedDepreciation = $revaluation->newBookValue->accumulatedDepreciation;
        $depreciableAmount = $newCost - $newSalvageValue;
        
        if ($depreciableAmount > 0) {
            $depreciationPercentage = $accumulatedDepreciation / $depreciableAmount;
            $remainingMonths = (int) ceil($usefulLifeMonths * (1 - $depreciationPercentage));
            $remainingMonths = max(1, $remainingMonths);
        } else {
            $remainingMonths = 1;
        }

        // Generate new schedule - in real implementation would update the schedule
        // For now, this is a placeholder that would call the schedule generator
        $tenantId = $this->assetProvider->getAsset($assetId)['tenant_id'] ?? '';
        
        // The schedule would be regenerated here with new values
        // $newSchedule = $this->scheduleGenerator->adjust(...)
    }

    /**
     * Validate revaluation parameters.
     */
    public function validate(
        string $assetId,
        float $newValue,
        float $newSalvageValue
    ): array {
        $errors = [];
        
        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            $errors[] = 'Asset not found';
            return $errors;
        }

        $currentCost = $this->assetProvider->getAssetCost($assetId);
        $currentAccumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        
        if ($newSalvageValue < 0) {
            $errors[] = 'Salvage value cannot be negative';
        }
        
        if ($newSalvageValue > $newValue) {
            $errors[] = 'Salvage value cannot exceed the new asset value';
        }
        
        // Check for significant change warning
        $currentNetBookValue = $currentCost - $currentAccumulatedDepreciation;
        if ($currentNetBookValue > 0) {
            $percentageChange = abs($newValue - $currentNetBookValue) / $currentNetBookValue;
            if ($percentageChange > 0.5) {
                $errors[] = sprintf(
                    'Warning: Revaluation represents a %.1f%% change from current book value',
                    $percentageChange * 100
                );
            }
        }
        
        return $errors;
    }

    /**
     * Get revaluation reserve balance.
     */
    public function getReserveBalance(string $assetId): float
    {
        // In a real implementation, this would query the sum of all increment revaluations
        // For now, return 0 as placeholder
        return 0.0;
    }

    /**
     * Check if asset can be revalued.
     */
    public function canRevalue(string $assetId): bool
    {
        if ($this->currentTier < 2) {
            return false;
        }
        
        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            return false;
        }
        
        // Check if asset is active
        if (!$this->assetProvider->isAssetActive($assetId)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get revaluation for specific period.
     */
    public function getForPeriod(string $assetId, string $periodId): ?AssetRevaluation
    {
        // In a real implementation, this would query by period
        return null;
    }

    /**
     * Find revaluation by ID.
     */
    public function findById(string $revaluationId): ?AssetRevaluation
    {
        // In a real implementation, this would query the persistence layer
        return null;
    }

    /**
     * Approve a revaluation.
     */
    public function approve(string $revaluationId, string $approvedBy): AssetRevaluation
    {
        $revaluation = $this->findById($revaluationId);
        
        if ($revaluation === null) {
            throw new \InvalidArgumentException(
                sprintf('Revaluation not found: %s', $revaluationId)
            );
        }

        // In a real implementation, this would update approval status
        return $revaluation;
    }

    /**
     * Generate a unique revaluation ID.
     */
    private function generateRevaluationId(string $assetId): string
    {
        return sprintf('REV-%s-%s', $assetId, uniqid());
    }
}
