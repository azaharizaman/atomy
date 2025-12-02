<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that can be formatted for display.
 */
interface Formattable
{
    /**
     * Format for display.
     * 
     * @param array<string, mixed> $options Formatting options
     */
    public function format(array $options = []): string;
}
