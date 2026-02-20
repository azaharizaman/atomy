<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

/**
 * Simple Sequence Generator Implementation
 *
 * Uses UUID v4 for ID generation. In production, this should be replaced
 * with a proper implementation that uses Nexus\Sequencing\Services\SequenceManager
 */
final readonly class SimpleSequenceGenerator implements SequenceGeneratorInterface
{
    public function generateId(string $prefix): string
    {
        $uuid = sprintf(
            '%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
        
        return sprintf('%s-%s', $prefix, $uuid);
    }
}
