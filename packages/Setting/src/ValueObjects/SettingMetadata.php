<?php

declare(strict_types=1);

namespace Nexus\Setting\ValueObjects;

/**
 * Represents metadata for a setting definition.
 *
 * This immutable value object contains all metadata required to
 * describe, validate, and render a setting in the UI.
 */
final readonly class SettingMetadata
{
    /**
     * Create new setting metadata.
     *
     * @param string $key The unique setting key
     * @param string $type The data type (string, int, bool, float, array, json)
     * @param mixed $defaultValue The default value if not set
     * @param string|null $description Human-readable description
     * @param array<string, mixed> $validationRules Validation rules (min, max, pattern, etc.)
     * @param bool $isReadOnly Whether the setting is immutable
     * @param bool $isProtected Whether the setting is protected from override
     * @param bool $isEncrypted Whether the setting should be encrypted
     * @param string|null $group The logical group/namespace this setting belongs to
     * @param array<string, mixed> $uiMetadata Additional UI rendering metadata
     */
    public function __construct(
        public string $key,
        public string $type = 'string',
        public mixed $defaultValue = null,
        public ?string $description = null,
        public array $validationRules = [],
        public bool $isReadOnly = false,
        public bool $isProtected = false,
        public bool $isEncrypted = false,
        public ?string $group = null,
        public array $uiMetadata = [],
    ) {
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'] ?? '',
            type: $data['type'] ?? 'string',
            defaultValue: $data['default_value'] ?? $data['defaultValue'] ?? null,
            description: $data['description'] ?? null,
            validationRules: $data['validation_rules'] ?? $data['validationRules'] ?? [],
            isReadOnly: $data['is_readonly'] ?? $data['isReadOnly'] ?? false,
            isProtected: $data['is_protected'] ?? $data['isProtected'] ?? false,
            isEncrypted: $data['is_encrypted'] ?? $data['isEncrypted'] ?? false,
            group: $data['group'] ?? null,
            uiMetadata: $data['ui_metadata'] ?? $data['uiMetadata'] ?? [],
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'default_value' => $this->defaultValue,
            'description' => $this->description,
            'validation_rules' => $this->validationRules,
            'is_readonly' => $this->isReadOnly,
            'is_protected' => $this->isProtected,
            'is_encrypted' => $this->isEncrypted,
            'group' => $this->group,
            'ui_metadata' => $this->uiMetadata,
        ];
    }

    /**
     * Check if this setting can be modified.
     */
    public function isWritable(): bool
    {
        return ! $this->isReadOnly;
    }

    /**
     * Check if this setting can be overridden by lower layers.
     */
    public function canBeOverridden(): bool
    {
        return ! $this->isProtected;
    }
}
