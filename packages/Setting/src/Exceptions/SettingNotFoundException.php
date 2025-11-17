<?php

declare(strict_types=1);

namespace Nexus\Setting\Exceptions;

use Exception;

/**
 * Exception thrown when a requested setting is not found.
 */
class SettingNotFoundException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $key The setting key that was not found
     */
    public function __construct(string $key)
    {
        parent::__construct("Setting not found: {$key}");
    }

    /**
     * Get the setting key that was not found.
     */
    public function getKey(): string
    {
        // Extract key from message
        return str_replace('Setting not found: ', '', $this->getMessage());
    }
}
