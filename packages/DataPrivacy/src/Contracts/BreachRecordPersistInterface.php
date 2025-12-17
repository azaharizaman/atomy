<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\BreachRecord;

/**
 * Write operations for breach records (CQRS Command Model).
 */
interface BreachRecordPersistInterface
{
    /**
     * Save a new breach record.
     *
     * @return string The generated breach ID
     */
    public function save(BreachRecord $breach): string;

    /**
     * Update an existing breach record.
     */
    public function update(BreachRecord $breach): void;

    /**
     * Delete a breach record.
     */
    public function delete(string $id): void;

    /**
     * Mark breach as notified to regulator.
     *
     * @return BreachRecord The updated breach record
     */
    public function markRegulatoryNotified(
        string $id,
        \DateTimeImmutable $notifiedAt,
        ?string $referenceNumber = null
    ): BreachRecord;

    /**
     * Mark breach as notified to affected individuals.
     *
     * @return BreachRecord The updated breach record
     */
    public function markIndividualsNotified(
        string $id,
        \DateTimeImmutable $notifiedAt,
        int $individualsNotified
    ): BreachRecord;

    /**
     * Mark breach as resolved.
     *
     * @return BreachRecord The updated breach record
     */
    public function markResolved(
        string $id,
        \DateTimeImmutable $resolvedAt,
        string $resolutionDetails
    ): BreachRecord;

    /**
     * Add evidence to breach record.
     */
    public function addEvidence(
        string $id,
        string $evidenceReference,
        string $description
    ): void;

    /**
     * Update records affected count.
     *
     * @return BreachRecord The updated breach record
     */
    public function updateRecordsAffected(
        string $id,
        int $recordsAffected
    ): BreachRecord;
}
