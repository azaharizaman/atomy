<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Contracts;

/**
 * ID Generator Interface
 * 
 * Interface for generating unique identifiers (ULIDs, UUIDs).
 */
interface IdGeneratorInterface
{
    /**
     * Generate a new unique identifier
     */
    public function generate(): string;
}
