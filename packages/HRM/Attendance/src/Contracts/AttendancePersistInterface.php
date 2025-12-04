<?php

declare(strict_types=1);

namespace Nexus\Attendance\Contracts;

/**
 * Persistence interface for attendance records (CQRS Write Model)
 * 
 * Provides write operations for creating, updating, and deleting attendance records.
 * Follows CQRS principle by separating commands from queries.
 */
interface AttendancePersistInterface
{
    /**
     * Save attendance record (create or update)
     * 
     * @param AttendanceRecordInterface $record Attendance record to save
     * @return AttendanceRecordInterface Saved attendance record
     */
    public function save(AttendanceRecordInterface $record): AttendanceRecordInterface;

    /**
     * Delete attendance record
     * 
     * @param string $id Attendance record ID
     * @return void
     */
    public function delete(string $id): void;
}
