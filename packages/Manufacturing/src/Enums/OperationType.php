<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * Operation Type enum.
 *
 * Defines the type of manufacturing operation in a routing.
 */
enum OperationType: string
{
    /**
     * Standard production operation.
     */
    case PRODUCTION = 'production';

    /**
     * Quality inspection operation.
     */
    case INSPECTION = 'inspection';

    /**
     * Material movement/transport operation.
     */
    case MOVE = 'move';

    /**
     * Queue/wait time between operations.
     */
    case QUEUE = 'queue';

    /**
     * Setup operation (machine preparation).
     */
    case SETUP = 'setup';

    /**
     * Subcontracted operation (external processing).
     */
    case SUBCONTRACT = 'subcontract';

    /**
     * Packaging operation.
     */
    case PACKAGING = 'packaging';

    /**
     * Rework operation.
     */
    case REWORK = 'rework';

    /**
     * Check if operation type consumes machine capacity.
     */
    public function consumesCapacity(): bool
    {
        return match ($this) {
            self::PRODUCTION, self::SETUP, self::INSPECTION, self::PACKAGING, self::REWORK => true,
            self::MOVE, self::QUEUE, self::SUBCONTRACT => false,
        };
    }

    /**
     * Check if operation type incurs labor cost.
     */
    public function incursLaborCost(): bool
    {
        return match ($this) {
            self::PRODUCTION, self::SETUP, self::INSPECTION, self::PACKAGING, self::REWORK => true,
            self::MOVE => true,
            self::QUEUE, self::SUBCONTRACT => false,
        };
    }

    /**
     * Check if operation type is value-adding.
     */
    public function isValueAdding(): bool
    {
        return match ($this) {
            self::PRODUCTION, self::PACKAGING => true,
            default => false,
        };
    }

    /**
     * Check if operation can be parallelized.
     */
    public function canBeParallel(): bool
    {
        return match ($this) {
            self::INSPECTION, self::PACKAGING => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRODUCTION => 'Production',
            self::INSPECTION => 'Inspection',
            self::MOVE => 'Move',
            self::QUEUE => 'Queue',
            self::SETUP => 'Setup',
            self::SUBCONTRACT => 'Subcontract',
            self::PACKAGING => 'Packaging',
            self::REWORK => 'Rework',
        };
    }

    /**
     * Get description.
     */
    public function description(): string
    {
        return match ($this) {
            self::PRODUCTION => 'Standard manufacturing operation',
            self::INSPECTION => 'Quality control and inspection',
            self::MOVE => 'Material movement between stations',
            self::QUEUE => 'Wait time between operations',
            self::SETUP => 'Machine setup and preparation',
            self::SUBCONTRACT => 'External processing by third party',
            self::PACKAGING => 'Product packaging operation',
            self::REWORK => 'Correction of defects',
        };
    }
}
