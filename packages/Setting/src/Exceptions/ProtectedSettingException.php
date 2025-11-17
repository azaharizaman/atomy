<?php

declare(strict_types=1);

namespace Nexus\Setting\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to override a protected setting.
 */
class ProtectedSettingException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $key The protected setting key
     * @param string $layer The layer attempting to override
     */
    public function __construct(string $key, string $layer)
    {
        parent::__construct("Setting is protected and cannot be overridden at {$layer} layer: {$key}");
    }

    /**
     * Get the setting key that is protected.
     */
    public function getKey(): string
    {
        preg_match('/: (.+)$/', $this->getMessage(), $matches);

        return $matches[1] ?? '';
    }

    /**
     * Get the layer that attempted to override.
     */
    public function getLayer(): string
    {
        preg_match('/at (\w+) layer/', $this->getMessage(), $matches);

        return $matches[1] ?? '';
    }
}
