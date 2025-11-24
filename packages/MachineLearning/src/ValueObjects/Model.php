<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

/**
 * Immutable value object representing a loaded machine learning model
 * 
 * Contains model metadata, artifact location, and runtime information
 * needed for inference execution.
 * 
 * Example:
 * ```php
 * $model = new Model(
 *     name: 'fraud_detection',
 *     version: '1.0.0',
 *     format: 'pytorch',
 *     artifactPath: '/models/fraud_detection/1.0.0/model.pth',
 *     metadata: [
 *         'input_schema' => ['features' => ['amount', 'merchant_id', 'time']],
 *         'output_schema' => ['prediction' => 'float', 'probability' => 'float'],
 *         'framework' => 'pytorch',
 *         'framework_version' => '2.1.0',
 *     ]
 * );
 * ```
 */
final readonly class Model
{
    /**
     * @param string $name Model identifier (e.g., 'fraud_detection', 'churn_predictor')
     * @param string $version Model version (semantic versioning recommended)
     * @param string $format Model format (e.g., 'pytorch', 'onnx', 'tensorflow', 'sklearn')
     * @param string $artifactPath Absolute path to model artifact (file or directory)
     * @param array<string, mixed> $metadata Additional model metadata (schema, framework, tags, etc.)
     * @param \DateTimeImmutable|null $createdAt When the model was created/trained
     * @param \DateTimeImmutable|null $loadedAt When the model was loaded into memory
     */
    public function __construct(
        public string $name,
        public string $version,
        public string $format,
        public string $artifactPath,
        public array $metadata = [],
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $loadedAt = null,
    ) {
        if (empty($name)) {
            throw new \InvalidArgumentException('Model name cannot be empty');
        }

        if (empty($version)) {
            throw new \InvalidArgumentException('Model version cannot be empty');
        }

        if (empty($format)) {
            throw new \InvalidArgumentException('Model format cannot be empty');
        }

        if (empty($artifactPath)) {
            throw new \InvalidArgumentException('Model artifact path cannot be empty');
        }
    }

    /**
     * Get model identifier (name@version)
     * 
     * @return string Unique model identifier
     */
    public function getIdentifier(): string
    {
        return "{$this->name}@{$this->version}";
    }

    /**
     * Get metadata value by key
     * 
     * @param string $key Metadata key
     * @param mixed $default Default value if key doesn't exist
     * 
     * @return mixed Metadata value or default
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if model has specific metadata key
     * 
     * @param string $key Metadata key
     * 
     * @return bool True if key exists
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Get input schema from metadata
     * 
     * @return array<string, mixed> Input schema or empty array
     */
    public function getInputSchema(): array
    {
        return $this->metadata['input_schema'] ?? [];
    }

    /**
     * Get output schema from metadata
     * 
     * @return array<string, mixed> Output schema or empty array
     */
    public function getOutputSchema(): array
    {
        return $this->metadata['output_schema'] ?? [];
    }

    /**
     * Get framework name from metadata
     * 
     * @return string|null Framework name (e.g., 'pytorch', 'tensorflow')
     */
    public function getFramework(): ?string
    {
        return $this->metadata['framework'] ?? null;
    }

    /**
     * Check if model artifact exists on filesystem
     * 
     * @return bool True if artifact exists
     */
    public function artifactExists(): bool
    {
        return file_exists($this->artifactPath);
    }

    /**
     * Create a new instance with updated loaded timestamp
     * 
     * @return self New instance with current timestamp
     */
    public function withLoadedTimestamp(): self
    {
        return new self(
            name: $this->name,
            version: $this->version,
            format: $this->format,
            artifactPath: $this->artifactPath,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            loadedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a new instance with additional metadata
     * 
     * @param array<string, mixed> $metadata Metadata to merge
     * 
     * @return self New instance with merged metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            name: $this->name,
            version: $this->version,
            format: $this->format,
            artifactPath: $this->artifactPath,
            metadata: array_merge($this->metadata, $metadata),
            createdAt: $this->createdAt,
            loadedAt: $this->loadedAt,
        );
    }

    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'format' => $this->format,
            'artifact_path' => $this->artifactPath,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'loaded_at' => $this->loadedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
