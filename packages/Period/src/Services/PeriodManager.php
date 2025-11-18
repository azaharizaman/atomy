<?php

declare(strict_types=1);

namespace Nexus\Period\Services;

use DateTimeImmutable;
use Nexus\Period\Contracts\AuditLoggerInterface;
use Nexus\Period\Contracts\AuthorizationInterface;
use Nexus\Period\Contracts\CacheRepositoryInterface;
use Nexus\Period\Contracts\PeriodInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Contracts\PeriodRepositoryInterface;
use Nexus\Period\Enums\PeriodStatus;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\InvalidPeriodStatusException;
use Nexus\Period\Exceptions\NoOpenPeriodException;
use Nexus\Period\Exceptions\PeriodNotFoundException;
use Nexus\Period\Exceptions\PeriodReopeningUnauthorizedException;

/**
 * Period Manager Service
 * 
 * Main service for period management operations with caching for performance.
 * Critical performance requirement: isPostingAllowed() must execute in < 5ms.
 */
final class PeriodManager implements PeriodManagerInterface
{
    /**
     * Cache contract interface
     */
    private const CACHE_PREFIX = 'period:';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly PeriodRepositoryInterface $repository,
        private readonly CacheRepositoryInterface $cache,
        private readonly AuthorizationInterface $authorization,
        private readonly AuditLoggerInterface $auditLogger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function isPostingAllowed(DateTimeImmutable $date, PeriodType $type): bool
    {
        $cacheKey = self::CACHE_PREFIX . "open:{$type->value}";
        
        // Try cache first for performance (< 5ms requirement)
        $openPeriod = $this->cache->get($cacheKey);
        
        if ($openPeriod === null) {
            $openPeriod = $this->repository->findOpenByType($type);
            
            if ($openPeriod === null) {
                throw NoOpenPeriodException::forType($type->label());
            }
            
            // Cache the open period for fast subsequent lookups
            $this->cache->put($cacheKey, $openPeriod, self::CACHE_TTL);
        }
        
        return $openPeriod->containsDate($date) && $openPeriod->isPostingAllowed();
    }

    /**
     * {@inheritDoc}
     */
    public function getOpenPeriod(PeriodType $type): ?PeriodInterface
    {
        $cacheKey = self::CACHE_PREFIX . "open:{$type->value}";
        
        $openPeriod = $this->cache->get($cacheKey);
        
        if ($openPeriod === null) {
            $openPeriod = $this->repository->findOpenByType($type);
            
            if ($openPeriod !== null) {
                $this->cache->put($cacheKey, $openPeriod, self::CACHE_TTL);
            }
        }
        
        return $openPeriod;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentPeriodForDate(DateTimeImmutable $date, PeriodType $type): ?PeriodInterface
    {
        return $this->repository->findByDate($date, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function closePeriod(string $periodId, string $reason, string $userId): void
    {
        $period = $this->findById($periodId);
        
        // Validate status transition
        if (!$period->getStatus()->canTransitionTo(PeriodStatus::Closed)) {
            throw InvalidPeriodStatusException::forTransition(
                $period->getStatus()->value,
                PeriodStatus::Closed->value
            );
        }
        
        // Update period status to Closed
        $this->updatePeriodStatus($period, PeriodStatus::Closed);
        
        // Clear cache
        $this->clearPeriodCache($period->getType());
        
        // Audit log
        $this->auditLogger->log(
            $periodId,
            'period_closed',
            "Period {$period->getName()} closed by user {$userId}. Reason: {$reason}"
        );
    }

    /**
     * {@inheritDoc}
     */
    public function reopenPeriod(string $periodId, string $reason, string $userId): void
    {
        $period = $this->findById($periodId);
        
        // Check authorization
        if (!$this->authorization->canReopenPeriod($userId)) {
            throw PeriodReopeningUnauthorizedException::forUser($userId, $periodId);
        }
        
        // Validate status transition
        if (!$period->getStatus()->canTransitionTo(PeriodStatus::Open)) {
            throw InvalidPeriodStatusException::forTransition(
                $period->getStatus()->value,
                PeriodStatus::Open->value
            );
        }
        
        // Update period status to Open
        $this->updatePeriodStatus($period, PeriodStatus::Open);
        
        // Clear cache
        $this->clearPeriodCache($period->getType());
        
        // Audit log
        $this->auditLogger->log(
            $periodId,
            'period_reopened',
            "Period {$period->getName()} reopened by user {$userId}. Reason: {$reason}"
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createNextPeriod(PeriodType $type): PeriodInterface
    {
        // Get all periods of this type, sorted by end date descending
        $periods = $this->repository->findByType($type);
        
        if (empty($periods)) {
            throw new \RuntimeException(
                "Cannot create next period: No existing periods found for type {$type->value}. " .
                "Please create an initial period first."
            );
        }
        
        // Sort by end date to get the latest period
        usort($periods, fn($a, $b) => $b->getEndDate() <=> $a->getEndDate());
        $lastPeriod = $periods[0];
        
        // Calculate next period dates (starting the day after the last period ends)
        $nextStartDate = $lastPeriod->getEndDate()->modify('+1 day');
        
        // Determine the period length from the last period
        $dayCount = (int) $lastPeriod->getStartDate()->diff($lastPeriod->getEndDate())->days + 1;
        
        // Calculate end date based on the same duration
        // If it's roughly a month (28-31 days), use end of month logic
        if ($dayCount >= 28 && $dayCount <= 31) {
            $nextEndDate = $nextStartDate->modify('last day of this month');
        }
        // If it's roughly a quarter (89-92 days), use quarter logic
        elseif ($dayCount >= 89 && $dayCount <= 92) {
            $nextEndDate = $nextStartDate->modify('+2 months')->modify('last day of this month');
        }
        // If it's roughly a year (365-366 days), use year logic
        elseif ($dayCount >= 365 && $dayCount <= 366) {
            $nextEndDate = $nextStartDate->modify('last day of december this year');
        }
        // Otherwise, use the exact same duration
        else {
            $nextEndDate = $nextStartDate->modify('+' . ($dayCount - 1) . ' days');
        }
        
        // Check for overlapping periods
        if ($this->repository->hasOverlappingPeriod($nextStartDate, $nextEndDate, $type)) {
            throw new \RuntimeException(
                "Cannot create next period: Date range {$nextStartDate->format('Y-m-d')} to " .
                "{$nextEndDate->format('Y-m-d')} overlaps with an existing period."
            );
        }
        
        // Determine fiscal year
        $fiscalYear = $this->determineFiscalYear($nextStartDate, $nextEndDate);
        
        // Generate period name
        $periodName = $this->generatePeriodName($nextStartDate, $nextEndDate, $type);
        
        // Create new period data
        $periodData = [
            'type' => $type,
            'status' => PeriodStatus::Open,
            'start_date' => $nextStartDate,
            'end_date' => $nextEndDate,
            'fiscal_year' => $fiscalYear,
            'name' => $periodName,
            'description' => "Auto-generated period following {$lastPeriod->getName()}",
        ];
        
        // Create the period through the repository
        // Note: This requires the repository to support creating periods from data
        // The actual implementation depends on how the repository handles creation
        $newPeriod = $this->repository->create($periodData);
        
        // Clear cache for this type
        $this->clearPeriodCache($type);
        
        // Audit log
        $this->auditLogger->log(
            $newPeriod->getId(),
            'period_created',
            "Next period {$periodName} created automatically for type {$type->label()}"
        );
        
        return $newPeriod;
    }
    
    /**
     * Determine the fiscal year for a period
     */
    private function determineFiscalYear(DateTimeImmutable $startDate, DateTimeImmutable $endDate): string
    {
        // Use the year of the end date as the fiscal year
        // This handles cases where the period spans multiple calendar years
        return $endDate->format('Y');
    }
    
    /**
     * Generate a human-readable name for a period
     */
    private function generatePeriodName(DateTimeImmutable $startDate, DateTimeImmutable $endDate, PeriodType $type): string
    {
        // Calculate the duration in days
        $dayCount = (int) $startDate->diff($endDate)->days + 1;
        
        // Monthly period
        if ($dayCount >= 28 && $dayCount <= 31) {
            return strtoupper($startDate->format('M-Y')); // e.g., "JAN-2024"
        }
        
        // Quarterly period
        if ($dayCount >= 89 && $dayCount <= 92) {
            $quarter = (int) ceil((int) $startDate->format('n') / 3);
            return $startDate->format('Y') . "-Q{$quarter}"; // e.g., "2024-Q1"
        }
        
        // Yearly period
        if ($dayCount >= 365 && $dayCount <= 366) {
            return "FY-" . $endDate->format('Y'); // e.g., "FY-2024"
        }
        
        // Custom period
        return $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d');
    }

    /**
     * {@inheritDoc}
     */
    public function listPeriods(PeriodType $type, ?string $fiscalYear = null): array
    {
        return $this->repository->findByType($type, $fiscalYear);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $periodId): PeriodInterface
    {
        $period = $this->repository->find($periodId);
        
        if ($period === null) {
            throw PeriodNotFoundException::forId($periodId);
        }
        
        return $period;
    }

    /**
     * Update period status (internal method)
     */
    private function updatePeriodStatus(PeriodInterface $period, PeriodStatus $newStatus): void
    {
        // This requires a mutable method on the period or a new period instance
        // Implementation depends on how PeriodInterface is implemented in the app layer
        $this->repository->save($period);
    }

    /**
     * Clear all cached data for a period type
     */
    private function clearPeriodCache(PeriodType $type): void
    {
        $cacheKey = self::CACHE_PREFIX . "open:{$type->value}";
        $this->cache->forget($cacheKey);
    }
}
