<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Services;

use DateTimeImmutable;
use Nexus\FixedAssetDepreciation\Contracts\DepreciationMethodInterface;
use Nexus\FixedAssetDepreciation\Contracts\Integration\AssetDataProviderInterface;
use Nexus\FixedAssetDepreciation\Contracts\Integration\PeriodProviderInterface;
use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\Exceptions\DepreciationCalculationException;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;

/**
 * Depreciation Schedule Generator Service
 *
 * Generates complete depreciation schedules for assets from acquisition
 * to disposal, supporting multiple depreciation methods.
 *
 * @package Nexus\FixedAssetDepreciation\Services
 */
final readonly class DepreciationScheduleGenerator
{
    public function __construct(
        private DepreciationMethodFactory $methodFactory,
        private AssetDataProviderInterface $assetProvider,
        private PeriodProviderInterface $periodProvider,
    ) {}

    public function generate(
        string $assetId,
        string $tenantId,
        DepreciationType $depreciationType = DepreciationType::BOOK,
        ?string $scheduleId = null
    ): DepreciationSchedule {
        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $methodType = $this->assetProvider->getAssetDepreciationMethod($assetId);
        $acquisitionDate = DateTimeImmutable::createFromInterface(
            $this->assetProvider->getAssetAcquisitionDate($assetId)
        );

        if ($cost <= 0) {
            throw DepreciationCalculationException::invalidCost($assetId, $cost);
        }

        if ($usefulLifeMonths <= 0) {
            throw DepreciationCalculationException::invalidUsefulLife($assetId, $usefulLifeMonths);
        }

        $method = $this->methodFactory->create($methodType);
        $depreciationLife = DepreciationLife::fromYears(
            (int) ceil($usefulLifeMonths / 12),
            $cost,
            $salvageValue
        );

        $scheduleId = $scheduleId ?? $this->generateScheduleId($assetId);
        
        $periods = $this->generatePeriods(
            $assetId,
            $method,
            $cost,
            $salvageValue,
            $usefulLifeMonths,
            $acquisitionDate,
            $scheduleId
        );

        return new DepreciationSchedule(
            id: $scheduleId,
            assetId: $assetId,
            tenantId: $tenantId,
            methodType: $methodType,
            depreciationType: $depreciationType,
            depreciationLife: $depreciationLife,
            acquisitionDate: $acquisitionDate,
            startDepreciationDate: $acquisitionDate,
            endDepreciationDate: $this->calculateEndDate($acquisitionDate, $usefulLifeMonths),
            prorateConvention: ProrateConvention::DAILY,
            periods: $periods,
            status: DepreciationStatus::CALCULATED,
            currency: $this->assetProvider->getAsset($assetId)['currency'] ?? 'USD',
            createdAt: new DateTimeImmutable()
        );
    }

    public function regenerateFromPeriod(
        string $assetId,
        string $tenantId,
        int $fromPeriodNumber,
        float $currentBookValue,
        float $accumulatedDepreciation,
        DepreciationType $depreciationType = DepreciationType::BOOK
    ): array {
        $cost = $this->assetProvider->getAssetCost($assetId);
        $salvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $usefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $methodType = $this->assetProvider->getAssetDepreciationMethod($assetId);
        $acquisitionDate = DateTimeImmutable::createFromInterface(
            $this->assetProvider->getAssetAcquisitionDate($assetId)
        );

        $method = $this->methodFactory->create($methodType);
        $remainingMonths = $usefulLifeMonths - $fromPeriodNumber + 1;
        $scheduleId = $this->generateScheduleId($assetId);

        return $this->generateRemainingPeriods(
            $method,
            $cost,
            $salvageValue,
            $currentBookValue,
            $accumulatedDepreciation,
            $remainingMonths,
            $acquisitionDate,
            $fromPeriodNumber,
            $scheduleId
        );
    }

    private function generatePeriods(
        string $assetId,
        DepreciationMethodInterface $method,
        float $cost,
        float $salvageValue,
        int $usefulLifeMonths,
        DateTimeImmutable $acquisitionDate,
        string $scheduleId
    ): array {
        $periods = [];
        $currentBookValue = $cost;
        $accumulatedDepreciation = 0.0;
        $periodNumber = 1;

        for ($month = 1; $month <= $usefulLifeMonths; $month++) {
            if ($currentBookValue <= $salvageValue) {
                break;
            }

            $periodStartDate = $acquisitionDate->modify(sprintf('+%d months', $month - 1));
            $periodEndDate = $acquisitionDate->modify(sprintf('+%d months - 1 day', $month));
            $periodId = $periodStartDate->format('Y-m');

            $remainingMonths = $usefulLifeMonths - $month + 1;
            
            $depreciationAmount = $method->calculate(
                $cost,
                $salvageValue,
                $periodStartDate,
                $periodEndDate,
                [
                    'useful_life_months' => $usefulLifeMonths,
                    'accumulated_depreciation' => $accumulatedDepreciation,
                    'remaining_months' => $remainingMonths,
                    'acquisition_date' => $acquisitionDate,
                    'current_year' => (int) ceil($month / 12),
                ]
            );

            $period = DepreciationSchedulePeriod::create(
                scheduleId: $scheduleId,
                periodNumber: $periodNumber,
                periodId: $periodId,
                periodStartDate: $periodStartDate,
                periodEndDate: $periodEndDate,
                openingBookValue: $currentBookValue,
                depreciationAmount: $depreciationAmount->amount,
                previousAccumulatedDepreciation: $accumulatedDepreciation
            );

            $periods[] = $period;
            
            $accumulatedDepreciation += $depreciationAmount->amount;
            $currentBookValue = $cost - $accumulatedDepreciation;
            $periodNumber++;
        }

        return $periods;
    }

    private function generateRemainingPeriods(
        DepreciationMethodInterface $method,
        float $cost,
        float $salvageValue,
        float $currentBookValue,
        float $accumulatedDepreciation,
        int $remainingMonths,
        DateTimeImmutable $startDate,
        int $startPeriodNumber,
        string $scheduleId
    ): array {
        $periods = [];

        for ($month = 1; $month <= $remainingMonths; $month++) {
            if ($currentBookValue <= $salvageValue) {
                break;
            }

            $periodStartDate = $startDate->modify(sprintf('+%d months', $month - 1));
            $periodEndDate = $startDate->modify(sprintf('+%d months - 1 day', $month));
            $periodId = $periodStartDate->format('Y-m');

            $depreciationAmount = $method->calculate(
                $cost,
                $salvageValue,
                $periodStartDate,
                $periodEndDate,
                [
                    'useful_life_months' => $remainingMonths,
                    'accumulated_depreciation' => $accumulatedDepreciation,
                    'remaining_months' => $remainingMonths - $month + 1,
                    'current_year' => (int) ceil($month / 12),
                ]
            );

            $period = DepreciationSchedulePeriod::create(
                scheduleId: $scheduleId,
                periodNumber: $startPeriodNumber + $month - 1,
                periodId: $periodId,
                periodStartDate: $periodStartDate,
                periodEndDate: $periodEndDate,
                openingBookValue: $currentBookValue,
                depreciationAmount: $depreciationAmount->amount,
                previousAccumulatedDepreciation: $accumulatedDepreciation
            );

            $periods[] = $period;
            
            $accumulatedDepreciation += $depreciationAmount->amount;
            $currentBookValue = $cost - $accumulatedDepreciation;
        }

        return $periods;
    }

    private function calculateEndDate(DateTimeImmutable $acquisitionDate, int $usefulLifeMonths): DateTimeImmutable
    {
        return $acquisitionDate->modify(sprintf('+%d months - 1 day', $usefulLifeMonths));
    }

    private function generateScheduleId(string $assetId): string
    {
        return sprintf('SCH-%s-%s', $assetId, uniqid());
    }

    /**
     * Adjust the depreciation schedule with new parameters.
     *
     * Modifies the depreciation schedule when there are changes to the asset's
     * useful life, salvage value, or depreciation method. This will recalculate
     * future depreciation amounts while preserving historical calculations.
     *
     * @param string $assetId The asset identifier
     * @param string $tenantId The tenant identifier
     * @param array $adjustments The adjustments to apply:
     *                           - usefulLifeMonths: int New useful life in months
     *                           - salvageValue: float New salvage value
     *                           - method: DepreciationMethodType New depreciation method
     *                           - fromPeriodNumber: int Period from which to recalculate
     * @return DepreciationSchedule The adjusted depreciation schedule
     * @throws DepreciationCalculationException If recalculation fails
     */
    public function adjust(
        string $assetId,
        string $tenantId,
        array $adjustments
    ): DepreciationSchedule {
        $newUsefulLifeMonths = $adjustments['usefulLifeMonths'] ?? null;
        $newSalvageValue = $adjustments['salvageValue'] ?? null;
        $newMethodType = $adjustments['method'] ?? null;
        $fromPeriodNumber = $adjustments['fromPeriodNumber'] ?? 1;
        $reason = $adjustments['reason'] ?? 'Schedule adjustment';

        // Get current asset data
        $cost = $this->assetProvider->getAssetCost($assetId);
        $previousSalvageValue = $this->assetProvider->getAssetSalvageValue($assetId);
        $previousUsefulLifeMonths = $this->assetProvider->getAssetUsefulLife($assetId);
        $methodType = $newMethodType ?? $this->assetProvider->getAssetDepreciationMethod($assetId);
        $acquisitionDate = DateTimeImmutable::createFromInterface(
            $this->assetProvider->getAssetAcquisitionDate($assetId)
        );

        // Apply defaults
        $salvageValue = $newSalvageValue ?? $previousSalvageValue;
        $usefulLifeMonths = $newUsefulLifeMonths ?? $previousUsefulLifeMonths;

        if ($cost <= 0) {
            throw DepreciationCalculationException::invalidCost($assetId, $cost);
        }

        if ($usefulLifeMonths <= 0) {
            throw DepreciationCalculationException::invalidUsefulLife($assetId, $usefulLifeMonths);
        }

        if ($salvageValue > $cost) {
            throw DepreciationCalculationException::salvageExceedsCost($assetId, $salvageValue, $cost);
        }

        // Calculate accumulated depreciation up to the adjustment period
        $accumulatedDepreciation = 0.0;
        $currentBookValue = $cost;

        if ($fromPeriodNumber > 1) {
            // Calculate historical depreciation up to the adjustment point
            $method = $this->methodFactory->create($methodType);
            $depreciationLife = DepreciationLife::fromYears(
                (int) ceil($previousUsefulLifeMonths / 12),
                $cost,
                $previousSalvageValue
            );

            for ($month = 1; $month < $fromPeriodNumber && $month <= $previousUsefulLifeMonths; $month++) {
                if ($currentBookValue <= $previousSalvageValue) {
                    break;
                }

                $periodStartDate = $acquisitionDate->modify(sprintf('+%d months', $month - 1));
                $remainingMonths = $previousUsefulLifeMonths - $month + 1;

                $depreciationAmount = $method->calculate(
                    $cost,
                    $previousSalvageValue,
                    $periodStartDate,
                    $periodStartDate->modify('+1 month -1 day'),
                    [
                        'useful_life_months' => $previousUsefulLifeMonths,
                        'accumulated_depreciation' => $accumulatedDepreciation,
                        'remaining_months' => $remainingMonths,
                        'acquisition_date' => $acquisitionDate,
                        'current_year' => (int) ceil($month / 12),
                    ]
                );

                $accumulatedDepreciation += $depreciationAmount->amount;
                $currentBookValue = $cost - $accumulatedDepreciation;
            }
        }

        // Calculate remaining months for new schedule
        $remainingMonths = max(0, $usefulLifeMonths - $fromPeriodNumber + 1);

        // Generate new depreciation method
        $method = $this->methodFactory->create($methodType);
        $scheduleId = $this->generateScheduleId($assetId);

        // Generate future periods with new parameters
        $futurePeriods = $this->generateRemainingPeriods(
            $method,
            $cost,
            $salvageValue,
            $currentBookValue,
            $accumulatedDepreciation,
            $remainingMonths,
            $acquisitionDate->modify(sprintf('+%d months', $fromPeriodNumber - 1)),
            $fromPeriodNumber,
            $scheduleId
        );

        // Create the adjusted depreciation life
        $depreciationLife = DepreciationLife::fromYears(
            (int) ceil($usefulLifeMonths / 12),
            $cost,
            $salvageValue
        );

        return new DepreciationSchedule(
            id: $scheduleId,
            assetId: $assetId,
            tenantId: $tenantId,
            methodType: $methodType,
            depreciationType: DepreciationType::BOOK,
            depreciationLife: $depreciationLife,
            acquisitionDate: $acquisitionDate,
            startDepreciationDate: $acquisitionDate,
            endDepreciationDate: $this->calculateEndDate($acquisitionDate, $usefulLifeMonths),
            prorateConvention: ProrateConvention::DAILY,
            periods: $futurePeriods,
            status: DepreciationStatus::CALCULATED,
            currency: $this->assetProvider->getAsset($assetId)['currency'] ?? 'USD',
            createdAt: new DateTimeImmutable()
        );
    }

    /**
     * Recalculate the schedule from a specific period.
     *
     * Recalculates all depreciation amounts from the specified period
     * onward with optional new parameters.
     *
     * @param string $assetId The asset identifier
     * @param string $tenantId The tenant identifier
     * @param int $fromPeriodNumber The period from which to recalculate
     * @param array $newParameters Optional new parameters for recalculation
     * @return DepreciationSchedule The recalculated schedule
     */
    public function recalculateFromPeriod(
        string $assetId,
        string $tenantId,
        int $fromPeriodNumber,
        array $newParameters = []
    ): DepreciationSchedule {
        return $this->adjust(
            $assetId,
            $tenantId,
            array_merge(
                $newParameters,
                ['fromPeriodNumber' => $fromPeriodNumber]
            )
        );
    }

    /**
     * Validate schedule adjustment parameters.
     *
     * @param string $assetId The asset identifier
     * @param int|null $newUsefulLifeMonths Proposed new useful life
     * @param float|null $newSalvageValue Proposed new salvage value
     * @return array Array of validation errors (empty if valid)
     */
    public function validateAdjustment(
        string $assetId,
        ?int $newUsefulLifeMonths = null,
        ?float $newSalvageValue = null
    ): array {
        $errors = [];

        $cost = $this->assetProvider->getAssetCost($assetId);
        $currentUsefulLife = $this->assetProvider->getAssetUsefulLife($assetId);
        $currentSalvageValue = $this->assetProvider->getAssetSalvageValue($assetId);

        if ($cost <= 0) {
            $errors[] = 'Asset cost must be greater than zero';
        }

        if ($newUsefulLifeMonths !== null && $newUsefulLifeMonths <= 0) {
            $errors[] = 'Useful life must be greater than zero';
        }

        $salvageValue = $newSalvageValue ?? $currentSalvageValue;
        if ($salvageValue < 0) {
            $errors[] = 'Salvage value cannot be negative';
        }

        if ($salvageValue > $cost) {
            $errors[] = 'Salvage value cannot exceed asset cost';
        }

        return $errors;
    }
}
