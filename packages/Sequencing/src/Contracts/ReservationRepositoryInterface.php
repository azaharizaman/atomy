<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Repository interface for managing sequence number reservations.
 *
 * Reservations allow numbers to be temporarily held without finalizing them.
 */
interface ReservationRepositoryInterface
{
    /**
     * Reserve a batch of numbers with a TTL.
     *
     * @param string[] $numbers
     * @return string Reservation ID (UUID)
     */
    public function reserve(SequenceInterface $sequence, array $numbers, int $ttlMinutes): string;

    /**
     * Release reserved numbers back to the pool.
     *
     * @param string[] $numbers
     */
    public function release(SequenceInterface $sequence, array $numbers): void;

    /**
     * Finalize (consume) reserved numbers.
     *
     * @param string[] $numbers
     */
    public function finalize(SequenceInterface $sequence, array $numbers): void;

    /**
     * Get all active reservations for a sequence.
     *
     * @return array<string, mixed>[]
     */
    public function getActiveReservations(SequenceInterface $sequence): array;

    /**
     * Release expired reservations automatically.
     *
     * @return int Number of reservations released
     */
    public function releaseExpired(): int;

    /**
     * Check if a number is currently reserved.
     */
    public function isReserved(SequenceInterface $sequence, string $number): bool;
}
