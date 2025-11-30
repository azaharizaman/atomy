<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Upcaster Interface
 *
 * Framework-agnostic contract for transforming events from one version to another.
 * Each upcaster handles a specific version transformation (e.g., v1 → v2, v2 → v3).
 *
 * Pattern:
 * - Single Responsibility: One upcaster per version transformation
 * - Immutable: Original events are never modified, new events are created
 * - Composable: Multiple upcasters can be chained together
 *
 * Example Usage:
 * ```php
 * class AccountCreatedV1ToV2Upcaster implements UpcasterInterface
 * {
 *     public function upcast(array $eventData): array
 *     {
 *         return array_merge($eventData, ['currency' => 'MYR']);
 *     }
 *
 *     public function supports(string $eventType, int $version): bool
 *     {
 *         return $eventType === 'AccountCreated' && $version === 1;
 *     }
 *
 *     public function getTargetVersion(): int
 *     {
 *         return 2;
 *     }
 * }
 * ```
 *
 * Requirements satisfied:
 * - MAI-EVS-7810: Event schema migration (upcasting)
 * - REL-EVS-7415: Upcasters MUST be individually testable
 * - ARC-EVS-7017: Immutable event transformation
 *
 * @package Nexus\EventStream\Contracts
 */
interface UpcasterInterface
{
    /**
     * Transform event data from one version to the next.
     *
     * This method MUST NOT modify the original event data.
     * It MUST return a new array with the transformed data.
     *
     * @param array<string, mixed> $eventData The event data to transform
     * @return array<string, mixed> The transformed event data
     * @throws \Nexus\EventStream\Exceptions\UpcasterFailedException If transformation fails
     */
    public function upcast(array $eventData): array;

    /**
     * Determine if this upcaster supports the given event type and version.
     *
     * @param string $eventType The event type (e.g., 'AccountCreated')
     * @param int $version The current event version
     * @return bool True if this upcaster can transform this event
     */
    public function supports(string $eventType, int $version): bool;

    /**
     * Get the target version this upcaster transforms to.
     *
     * For example, if this upcaster transforms from v1 → v2, return 2.
     *
     * @return int The target version number
     */
    public function getTargetVersion(): int;
}
