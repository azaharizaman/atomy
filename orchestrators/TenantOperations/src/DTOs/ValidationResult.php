<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

use Nexus\Common\Contracts\OperationResultInterface;

/**
 * Result of a validation operation.
 * 
 * Implements OperationResultInterface for standardization.
 */
final readonly class ValidationResult implements OperationResultInterface
{
    /**
     * @param array<int, array{rule: string, message: string, severity: string}> $errors
     */
    public function __construct(
        public bool $passed,
        public array $errors = [],
    ) {}

    /**
     * @inheritDoc
     */
    public function isSuccess(): bool
    {
        return $this->passed;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return $this->passed ? 'Validation passed' : 'Validation failed';
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getIssues(): array
    {
        return array_map(fn($e) => [
            'rule' => $e['rule'],
            'message' => $e['message']
        ], $this->errors);
    }

    /**
     * Create a passed validation result.
     */
    public static function passed(): self
    {
        return new self(passed: true);
    }

    /**
     * Create a failed validation result.
     *
     * @param array<int, array{rule: string, message: string, severity: string}> $errors
     */
    public static function failed(array $errors): self
    {
        return new self(passed: false, errors: $errors);
    }

    /**
     * Add an error to the result.
     *
     * @return static
     */
    public function withError(string $rule, string $message, string $severity = 'error'): self
    {
        $errors = $this->errors;
        $errors[] = [
            'rule' => $rule,
            'message' => $message,
            'severity' => $severity,
        ];

        return new self(passed: false, errors: $errors);
    }
}
