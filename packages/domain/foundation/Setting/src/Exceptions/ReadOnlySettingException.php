<?php

declare(strict_types=1);

namespace Nexus\Setting\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to modify a read-only setting.
 */
class ReadOnlySettingException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $key The read-only setting key
     */
    public function __construct(string $key)
    {
        parent::__construct("Setting is read-only and cannot be modified: {$key}");
    }

    /**
     * Get the setting key that is read-only.
     */
    public function getKey(): string
    {
        return str_replace('Setting is read-only and cannot be modified: ', '', $this->getMessage());
    }
}
