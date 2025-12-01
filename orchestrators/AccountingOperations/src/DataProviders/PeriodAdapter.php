<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DataProviders;

use Nexus\AccountPeriodClose\Contracts\PeriodContextInterface;

/**
 * Adapter that integrates with Nexus\Period for period context.
 */
final readonly class PeriodAdapter implements PeriodContextInterface
{
    public function __construct(
        // Injected dependencies from consuming application
    ) {}

    public function getCurrentPeriodId(string $tenantId): string
    {
        // Implementation provided by consuming application
        return '';
    }

    public function isPeriodOpen(string $tenantId, string $periodId): bool
    {
        // Implementation provided by consuming application
        return false;
    }

    public function isPeriodClosed(string $tenantId, string $periodId): bool
    {
        // Implementation provided by consuming application
        return false;
    }

    public function getPeriodStartDate(string $tenantId, string $periodId): \DateTimeImmutable
    {
        // Implementation provided by consuming application
        return new \DateTimeImmutable();
    }

    public function getPeriodEndDate(string $tenantId, string $periodId): \DateTimeImmutable
    {
        // Implementation provided by consuming application
        return new \DateTimeImmutable();
    }

    public function getNextPeriodId(string $tenantId, string $periodId): ?string
    {
        // Implementation provided by consuming application
        return null;
    }

    public function getPreviousPeriodId(string $tenantId, string $periodId): ?string
    {
        // Implementation provided by consuming application
        return null;
    }

    public function isYearEnd(string $tenantId, string $periodId): bool
    {
        // Implementation provided by consuming application
        return false;
    }
}
