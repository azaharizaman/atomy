<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use DateTimeImmutable;

/**
 * SnapshotRepositoryInterface
 *
 * Contract for storing and retrieving aggregate snapshots.
 * Snapshots optimize replay performance by caching aggregate state.
 *
 * Requirements satisfied:
 * - FUN-EVS-7204: Define SnapshotRepositoryInterface for storing/retrieving snapshots
 * - FUN-EVS-7209: Create snapshots automatically after N events
 * - FUN-EVS-7210: Restore aggregate from latest snapshot + subsequent events
 *
 * @package Nexus\EventStream\Contracts
 */
interface SnapshotRepositoryInterface
{
    /**
     * Save a snapshot of aggregate state
     *
     * @param string $aggregateId The aggregate identifier
     * @param int $version The version at which this snapshot was taken
     * @param array<string, mixed> $state The aggregate state
     * @return void
     */
    public function save(string $aggregateId, int $version, array $state): void;

    /**
     * Get the latest snapshot for an aggregate
     *
     * @param string $aggregateId The aggregate identifier
     * @return SnapshotInterface|null
     */
    public function getLatest(string $aggregateId): ?SnapshotInterface;

    /**
     * Get the latest snapshot for an aggregate (returns array)
     *
     * @param string $aggregateId The aggregate identifier
     * @return array<string, mixed>|null
     */
    public function getLatestSnapshot(string $aggregateId): ?array;

    /**
     * Get the latest snapshot at or before a specific timestamp
     *
     * @param string $aggregateId The aggregate identifier
     * @param DateTimeImmutable $timestamp The point in time
     * @return array<string, mixed>|null
     */
    public function getLatestSnapshotBefore(string $aggregateId, DateTimeImmutable $timestamp): ?array;

    /**
     * Get a snapshot at a specific timestamp
     *
     * @param string $aggregateId The aggregate identifier
     * @param DateTimeImmutable $timestamp The point in time
     * @return array<string, mixed>|null
     */
    public function getSnapshotAt(string $aggregateId, DateTimeImmutable $timestamp): ?array;

    /**
     * Get a snapshot at or before a specific version
     *
     * @param string $aggregateId The aggregate identifier
     * @param int $version The version
     * @return SnapshotInterface|null
     */
    public function getAtVersion(string $aggregateId, int $version): ?SnapshotInterface;

    /**
     * Delete all snapshots older than a specific date
     *
     * @param DateTimeImmutable $before Delete snapshots before this date
     * @return int Number of snapshots deleted
     */
    public function deleteOlderThan(DateTimeImmutable $before): int;

    /**
     * Check if a snapshot exists for an aggregate
     *
     * @param string $aggregateId The aggregate identifier
     * @return bool
     */
    public function exists(string $aggregateId): bool;
}
