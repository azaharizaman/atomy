<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when a BOM has circular dependencies.
 */
class CircularBomException extends \RuntimeException
{
    /**
     * @var array<string> The circular path
     */
    private array $circularPath;

    /**
     * @param array<string> $path
     */
    public function __construct(
        string $message = 'Circular dependency detected in BOM',
        array $path = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->circularPath = $path;
    }

    /**
     * Create exception with circular path.
     *
     * @param array<string> $path
     */
    public static function withPath(array $path): self
    {
        $pathString = implode(' -> ', $path);
        return new self(
            "Circular dependency detected in BOM: {$pathString}",
            $path
        );
    }

    /**
     * Get the circular dependency path.
     *
     * @return array<string>
     */
    public function getCircularPath(): array
    {
        return $this->circularPath;
    }
}
