<?php

declare(strict_types=1);

namespace Nexus\Storage\Exceptions;

/**
 * Exception thrown when a storage operation fails due to permission issues.
 *
 * @package Nexus\Storage\Exceptions
 */
class PermissionDeniedException extends StorageException
{
    /**
     * Create a new PermissionDeniedException instance.
     *
     * @param string $path The path where permission was denied
     * @param string $operation The operation that was attempted
     * @param \Throwable|null $previous The previous exception for chaining
     */
    public function __construct(string $path, string $operation = 'access', ?\Throwable $previous = null)
    {
        parent::__construct("Permission denied for {$operation} operation on path: {$path}", 403, $previous);
    }
}
