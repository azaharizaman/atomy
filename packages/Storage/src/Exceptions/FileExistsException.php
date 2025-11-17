<?php

declare(strict_types=1);

namespace Nexus\Storage\Exceptions;

/**
 * Exception thrown when a file already exists and overwriting is not allowed.
 *
 * @package Nexus\Storage\Exceptions
 */
class FileExistsException extends StorageException
{
    /**
     * Create a new FileExistsException instance.
     *
     * @param string $path The path to the existing file
     * @param \Throwable|null $previous The previous exception for chaining
     */
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct("File already exists at path: {$path}", 409, $previous);
    }
}
