<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Event Upcaster Interface
 *
 * Framework-agnostic contract for orchestrating event schema migrations.
 * Chains multiple individual upcasters to transform events from any version
 * to the latest version.
 *
 * Pattern:
 * - Chain of Responsibility: Applies upcasters in sequence
 * - Fail-Fast: Throws exception if any upcaster fails
 * - Version Validation: Ensures no version gaps in the chain
 *
 * Example Usage:
 * ```php
 * $upcaster = new DefaultEventUpcaster([
 *     new AccountCreatedV1ToV2Upcaster(),
 *     new AccountCreatedV2ToV3Upcaster(),
 * ]);
 *
 * // Upcast from v1 to v3
 * $upcastedEvent = $upcaster->upcastEvent('AccountCreated', 1, $eventData);
 * ```
 *
 * Requirements satisfied:
 * - MAI-EVS-7810: Event schema migration orchestration
 * - REL-EVS-7415: Fail-fast upcasting with validation
 * - ARC-EVS-7017: Composable upcaster chain
 *
 * @package Nexus\EventStream\Contracts
 */
interface EventUpcasterInterface
{
    /**
     * Upcast an event from its current version to the latest version.
     *
     * This method applies all necessary upcasters in sequence to transform
     * the event data to the latest version.
     *
     * If no upcasters are registered for this event type and version,
     * the original data is returned unchanged.
     *
     * @param string $eventType The event type (e.g., 'AccountCreated')
     * @param int $currentVersion The current event version
     * @param array<string, mixed> $eventData The event data to upcast
     * @return array{version: int, data: array<string, mixed>} Upcasted version and data
     * @throws \Nexus\EventStream\Exceptions\UpcasterFailedException If upcasting fails
     */
    public function upcastEvent(string $eventType, int $currentVersion, array $eventData): array;

    /**
     * Register an upcaster in the chain.
     *
     * Upcasters are applied in the order they are registered.
     *
     * @param UpcasterInterface $upcaster The upcaster to register
     * @return self Fluent interface
     * @throws \InvalidArgumentException If upcaster creates version gap
     */
    public function registerUpcaster(UpcasterInterface $upcaster): self;

    /**
     * Get all registered upcasters for a specific event type.
     *
     * @param string $eventType The event type
     * @return UpcasterInterface[] Array of upcasters sorted by version
     */
    public function getUpcastersForEventType(string $eventType): array;

    /**
     * Get the latest version for a specific event type.
     *
     * @param string $eventType The event type
     * @return int|null The latest version, or null if no upcasters registered
     */
    public function getLatestVersion(string $eventType): ?int;
}
