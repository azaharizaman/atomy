<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Entities\AssetDepreciation;
use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Exceptions\AssetNotDepreciableException;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationPeriodClosedException;
use Nexus\FixedAssetDepreciation\Exceptions\ScheduleNotFoundException;
use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult;
use Nexus\FixedAssetDepreciation\ValueObjects\PeriodForecast;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Nexus\FixedAssetDepreciation\Events\DepreciationCalculatedEvent;
use Nexus\FixedAssetDepreciation\Events\DepreciationRunCompletedEvent;

/**
 * Depreciation Manager
 *
 * Primary facade coordinating all depreciation operations.
 * Implements the DepreciationManagerInterface providing a single entry point
 * for all depreciation-related functionality.
 *
 * Progressive Features:
 * - Tier 1: Basic depreciation, schedule generation
 * - Tier 2: Advanced methods, forecasting, revaluation
 * - Tier 3: Tax depreciation, multi-currency, full IFRS
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
readonly class DepreciationManager implements \Nexus\FixedAssetDepreciation\Contracts\DepreciationManagerInterface
{
    public function __construct(
        private DepreciationCalculator $calculator,
        private DepreciationScheduleGenerator $scheduleGenerator,
        private ?AssetRevaluationService $revaluationService,
        private DepreciationMethodFactory $methodFactory,
        private AssetDataProviderInterface $assetProvider,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private int $currentTier = 1,
    ) {}

    public function calculateDepreciation(string $assetId, string $periodId): AssetDepreciation
    {
        $this->validateAssetDepreciable($assetId);

        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            throw AssetNotDepreciableException::notFound($assetId);
        }

        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        $methodType = $this->assetProvider->getAssetDepreciationMethod($assetId);

        $depreciationAmount = $this->calculator->calculate(
            $assetId,
            new DateTimeImmutable(),
            DepreciationType::BOOK
        );

        $bookValueBefore = new BookValue($cost, $salvageValue, $accumulatedDepreciation);
        $bookValueAfter = $bookValueBefore->depreciate($depreciationAmount);

        $depreciation = new AssetDepreciation(
            id: $this->generateDepreciationId($assetId, $periodId),
            assetId: $assetId,
            scheduleId: $this->getScheduleId($assetId),
            periodId: $periodId,
            methodType: $methodType,
            depreciationType: DepreciationType::BOOK,
            depreciationAmount: $depreciationAmount,
            bookValueBefore: $bookValueBefore,
            bookValueAfter: $bookValueAfter,
            calculationDate: new DateTimeImmutable(),
            postingDate: null,
            status: DepreciationStatus::CALCULATED
        );

        $this->eventDispatcher->dispatch(new DepreciationCalculatedEvent(
            depreciationId: $depreciation->id,
            assetId: $assetId,
            tenantId: $asset['tenant_id'] ?? '',
            periodId: $periodId,
            methodType: $methodType,
            depreciationType: DepreciationType::BOOK,
            depreciationAmount: $depreciationAmount,
            bookValueBefore: $bookValueBefore->getNetBookValue(),
            bookValueAfter: $bookValueAfter->getNetBookValue(),
            calculationDate: new DateTimeImmutable()
        ));

        $this->logger->info('Depreciation calculated', [
            'asset_id' => $assetId,
            'period_id' => $periodId,
            'amount' => $depreciationAmount->amount,
        ]);

        return $depreciation;
    }

    public function generateSchedule(string $assetId): DepreciationSchedule
    {
        $this->validateAssetDepreciable($assetId);

        $asset = $this->assetProvider->getAsset($assetId);
        if ($asset === null) {
            throw AssetNotDepreciableException::notFound($assetId);
        }

        return $this->scheduleGenerator->generate(
            $assetId,
            $asset['tenant_id'] ?? '',
            DepreciationType::BOOK
        );
    }

    public function runPeriodicDepreciation(string $periodId): DepreciationRunResult
    {
        $runId = 'RUN-' . uniqid();
        $processedAssets = [];
        $errors = [];

        $this->logger->info('Starting periodic depreciation run', [
            'run_id' => $runId,
            'period_id' => $periodId,
        ]);

        $result = DepreciationRunResult::create(
            periodId: $periodId,
            runId: $runId,
            processedAssets: $processedAssets,
            errors: $errors,
            currency: 'USD'
        );

        $this->eventDispatcher->dispatch(DepreciationRunCompletedEvent::fromResult(
            $result,
            '',
            false
        ));

        return $result;
    }

    public function reverseDepreciation(string $depreciationId, string $reason): void
    {
        $this->logger->warning('Depreciation reversal requested', [
            'depreciation_id' => $depreciationId,
            'reason' => $reason,
        ]);
    }

    public function revalueAsset(
        string $assetId,
        float $newValue,
        float $newSalvageValue,
        \Nexus\FixedAssetDepreciation\Enums\RevaluationType $type,
        string $reason,
        ?string $glAccountId = null
    ): \Nexus\FixedAssetDepreciation\Entities\AssetRevaluation {
        if ($this->revaluationService === null) {
            throw new \RuntimeException('Revaluation service not available in current tier');
        }

        return $this->revaluationService->revalue(
            $assetId,
            $newValue,
            $newSalvageValue,
            $type,
            $reason,
            $glAccountId
        );
    }

    public function calculateTaxDepreciation(string $assetId, string $taxMethod, int $taxYear): DepreciationAmount
    {
        if ($this->currentTier < 3) {
            throw new \RuntimeException('Tax depreciation requires Tier 3 (Enterprise)');
        }

        return $this->calculator->calculate($assetId, null, DepreciationType::TAX);
    }

    public function forecastDepreciation(string $assetId, int $numberOfPeriods): DepreciationForecast
    {
        if ($this->currentTier < 2) {
            throw new \RuntimeException('Depreciation forecasting requires Tier 2 (Advanced) or higher');
        }

        return $this->calculator->forecast($assetId, $numberOfPeriods);
    }

    private function validateAssetDepreciable(string $assetId): void
    {
        $asset = $this->assetProvider->getAsset($assetId);
        
        if ($asset === null) {
            throw AssetNotDepreciableException::notFound($assetId);
        }

        if (!$this->assetProvider->isAssetActive($assetId)) {
            throw AssetNotDepreciableException::inactive($assetId, $asset['status'] ?? 'unknown');
        }

        $cost = $this->assetProvider->getAssetCost($assetId);
        if ($cost <= 0) {
            throw AssetNotDepreciableException::zeroCost($assetId);
        }

        $accumulatedDepreciation = $this->assetProvider->getAccumulatedDepreciation($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $depreciableAmount = $cost - $salvageValue;

        if ($accumulatedDepreciation >= $depreciableAmount) {
            throw AssetNotDepreciableException::fullyDepreciated($assetId, $cost - $accumulatedDepreciation, $salvageValue);
        }
    }

    private function generateDepreciationId(string $assetId, string $periodId): string
    {
        return sprintf('DEP-%s-%s-%s', $assetId, $periodId, uniqid());
    }

    private function getScheduleId(string $assetId): string
    {
        return sprintf('SCH-%s', $assetId);
    }
}
