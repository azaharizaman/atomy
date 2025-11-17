<?php

declare(strict_types=1);

namespace Nexus\Setting\Exceptions;

use Exception;

/**
 * Exception thrown when setting validation fails.
 */
class SettingValidationException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $key The setting key
     * @param string $reason The validation failure reason
     */
    public function __construct(string $key, string $reason)
    {
        parent::__construct("Setting validation failed for '{$key}': {$reason}");
    }

    /**
     * Get the setting key that failed validation.
     */
    public function getKey(): string
    {
        preg_match("/for '(.+?)'/", $this->getMessage(), $matches);

        return $matches[1] ?? '';
    }

    /**
     * Get the validation failure reason.
     */
    public function getReason(): string
    {
        preg_match("/: (.+)$/", $this->getMessage(), $matches);

        return $matches[1] ?? '';
    }
}
