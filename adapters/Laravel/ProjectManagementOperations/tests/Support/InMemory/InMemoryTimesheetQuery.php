<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use DateTimeImmutable;
use Nexus\TimeTracking\Contracts\TimesheetQueryInterface;
use Nexus\TimeTracking\ValueObjects\TimesheetSummary;
use Nexus\TimeTracking\Enums\TimesheetStatus;

/**
 * In-memory L1 TimesheetQueryInterface for integration tests.
 */
final class InMemoryTimesheetQuery implements TimesheetQueryInterface
{
    /** @var list<TimesheetSummary> */
    private array $entries = [];

    public function add(TimesheetSummary $entry): void
    {
        $this->entries[] = $entry;
    }

    public function getById(string $timesheetId): ?TimesheetSummary
    {
        foreach ($this->entries as $e) {
            if ($e->id === $timesheetId) {
                return $e;
            }
        }
        return null;
    }

    public function getApprovedHoursForWorkItem(string $workItemId): float
    {
        $total = 0.0;
        foreach ($this->entries as $e) {
            if ($e->workItemId === $workItemId && $e->status->isImmutable()) {
                $total += $e->hours;
            }
        }
        return $total;
    }

    public function getTotalHoursByUserAndDate(string $userId, DateTimeImmutable $date, ?string $excludeTimesheetId = null): float
    {
        $total = 0.0;
        foreach ($this->entries as $e) {
            if ($e->userId !== $userId || $e->date != $date) {
                continue;
            }
            if ($excludeTimesheetId !== null && $e->id === $excludeTimesheetId) {
                continue;
            }
            $total += $e->hours;
        }
        return $total;
    }

    /** @return list<TimesheetSummary> */
    public function getByWorkItem(string $workItemId): array
    {
        $out = [];
        foreach ($this->entries as $e) {
            if ($e->workItemId === $workItemId) {
                $out[] = $e;
            }
        }
        return $out;
    }
}
