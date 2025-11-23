<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\ProjectionStateRepositoryInterface;

/**
 * In-Memory Projection State Repository
 *
 * Simple in-memory state storage for testing and single-process scenarios.
 * NOT suitable for production (use Redis/Database repository in production).
 *
 * @package Nexus\EventStream\Services
 */
final class InMemoryProjectionStateRepository implements ProjectionStateRepositoryInterface
{
    /**
     * @var array<string, array{event_id: string, processed_at: \DateTimeImmutable}>
     */
    private array $states = [];

    /**
     * {@inheritDoc}
     */
    public function getLastProcessedEventId(string $projectorName): ?string
    {
        return $this->states[$projectorName]['event_id'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastProcessedAt(string $projectorName): ?\DateTimeImmutable
    {
        return $this->states[$projectorName]['processed_at'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function saveState(
        string $projectorName,
        string $eventId,
        \DateTimeImmutable $processedAt
    ): void {
        $this->states[$projectorName] = [
            'event_id' => $eventId,
            'processed_at' => $processedAt,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function resetState(string $projectorName): void
    {
        unset($this->states[$projectorName]);
    }

    /**
     * {@inheritDoc}
     */
    public function hasState(string $projectorName): bool
    {
        return isset($this->states[$projectorName]);
    }

    /**
     * Clear all states (for testing).
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->states = [];
    }
}
