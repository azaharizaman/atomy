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
        // Implementation depends on business logic for period creation
        // This is a placeholder that needs to be implemented based on specific requirements
        throw new \RuntimeException('createNextPeriod not yet implemented');
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
