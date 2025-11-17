<?php

declare(strict_types=1);

namespace Nexus\Storage\Exceptions;

/**
 * Exception thrown when a requested file does not exist in storage.
 *
 * @package Nexus\Storage\Exceptions
 */
class FileNotFoundException extends StorageException
{
    /**
     * Create a new FileNotFoundException instance.
     *
     * @param string $path The path to the missing file
     * @param \Throwable|null $previous The previous exception for chaining
     */
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct("File not found at path: {$path}", 404, $previous);
    }
}
