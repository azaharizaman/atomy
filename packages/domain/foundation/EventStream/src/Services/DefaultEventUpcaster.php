<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\EventUpcasterInterface;
use Nexus\EventStream\Contracts\UpcasterInterface;
use Nexus\EventStream\Exceptions\UpcasterFailedException;

/**
 * Default Event Upcaster
 *
 * Framework-agnostic implementation of event schema migration orchestration.
 * Chains multiple individual upcasters to transform events from any version
 * to the latest version.
 *
 * Features:
 * - Chain of Responsibility pattern for composable transformations
 * - Fail-fast error handling with detailed context
 * - Version gap detection to prevent invalid migrations
 * - Immutable transformations (original events never modified)
 *
 * Example Usage:
 * ```php
 * $upcaster = new DefaultEventUpcaster();
 * $upcaster
 *     ->registerUpcaster(new AccountCreatedV1ToV2Upcaster())
 *     ->registerUpcaster(new AccountCreatedV2ToV3Upcaster());
 *
 * // Upcast from v1 to v3
 * $result = $upcaster->upcastEvent('AccountCreated', 1, $oldEventData);
 * // $result['version'] === 3
 * // $result['data'] === $upcastedData
 * ```
 *
 * Requirements satisfied:
 * - MAI-EVS-7810: Event schema migration orchestration
 * - REL-EVS-7415: Fail-fast upcasting with individual testability
 * - ARC-EVS-7017: Composable, immutable transformation chain
 *
 * @package Nexus\EventStream\Services
 */
final class DefaultEventUpcaster implements EventUpcasterInterface
{
    /**
     * Registered upcasters (stored as flat array for simplicity).
     *
     * @var UpcasterInterface[]
     */
    private array $upcasters = [];

    /**
     * Memoization cache for upcasters by event type.
     * Maps event type to filtered and sorted array of upcasters.
     *
     * @var array<string, UpcasterInterface[]>
     */
    private array $upcasterCache = [];

    public function upcastEvent(string $eventType, int $currentVersion, array $eventData): array
    {
        $upcastersForEvent = $this->getUpcastersForEventType($eventType);

        if (empty($upcastersForEvent)) {
            return ['version' => $currentVersion, 'data' => $eventData];
        }

        $version = $currentVersion;
        $data = $eventData;
        $latestVersion = $this->getLatestVersion($eventType);

        // Apply upcasters in sequence
        while ($version < $latestVersion) {
            $nextUpcaster = null;

            // Find upcaster that transforms from current version
            foreach ($upcastersForEvent as $upcaster) {
                if ($upcaster->supports($eventType, $version)) {
                    $nextUpcaster = $upcaster;
                    break;
                }
            }

            // No upcaster found for current version → version gap
            if ($nextUpcaster === null) {
                throw UpcasterFailedException::versionGap($eventType, $version, $version + 1);
            }

            $targetVersion = $nextUpcaster->getTargetVersion();

            try {
                $data = $nextUpcaster->upcast($data);
                $version = $targetVersion;
            } catch (\Throwable $e) {
                throw new UpcasterFailedException(
                    $eventType,
                    $version,
                    $targetVersion,
                    $e->getMessage(),
                    '',
                    0,
                    $e
                );
            }
        }

        return ['version' => $version, 'data' => $data];
    }

    public function registerUpcaster(UpcasterInterface $upcaster): self
    {
        $this->upcasters[] = $upcaster;
        
        // Clear cache when new upcaster is registered to ensure fresh lookup
        $this->upcasterCache = [];

        return $this;
    }

    public function getUpcastersForEventType(string $eventType): array
    {
        // Return cached result if available
        if (isset($this->upcasterCache[$eventType])) {
            return $this->upcasterCache[$eventType];
        }

        $filtered = [];

        foreach ($this->upcasters as $upcaster) {
            // Check if this upcaster supports any version of this event type
            // We probe versions 1-10 (reasonable range for event schema versioning)
            for ($version = 1; $version <= 10; $version++) {
                if ($upcaster->supports($eventType, $version)) {
                    $filtered[] = $upcaster;
                    break;
                }
            }
        }

        // Sort by target version
        usort($filtered, fn($a, $b) => $a->getTargetVersion() <=> $b->getTargetVersion());

        // Cache the result for subsequent calls
        $this->upcasterCache[$eventType] = $filtered;

        return $filtered;
    }

    public function getLatestVersion(string $eventType): ?int
    {
        $upcasters = $this->getUpcastersForEventType($eventType);

        if (empty($upcasters)) {
            return null;
        }

        $latestVersion = 0;
        foreach ($upcasters as $upcaster) {
            $targetVersion = $upcaster->getTargetVersion();
            if ($targetVersion > $latestVersion) {
                $latestVersion = $targetVersion;
            }
        }

        return $latestVersion;
    }
}
