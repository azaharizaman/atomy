<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use DateTimeImmutable;

/**
 * SnapshotInterface
 *
 * Represents a snapshot of aggregate state at a specific version.
 *
 * Requirements satisfied:
 * - ARC-EVS-7002: All data structures defined via interfaces
 *
 * @package Nexus\EventStream\Contracts
 */
interface SnapshotInterface
{
    /**
     * Get the aggregate ID this snapshot belongs to
     *
     * @return string
     */
    public function getAggregateId(): string;

    /**
     * Get the version at which this snapshot was taken
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * Get the aggregate state
     *
     * @return array<string, mixed>
     */
    public function getState(): array;

    /**
     * Get the timestamp when this snapshot was created
     *
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get the checksum for snapshot validation
     *
     * @return string
     */
    public function getChecksum(): string;
}
