<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\WorkCenterManagerInterface;
use Nexus\Manufacturing\Contracts\WorkCenterRepositoryInterface;
use Nexus\Manufacturing\Contracts\WorkCenterInterface;
use Nexus\Manufacturing\Exceptions\WorkCenterNotFoundException;

/**
 * Work Center Manager implementation.
 *
 * Manages work center master data and capacity calculations.
 */
final readonly class WorkCenterManager implements WorkCenterManagerInterface
{
    public function __construct(
        private WorkCenterRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(
        string $code,
        string $name,
        float $capacityHoursPerDay,
        float $efficiency = 1.0,
        float $utilization = 1.0,
        float $laborRate = 0.0,
        float $machineRate = 0.0,
        float $overheadRate = 0.0,
        ?string $costCenterId = null
    ): WorkCenterInterface {
        return $this->repository->create([
            'code' => $code,
            'name' => $name,
            'capacityHoursPerDay' => $capacityHoursPerDay,
            'efficiency' => $efficiency,
            'utilization' => $utilization,
            'laborRate' => $laborRate,
            'machineRate' => $machineRate,
            'overheadRate' => $overheadRate,
            'costCenterId' => $costCenterId,
            'isActive' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getById(string $id): WorkCenterInterface
    {
        $workCenter = $this->repository->findById($id);

        if ($workCenter === null) {
            throw WorkCenterNotFoundException::withId($id);
        }

        return $workCenter;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCode(string $code): WorkCenterInterface
    {
        $workCenter = $this->repository->findByCode($code);

        if ($workCenter === null) {
            throw WorkCenterNotFoundException::withCode($code);
        }

        return $workCenter;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $workCenterId, array $data): void
    {
        $this->getById($workCenterId);

        $this->repository->update($workCenterId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(string $workCenterId): void
    {
        $this->getById($workCenterId);

        $this->repository->update($workCenterId, ['isActive' => false]);
    }

    /**
     * Activate a work center.
     */
    public function activate(string $id): void
    {
        $this->getById($id);

        $this->repository->update($id, ['isActive' => true]);
    }

    /**
     * Calculate daily capacity.
     */
    public function calculateDailyCapacity(string $id): float
    {
        $workCenter = $this->getById($id);

        return $workCenter->getCapacityHoursPerDay()
            * $workCenter->getEfficiency()
            * $workCenter->getUtilization();
    }

    /**
     * Calculate weekly capacity.
     */
    public function calculateWeeklyCapacity(string $id): float
    {
        $dailyCapacity = $this->calculateDailyCapacity($id);
        return $dailyCapacity * 5; // Assuming 5-day work week
    }

    /**
     * Get available hours for a specific date.
     */
    public function getAvailableHours(string $id, \DateTimeImmutable $date): float
    {
        $workCenter = $this->getById($id);

        // Check if date is a working day
        $dayOfWeek = (int) $date->format('N'); // 1 (Monday) to 7 (Sunday)
        $daysPerWeek = $workCenter->getDaysPerWeek();

        // Assume work days are Monday to Friday for 5-day week, etc.
        if ($dayOfWeek > $daysPerWeek) {
            return 0.0;
        }

        // Check for holidays or special closures
        $closures = $this->repository->getClosures($id, $date);
        if (count($closures) > 0) {
            return 0.0;
        }

        // Apply efficiency and utilization
        return $workCenter->getCapacityHoursPerDay()
            * $workCenter->getEfficiency()
            * $workCenter->getUtilization();
    }

    /**
     * Get available hours for a period.
     */
    public function getAvailableHoursForPeriod(string $id, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): float
    {
        $totalHours = 0.0;
        $current = $startDate;

        while ($current <= $endDate) {
            $totalHours += $this->getAvailableHours($id, $current);
            $current = $current->modify('+1 day');
        }

        return $totalHours;
    }

    /**
     * Add closure to work center.
     */
    public function addClosure(string $id, \DateTimeImmutable $date, string $reason, float $hoursUnavailable = 0.0): void
    {
        $this->getById($id);

        $this->repository->addClosure($id, [
            'date' => $date->format('Y-m-d'),
            'reason' => $reason,
            'hoursUnavailable' => $hoursUnavailable,
        ]);
    }

    /**
     * Remove closure from work center.
     */
    public function removeClosure(string $id, \DateTimeImmutable $date): void
    {
        $this->getById($id);

        $this->repository->removeClosure($id, $date);
    }

    /**
     * Set alternative work centers.
     */
    public function setAlternatives(string $id, array $alternativeIds): void
    {
        $this->getById($id);

        // Validate all alternatives exist
        foreach ($alternativeIds as $altId) {
            $this->getById($altId);
        }

        $this->repository->update($id, ['alternativeIds' => $alternativeIds]);
    }

    /**
     * Get alternative work centers.
     */
    public function getAlternatives(string $id): array
    {
        $workCenter = $this->getById($id);

        $alternativeIds = $workCenter->getAlternativeIds();
        $alternatives = [];

        foreach ($alternativeIds as $altId) {
            try {
                $alt = $this->getById($altId);
                if ($alt->isActive()) {
                    $alternatives[] = $alt;
                }
            } catch (WorkCenterNotFoundException) {
                // Alternative no longer exists
            }
        }

        return $alternatives;
    }

    /**
     * Find work centers by type.
     */
    public function findByType(string $type): array
    {
        return $this->repository->findByType($type);
    }

    /**
     * Find active work centers.
     */
    public function findActive(): array
    {
        return $this->repository->findActive();
    }

    /**
     * {@inheritdoc}
     */
    public function setCalendar(
        string $workCenterId,
        \DateTimeImmutable $date,
        array $periods,
        bool $isWorkingDay = true
    ): void {
        $this->getById($workCenterId);

        $this->repository->setCalendar($workCenterId, [
            'date' => $date->format('Y-m-d'),
            'periods' => $periods,
            'isWorkingDay' => $isWorkingDay,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function copyCalendar(
        string $workCenterId,
        \DateTimeImmutable $sourceStart,
        \DateTimeImmutable $sourceEnd,
        \DateTimeImmutable $targetStart
    ): void {
        $this->getById($workCenterId);

        $this->repository->copyCalendar($workCenterId, $sourceStart, $sourceEnd, $targetStart);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCapacity(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $this->getById($workCenterId);

        $capacity = [];
        $current = $startDate;

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $capacity[$dateKey] = $this->getAvailableHours($workCenterId, $current);
            $current = $current->modify('+1 day');
        }

        return $capacity;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadedCapacity(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $this->getById($workCenterId);

        return $this->repository->getLoadedCapacity($workCenterId, $startDate, $endDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getUtilization(
        string $workCenterId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $available = $this->getAvailableCapacity($workCenterId, $startDate, $endDate);
        $loaded = $this->getLoadedCapacity($workCenterId, $startDate, $endDate);

        $utilization = [];
        foreach ($available as $date => $availableHours) {
            $loadedHours = $loaded[$date] ?? 0.0;
            $utilization[$date] = $availableHours > 0 ? ($loadedHours / $availableHours) * 100 : 0;
        }

        return $utilization;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateCost(
        string $workCenterId,
        float $setupHours,
        float $runHours
    ): array {
        $workCenter = $this->getById($workCenterId);

        $laborCost = ($setupHours + $runHours) * $workCenter->getLaborRate();
        $machineCost = ($setupHours + $runHours) * $workCenter->getMachineRate();
        $overheadCost = ($setupHours + $runHours) * $workCenter->getOverheadRate();

        return [
            'labor' => $laborCost,
            'machine' => $machineCost,
            'overhead' => $overheadCost,
            'total' => $laborCost + $machineCost + $overheadCost,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findAlternatives(string $workCenterId): array
    {
        return $this->getAlternatives($workCenterId);
    }
}
