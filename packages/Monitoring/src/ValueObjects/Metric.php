<?php

declare(strict_types=1);

namespace Nexus\Monitoring\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Metric Value Object
 *
 * Immutable representation of a single metric measurement with optional
 * distributed tracing context (OpenTelemetry-ready).
 *
 * @package Nexus\Monitoring\ValueObjects
 */
final readonly class Metric
{
    /**
     * @param string $name Metric name (alphanumeric + underscores only)
     * @param MetricType $type Type of metric (counter, gauge, timing, histogram)
     * @param float $value Numerical value of the metric
     * @param array<string, scalar> $tags Key-value tags for dimensions (must be scalar values)
     * @param DateTimeImmutable $timestamp When the metric was recorded (microsecond precision)
     * @param string|null $traceId Optional OpenTelemetry trace ID for correlation
     * @param string|null $spanId Optional OpenTelemetry span ID for correlation
     */
    public function __construct(
        public string $name,
        public MetricType $type,
        public float $value,
        public array $tags,
        public DateTimeImmutable $timestamp,
        public ?string $traceId = null,
        public ?string $spanId = null,
    ) {
        $this->validateName($name);
        $this->validateTags($tags);
        $this->validateTraceContext($traceId, $spanId);
    }

    /**
     * Create a new Metric instance with trace context added.
     * Useful for fluent API when adding trace context after metric creation.
     *
     * @param string $traceId OpenTelemetry trace ID
     * @param string $spanId OpenTelemetry span ID
     * @return self
     */
    public function withTraceContext(string $traceId, string $spanId): self
    {
        return new self(
            name: $this->name,
            type: $this->type,
            value: $this->value,
            tags: $this->tags,
            timestamp: $this->timestamp,
            traceId: $traceId,
            spanId: $spanId
        );
    }

    /**
     * Check if this metric has trace context attached.
     *
     * @return bool
     */
    public function hasTraceContext(): bool
    {
        return $this->traceId !== null && $this->spanId !== null;
    }

    /**
     * Convert metric to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'value' => $this->value,
            'tags' => $this->tags,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'trace_id' => $this->traceId,
            'span_id' => $this->spanId,
        ];
    }

    /**
     * Validate metric name format.
     *
     * @param string $name
     * @throws InvalidArgumentException
     */
    private function validateName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Metric name cannot be empty');
        }

        if (!preg_match('/^[a-z0-9_]+$/i', $name)) {
            throw new InvalidArgumentException(
                'Metric name must contain only alphanumeric characters and underscores: ' . $name
            );
        }
    }

    /**
     * Validate that all tag values are scalar.
     *
     * @param array<string, mixed> $tags
     * @throws InvalidArgumentException
     */
    private function validateTags(array $tags): void
    {
        foreach ($tags as $key => $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Tag value for key "%s" must be scalar (string, int, float, bool), %s given',
                        $key,
                        gettype($value)
                    )
                );
            }
        }
    }

    /**
     * Validate trace context consistency.
     *
     * @param string|null $traceId
     * @param string|null $spanId
     * @throws InvalidArgumentException
     */
    private function validateTraceContext(?string $traceId, ?string $spanId): void
    {
        // If one is set, both must be set
        if (($traceId === null) !== ($spanId === null)) {
            throw new InvalidArgumentException(
                'Both traceId and spanId must be provided together or both null'
            );
        }
    }
}
