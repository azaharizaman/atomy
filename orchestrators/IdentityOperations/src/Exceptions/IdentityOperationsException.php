<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Exceptions;

/**
 * Base exception for all IdentityOperations errors.
 */
class IdentityOperationsException extends \RuntimeException
{
    private array $context;

    public function __construct(string $message, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
