<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for workflow triggers.
 *
 * Triggers automatically instantiate workflows based on events.
 */
interface TriggerInterface
{
    /**
     * Get the trigger type name.
     */
    public function getType(): string;

    /**
     * Check if trigger should fire for given event.
     *
     * @param array<string, mixed> $eventData Event data
     * @param array<string, mixed> $config Trigger configuration
     */
    public function shouldFire(array $eventData, array $config): bool;

    /**
     * Extract workflow data from event.
     *
     * @param array<string, mixed> $eventData Event data
     * @param array<string, mixed> $config Trigger configuration
     * @return array<string, mixed> Workflow data to initialize with
     */
    public function extractWorkflowData(array $eventData, array $config): array;

    /**
     * Get the trigger schema definition.
     *
     * @return array<string, mixed>
     */
    public function getSchema(): array;
}
