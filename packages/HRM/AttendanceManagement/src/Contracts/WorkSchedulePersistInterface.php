<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Contracts;

/**
 * Persistence interface for work schedules (CQRS Write Model)
 * 
 * Provides write operations for creating, updating, and deleting work schedules.
 * Follows CQRS principle by separating commands from queries.
 */
interface WorkSchedulePersistInterface
{
    /**
     * Save work schedule (create or update)
     * 
     * @param WorkScheduleInterface $schedule Work schedule to save
     * @return WorkScheduleInterface Saved work schedule
     */
    public function save(WorkScheduleInterface $schedule): WorkScheduleInterface;

    /**
     * Delete work schedule
     * 
     * @param string $id Schedule ID
     * @return void
     */
    public function delete(string $id): void;
}
