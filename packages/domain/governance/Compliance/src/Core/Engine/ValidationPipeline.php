<?php

declare(strict_types=1);

namespace Nexus\Compliance\Core\Engine;

use Psr\Log\LoggerInterface;

/**
 * Multi-step validation pipeline for compliance checks.
 * 
 * Allows chaining multiple validators and accumulating validation errors.
 */
final class ValidationPipeline
{
    /**
     * @var array<callable> Validator functions
     */
    private array $validators = [];

    /**
     * @var array<string> Accumulated validation errors
     */
    private array $errors = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Add a validator to the pipeline.
     *
     * @param callable $validator Function that returns array of errors (empty if valid)
     * @return self For method chaining
     */
    public function addValidator(callable $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Execute all validators in the pipeline.
     *
     * @param array<string, mixed> $context The validation context
     * @return array<string> All validation errors (empty if valid)
     */
    public function validate(array $context): array
    {
        $this->errors = [];

        $this->logger->debug("Running validation pipeline", [
            'validator_count' => count($this->validators),
        ]);

        foreach ($this->validators as $index => $validator) {
            $this->logger->debug("Executing validator", ['index' => $index]);
            
            $validatorErrors = $validator($context);
            
            if (!empty($validatorErrors)) {
                $this->logger->debug("Validator produced errors", [
                    'index' => $index,
                    'error_count' => count($validatorErrors),
                ]);
                $this->errors = array_merge($this->errors, $validatorErrors);
            }
        }

        $this->logger->info("Validation pipeline completed", [
            'total_errors' => count($this->errors),
            'is_valid' => empty($this->errors),
        ]);

        return $this->errors;
    }

    /**
     * Check if the last validation passed.
     *
     * @return bool True if no errors were found
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors from the last run.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Reset the pipeline (clear validators and errors).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->validators = [];
        $this->errors = [];
    }

    /**
     * Create a standard field validation function.
     *
     * @param string $field The field to validate
     * @param string $errorMessage The error message if validation fails
     * @return callable Validator function
     */
    public static function requireField(string $field, string $errorMessage): callable
    {
        return function (array $context) use ($field, $errorMessage): array {
            if (!isset($context[$field]) || empty($context[$field])) {
                return [$errorMessage];
            }
            return [];
        };
    }

    /**
     * Create a standard type validation function.
     *
     * @param string $field The field to validate
     * @param string $type Expected type (string, int, bool, array)
     * @param string $errorMessage The error message if validation fails
     * @return callable Validator function
     */
    public static function requireType(string $field, string $type, string $errorMessage): callable
    {
        return function (array $context) use ($field, $type, $errorMessage): array {
            if (!isset($context[$field])) {
                return [];
            }

            $actualType = gettype($context[$field]);
            if ($actualType !== $type) {
                return [$errorMessage];
            }

            return [];
        };
    }
}
