<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

/**
 * Sequence Generator Interface
 *
 * Wrapper for Nexus Sequencing package to generate unique IDs
 */
interface SequenceGeneratorInterface
{
    /**
     * Generate a unique ID with the given prefix
     */
    public function generateId(string $prefix): string;
}
