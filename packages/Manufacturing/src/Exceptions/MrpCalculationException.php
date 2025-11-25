<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when MRP calculation fails.
 */
class MrpCalculationException extends \RuntimeException
{
    /**
     * @var array<string> Errors encountered
     */
    private array $errors;

    /**
     * @param array<string> $errors
     */
    public function __construct(
        string $message = 'MRP calculation failed',
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public static function missingBom(string $productId): self
    {
        return new self(
            "MRP calculation failed: No BOM found for product '{$productId}'",
            ["Missing BOM for product '{$productId}'"]
        );
    }

    public static function missingLeadTime(string $productId): self
    {
        return new self(
            "MRP calculation failed: No lead time defined for product '{$productId}'",
            ["Missing lead time for product '{$productId}'"]
        );
    }

    /**
     * Create exception with multiple errors.
     *
     * @param array<string> $errors
     */
    public static function withErrors(array $errors): self
    {
        $count = count($errors);
        return new self(
            "MRP calculation failed with {$count} error(s)",
            $errors
        );
    }

    /**
     * Get calculation errors.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
