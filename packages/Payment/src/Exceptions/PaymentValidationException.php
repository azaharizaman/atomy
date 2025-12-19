<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when payment validation fails.
 */
final class PaymentValidationException extends PaymentException
{
    /**
     * @param string $message Validation error message
     * @param array<string, array<string>> $errors Field-level validation errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            422,
            $previous,
            ['errors' => $errors],
        );
    }

    /**
     * Get all validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field.
     *
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if there are errors for a specific field.
     */
    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Create from an array of errors.
     *
     * @param array<string, array<string>> $errors
     */
    public static function fromErrors(array $errors): self
    {
        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = sprintf('%s: %s', $field, $error);
            }
        }

        return new self(
            'Payment validation failed: ' . implode('; ', $messages),
            $errors,
        );
    }

    /**
     * Create for a single field error.
     */
    public static function forField(string $field, string $message): self
    {
        return new self(
            sprintf('Payment validation failed: %s - %s', $field, $message),
            [$field => [$message]],
        );
    }
}
