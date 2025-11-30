<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Immutable field mapping value object
 * 
 * Maps source field to target field with optional transformations.
 */
readonly class FieldMapping
{
    /**
     * @param string $sourceField Source field name from import file
     * @param string $targetField Target field name in domain entity
     * @param bool $required Whether field is required
     * @param mixed $defaultValue Default value if source is empty/null
     * @param array<string> $transformations Transformation rules to apply (e.g., ['trim', 'upper'])
     */
    public function __construct(
        public string $sourceField,
        public string $targetField,
        public bool $required = false,
        public mixed $defaultValue = null,
        public array $transformations = []
    ) {}

    /**
     * Check if field has transformations
     */
    public function hasTransformations(): bool
    {
        return !empty($this->transformations);
    }

    /**
     * Get transformation count
     */
    public function getTransformationCount(): int
    {
        return count($this->transformations);
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'source_field' => $this->sourceField,
            'target_field' => $this->targetField,
            'required' => $this->required,
            'default_value' => $this->defaultValue,
            'transformations' => $this->transformations,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sourceField: $data['source_field'],
            targetField: $data['target_field'],
            required: $data['required'] ?? false,
            defaultValue: $data['default_value'] ?? null,
            transformations: $data['transformations'] ?? []
        );
    }
}
