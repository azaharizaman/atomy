<?php

declare(strict_types=1);

namespace Nexus\Monitoring\ValueObjects;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Aggregation Specification Value Object
 *
 * Immutable specification for aggregating metrics over time ranges.
 * Used to abstract aggregation logic from specific TSDB implementations.
 *
 * @package Nexus\Monitoring\ValueObjects
 */
final readonly class AggregationSpec
{
    /**
     * @param string $metricName Name of the metric to aggregate
     * @param AggregationFunction $function Aggregation function to apply
     * @param DateTimeInterface $from Start of time range (inclusive)
     * @param DateTimeInterface $to End of time range (inclusive)
     * @param array<string, scalar> $tags Optional tag filters
     * @param array<string> $groupBy Optional dimensions to group by
     */
    public function __construct(
        public string $metricName,
        public AggregationFunction $function,
        public DateTimeInterface $from,
        public DateTimeInterface $to,
        public array $tags = [],
        public array $groupBy = [],
    ) {
        $this->validate();
    }

    /**
     * Validate aggregation specification constraints.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->metricName)) {
            throw new InvalidArgumentException('Metric name cannot be empty');
        }

        if ($this->from >= $this->to) {
            throw new InvalidArgumentException(
                sprintf(
                    'From date (%s) must be before to date (%s)',
                    $this->from->format('Y-m-d H:i:s'),
                    $this->to->format('Y-m-d H:i:s')
                )
            );
        }

        foreach ($this->tags as $key => $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf('Tag value for key "%s" must be scalar', $key)
                );
            }
        }

        foreach ($this->groupBy as $dimension) {
            if (!is_string($dimension) || empty($dimension)) {
                throw new InvalidArgumentException('Group by dimensions must be non-empty strings');
            }
        }
    }

    /**
     * Check if this aggregation requires TSDB-specific features.
     * Non-standard functions may not be supported by all storage backends.
     *
     * @return bool
     */
    public function requiresAdvancedTsdb(): bool
    {
        return !$this->function->isStandard();
    }

    /**
     * Get time range duration in seconds.
     *
     * @return int
     */
    public function getDurationSeconds(): int
    {
        return $this->to->getTimestamp() - $this->from->getTimestamp();
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'metric_name' => $this->metricName,
            'function' => $this->function->value,
            'from' => $this->from->format('Y-m-d H:i:s'),
            'to' => $this->to->format('Y-m-d H:i:s'),
            'tags' => $this->tags,
            'group_by' => $this->groupBy,
        ];
    }
}
