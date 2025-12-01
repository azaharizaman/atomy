<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Contracts;

/**
 * Contract for Nexus\Period integration.
 *
 * This interface is implemented by the orchestrator to interact
 * with the Nexus\Period package.
 */
interface PeriodContextInterface
{
    /**
     * Get period information.
     *
     * @param string $periodId
     * @return array{id: string, name: string, start_date: \DateTimeImmutable, end_date: \DateTimeImmutable, status: string}
     */
    public function getPeriod(string $periodId): array;

    /**
     * Check if period is open.
     *
     * @param string $periodId
     * @return bool
     */
    public function isOpen(string $periodId): bool;

    /**
     * Lock a period.
     *
     * @param string $periodId
     * @return void
     */
    public function lock(string $periodId): void;

    /**
     * Unlock a period.
     *
     * @param string $periodId
     * @return void
     */
    public function unlock(string $periodId): void;
}
