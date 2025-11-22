<?php

declare(strict_types=1);

namespace Nexus\Monitoring\ValueObjects;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Query Specification Value Object
 *
 * Immutable specification for querying metrics from storage.
 * Used to abstract query logic from specific TSDB implementations.
 *
 * @package Nexus\Monitoring\ValueObjects
 */
final readonly class QuerySpec
{
    /**
     * @param string $metricName Name of the metric to query
     * @param DateTimeInterface $from Start of time range (inclusive)
     * @param DateTimeInterface $to End of time range (inclusive)
     * @param array<string, scalar> $tags Optional tag filters
     * @param int|null $limit Optional maximum number of results
     * @param string|null $orderBy Optional field to order by (e.g., 'timestamp', 'value')
     */
    public function __construct(
        public string $metricName,
        public DateTimeInterface $from,
        public DateTimeInterface $to,
        public array $tags = [],
        public ?int $limit = null,
        public ?string $orderBy = null,
    ) {
        $this->validate();
    }

    /**
     * Validate query specification constraints.
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

        if ($this->limit !== null && $this->limit <= 0) {
            throw new InvalidArgumentException('Limit must be a positive integer');
        }

        foreach ($this->tags as $key => $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException(
                    sprintf('Tag value for key "%s" must be scalar', $key)
                );
            }
        }
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
            'from' => $this->from->format('Y-m-d H:i:s'),
            'to' => $this->to->format('Y-m-d H:i:s'),
            'tags' => $this->tags,
            'limit' => $this->limit,
            'order_by' => $this->orderBy,
        ];
    }
}
