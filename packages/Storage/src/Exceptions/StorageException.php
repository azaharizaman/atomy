<?php

declare(strict_types=1);

namespace Nexus\Storage\Exceptions;

use Exception;

/**
 * Base exception for all storage-related errors.
 *
 * All specific storage exceptions extend this base class to allow
 * for comprehensive error handling.
 *
 * @package Nexus\Storage\Exceptions
 */
class StorageException extends Exception
{
    /**
     * Create a new StorageException instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception for chaining
     */
    public function __construct(string $message = 'A storage error occurred', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
