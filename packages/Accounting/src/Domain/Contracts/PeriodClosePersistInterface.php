<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\PeriodCloseRecord;
use Nexus\Accounting\Domain\Exceptions\PeriodCloseException;

/**
 * Persistence interface for period close records.
 *
 * Follows CQRS pattern - write operations only.
 */
interface PeriodClosePersistInterface
{
    /**
     * Save a period close record.
     *
     * @param PeriodCloseRecord $record The record to save
     * @return PeriodCloseRecord The saved record
     * @throws PeriodCloseException If save operation fails
     */
    public function save(PeriodCloseRecord $record): PeriodCloseRecord;

    /**
     * Delete a period close record (reopen period).
     *
     * @param string $id Record ID to delete
     * @throws PeriodCloseException If delete operation fails
     */
    public function delete(string $id): void;
}
