<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when file is not found in storage.
 */
class FileNotFoundException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $path,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function atPath(string $path): self
    {
        return new self(
            message: "File not found at path: {$path}",
            path: $path
        );
    }
}
