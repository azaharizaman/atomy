<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Services;

use Nexus\Sequencing\Contracts\ReservationRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;

/**
 * Service for reserving and managing temporary number allocations.
 */
final readonly class ReservationService
{
    public function __construct(
        private SequenceRepositoryInterface $sequenceRepository,
        private ReservationRepositoryInterface $reservationRepository,
        private BulkGeneratorService $bulkGenerator,
    ) {}

    /**
     * Reserve a batch of numbers with TTL.
     *
     * @param string $sequenceName
     * @param int $count Number of sequences to reserve
     * @param int $ttlMinutes Time-to-live in minutes
     * @param string|null $scopeIdentifier
     * @param array<string, string|int> $contextVariables
     * @return array{reservation_id: string, numbers: string[]}
     */
    public function reserve(
        string $sequenceName,
        int $count,
        int $ttlMinutes = 30,
        ?string $scopeIdentifier = null,
        array $contextVariables = []
    ): array {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);

        // Generate numbers atomically
        $numbers = $this->bulkGenerator->generateBulk(
            $sequenceName,
            $count,
            $scopeIdentifier,
            $contextVariables
        );

        // Reserve them
        $reservationId = $this->reservationRepository->reserve($sequence, $numbers, $ttlMinutes);

        return [
            'reservation_id' => $reservationId,
            'numbers' => $numbers,
        ];
    }

    /**
     * Release reserved numbers back to the pool.
     *
     * @param string[] $numbers
     */
    public function release(
        string $sequenceName,
        array $numbers,
        ?string $scopeIdentifier = null
    ): void {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $this->reservationRepository->release($sequence, $numbers);
    }

    /**
     * Finalize (consume) reserved numbers.
     *
     * @param string[] $numbers
     */
    public function finalize(
        string $sequenceName,
        array $numbers,
        ?string $scopeIdentifier = null
    ): void {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        $this->reservationRepository->finalize($sequence, $numbers);
    }

    /**
     * Get all active reservations for a sequence.
     *
     * @return array<string, mixed>[]
     */
    public function getActiveReservations(string $sequenceName, ?string $scopeIdentifier = null): array
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scopeIdentifier);
        return $this->reservationRepository->getActiveReservations($sequence);
    }

    /**
     * Clean up expired reservations (typically called by scheduler).
     *
     * @return int Number of reservations released
     */
    public function releaseExpired(): int
    {
        return $this->reservationRepository->releaseExpired();
    }
}
