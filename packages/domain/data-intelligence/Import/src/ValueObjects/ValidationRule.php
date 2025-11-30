<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Immutable validation rule value object
 * 
 * Defines a validation constraint for imported data.
 */
readonly class ValidationRule
{
    /**
     * @param string $field Field name to validate
     * @param string $type Validation type (e.g., 'required', 'email', 'numeric', 'min', 'max')
     * @param mixed $constraint Validation constraint value (e.g., min value, max length)
     * @param string $message Error message if validation fails
     */
    public function __construct(
        public string $field,
        public string $type,
        public mixed $constraint = null,
        public string $message = ''
    ) {}

    /**
     * Get default error message if none provided
     */
    public function getErrorMessage(): string
    {
        if (!empty($this->message)) {
            return $this->message;
        }

        return match($this->type) {
            'required' => "Field '{$this->field}' is required",
            'email' => "Field '{$this->field}' must be a valid email address",
            'numeric' => "Field '{$this->field}' must be numeric",
            'integer' => "Field '{$this->field}' must be an integer",
            'min' => "Field '{$this->field}' must be at least {$this->constraint}",
            'max' => "Field '{$this->field}' must not exceed {$this->constraint}",
            'min_length' => "Field '{$this->field}' must be at least {$this->constraint} characters",
            'max_length' => "Field '{$this->field}' must not exceed {$this->constraint} characters",
            'date' => "Field '{$this->field}' must be a valid date",
            'boolean' => "Field '{$this->field}' must be true or false",
            'unique' => "Field '{$this->field}' must be unique",
            default => "Field '{$this->field}' failed validation: {$this->type}",
        };
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'type' => $this->type,
            'constraint' => $this->constraint,
            'message' => $this->getErrorMessage(),
        ];
    }
}
