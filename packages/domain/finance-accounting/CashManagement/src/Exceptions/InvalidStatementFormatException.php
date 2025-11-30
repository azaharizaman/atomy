<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Exceptions;

use RuntimeException;

/**
 * Invalid Statement Format Exception
 *
 * Thrown when a bank statement file has invalid format.
 */
class InvalidStatementFormatException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid bank statement format',
        private readonly ?string $fileName = null,
        private readonly ?int $lineNumber = null
    ) {
        parent::__construct($message);
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    public static function missingColumn(string $columnName, string $fileName): self
    {
        return new self(
            message: sprintf('Missing required column "%s" in file "%s"', $columnName, $fileName),
            fileName: $fileName
        );
    }

    public static function invalidDate(string $date, int $lineNumber, string $fileName): self
    {
        return new self(
            message: sprintf('Invalid date format "%s" at line %d in file "%s"', $date, $lineNumber, $fileName),
            fileName: $fileName,
            lineNumber: $lineNumber
        );
    }

    public static function invalidAmount(string $amount, int $lineNumber, string $fileName): self
    {
        return new self(
            message: sprintf('Invalid amount "%s" at line %d in file "%s"', $amount, $lineNumber, $fileName),
            fileName: $fileName,
            lineNumber: $lineNumber
        );
    }
}
