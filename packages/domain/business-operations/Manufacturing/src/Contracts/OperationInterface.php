<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Operation entity.
 *
 * Represents a single operation/step in a routing.
 */
interface OperationInterface
{
    /**
     * Get the unique identifier for this operation.
     */
    public function getId(): string;

    /**
     * Get the parent routing ID.
     */
    public function getRoutingId(): string;

    /**
     * Get the operation sequence number.
     */
    public function getOperationNumber(): int;

    /**
     * Get the operation name/description.
     */
    public function getName(): string;

    /**
     * Get the work center ID where this operation is performed.
     */
    public function getWorkCenterId(): string;

    /**
     * Get the operation type (setup, run, teardown, etc.).
     */
    public function getType(): string;

    /**
     * Get setup time in minutes.
     */
    public function getSetupTime(): float;

    /**
     * Get run time per unit in minutes.
     */
    public function getRunTimePerUnit(): float;

    /**
     * Get teardown time in minutes.
     */
    public function getTeardownTime(): float;

    /**
     * Get the overlap percentage with previous operation (0-100).
     * Allows parallel processing when > 0.
     */
    public function getOverlapPercentage(): float;

    /**
     * Get the queue time before this operation in minutes.
     */
    public function getQueueTime(): float;

    /**
     * Get the move time after this operation in minutes.
     */
    public function getMoveTime(): float;

    /**
     * Check if this is a subcontracting operation.
     */
    public function isSubcontracted(): bool;

    /**
     * Get optional vendor ID for subcontracted operations.
     */
    public function getVendorId(): ?string;

    /**
     * Get optional notes for this operation.
     */
    public function getNotes(): ?string;
}
