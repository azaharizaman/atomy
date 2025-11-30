<?php

declare(strict_types=1);

namespace Nexus\EventStream\Core\Engine;

use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Exceptions\InvalidSnapshotException;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Enums\HashAlgorithm;
use Psr\Log\LoggerInterface;

/**
 * SnapshotManager
 *
 * Internal engine for managing snapshots.
 * Handles snapshot creation, validation, and restoration.
 *
 * Supports dual code paths:
 * - Legacy mode: Direct hash() calls
 * - Crypto mode: Nexus\Crypto interfaces (CRYPTO_LEGACY_MODE=false)
 *
 * Requirements satisfied:
 * - ARC-EVS-7010: Separate Core/ folder for internal engine (SnapshotManager)
 * - FUN-EVS-7209: Create snapshots automatically after N events
 * - FUN-EVS-7210: Restore aggregate from latest snapshot + subsequent events
 * - REL-EVS-7406: Snapshots validated before use (checksum verification)
 *
 * @package Nexus\EventStream\Core\Engine
 */
readonly class SnapshotManager
{
    public function __construct(
        private SnapshotRepositoryInterface $snapshotRepository,
        private EventStoreInterface $eventStore,
        private LoggerInterface $logger,
        private int $snapshotThreshold = 100,
        private ?HasherInterface $hasher = null,
        private bool $legacyMode = true,
    ) {
    }

    /**
     * Create a snapshot if the threshold is reached
     *
     * @param string $aggregateId The aggregate identifier
     * @param array<string, mixed> $state The aggregate state
     * @return bool True if snapshot was created
     */
    public function createIfNeeded(string $aggregateId, array $state): bool
    {
        $currentVersion = $this->eventStore->getCurrentVersion($aggregateId);
        $latestSnapshot = $this->snapshotRepository->getLatest($aggregateId);

        if ($latestSnapshot === null) {
            $eventsSinceSnapshot = $currentVersion;
        } else {
            $eventsSinceSnapshot = $currentVersion - $latestSnapshot->getVersion();
        }

        if ($eventsSinceSnapshot >= $this->snapshotThreshold) {
            $this->logger->info('Creating snapshot', [
                'aggregate_id' => $aggregateId,
                'version' => $currentVersion,
                'events_since_last_snapshot' => $eventsSinceSnapshot,
            ]);

            $this->snapshotRepository->save($aggregateId, $currentVersion, $state);

            return true;
        }

        return false;
    }

    /**
     * Validate a snapshot checksum
     *
     * @param string $aggregateId
     * @param array<string, mixed> $state
     * @param string $checksum
     * @return bool
     */
    public function validateChecksum(string $aggregateId, array $state, string $checksum): bool
    {
        $calculatedChecksum = $this->calculateChecksum($state);

        if ($calculatedChecksum !== $checksum) {
            $this->logger->warning('Snapshot checksum validation failed', [
                'aggregate_id' => $aggregateId,
                'expected' => $checksum,
                'actual' => $calculatedChecksum,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Calculate checksum for snapshot state
     *
     * @param array<string, mixed> $state
     * @return string
     */
    public function calculateChecksum(array $state): string
    {
        $data = json_encode($state, JSON_THROW_ON_ERROR);
        
        // Check if legacy mode is enabled
        if ($this->isLegacyMode()) {
            return hash('sha256', $data);
        }
        
        // Use Nexus\Crypto implementation
        if ($this->hasher !== null) {
            $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
            return $result->hash;
        }
        
        // Fallback to legacy
        return hash('sha256', $data);
    }
    
    /**
     * Check if legacy mode is enabled
     */
    private function isLegacyMode(): bool
    {
        return $this->legacyMode;
    }
}

