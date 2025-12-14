<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception thrown when an unsupported bank file format is requested.
 */
final class UnsupportedBankFileFormatException extends \RuntimeException
{
    public function __construct(string $message = 'Unsupported bank file format', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
