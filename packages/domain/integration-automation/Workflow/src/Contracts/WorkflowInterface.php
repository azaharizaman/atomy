<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Represents a workflow instance.
 *
 * A workflow is tied to a single model instance (subject) and tracks
 * its progression through defined states.
 */
interface WorkflowInterface
{
    /**
     * Get the workflow instance unique identifier.
     */
    public function getId(): string;

    /**
     * Get the workflow definition identifier.
     */
    public function getDefinitionId(): string;

    /**
     * Get the current state of the workflow.
     */
    public function getCurrentState(): string;

    /**
     * Get the subject model type (e.g., 'App\Models\PurchaseOrder').
     */
    public function getSubjectType(): string;

    /**
     * Get the subject model identifier.
     */
    public function getSubjectId(): string;

    /**
     * Get workflow data as associative array.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Set workflow data.
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void;

    /**
     * Get specific data value by key.
     */
    public function getDataValue(string $key, mixed $default = null): mixed;

    /**
     * Check if workflow is locked (prevents transitions).
     */
    public function isLocked(): bool;

    /**
     * Get workflow creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Get completion timestamp (null if not completed).
     */
    public function getCompletedAt(): ?\DateTimeInterface;
}
