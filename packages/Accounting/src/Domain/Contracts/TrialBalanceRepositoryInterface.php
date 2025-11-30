<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\TrialBalance;

/**
 * Contract for trial balance data access.
 *
 * Implementations must provide access to trial balance data
 * for a specific date or period.
 */
interface TrialBalanceRepositoryInterface
{
    /**
     * Get trial balance as of the specified date.
     *
     * @param \DateTimeImmutable $asOfDate The date for the trial balance
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return TrialBalance The trial balance
     *
     * @throws \RuntimeException If retrieval fails
     */
    public function getAsOf(\DateTimeImmutable $asOfDate, ?string $tenantId = null): TrialBalance;

    /**
     * Get adjusted trial balance as of the specified date.
     *
     * @param \DateTimeImmutable $asOfDate The date for the trial balance
     * @param string|null $tenantId Optional tenant ID for multi-tenant systems
     *
     * @return TrialBalance The adjusted trial balance
     *
     * @throws \RuntimeException If retrieval fails
     */
    public function getAdjustedAsOf(\DateTimeImmutable $asOfDate, ?string $tenantId = null): TrialBalance;
}
