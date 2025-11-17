<?php

declare(strict_types=1);

namespace Nexus\Storage\Exceptions;

/**
 * Exception thrown when an invalid file path is provided.
 *
 * This exception is used to prevent directory traversal attacks
 * and other path-related security issues.
 *
 * @package Nexus\Storage\Exceptions
 */
class InvalidPathException extends StorageException
{
    /**
     * Create a new InvalidPathException instance.
     *
     * @param string $path The invalid path
     * @param string $reason The reason why the path is invalid
     * @param \Throwable|null $previous The previous exception for chaining
     */
    public function __construct(string $path, string $reason = 'Invalid path format', ?\Throwable $previous = null)
    {
        parent::__construct("Invalid path '{$path}': {$reason}", 400, $previous);
    }

    /**
     * Create an exception for directory traversal attempts.
     *
     * @param string $path The path containing directory traversal patterns
     *
     * @return self
     */
    public static function directoryTraversal(string $path): self
    {
        return new self($path, 'Path contains directory traversal patterns (..)');
    }

    /**
     * Create an exception for absolute paths when relative paths are expected.
     *
     * @param string $path The absolute path
     *
     * @return self
     */
    public static function absolutePathNotAllowed(string $path): self
    {
        return new self($path, 'Absolute paths are not allowed');
    }
}
