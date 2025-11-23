<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Core;

use Nexus\Monitoring\Contracts\TelemetryTrackerInterface;

/**
 * SLOWrapper
 *
 * Utility class to simplify Service Level Objective (SLO) tracking.
 * Wraps operations with automatic success/failure/latency tracking.
 *
 * @package Nexus\Monitoring\Core
 */
final readonly class SLOWrapper
{
    public function __construct(
        private TelemetryTrackerInterface $telemetry,
        private string $operation,
        private array $baseTags = []
    ) {}

    /**
     * Execute a callable and track SLO metrics.
     *
     * @template T
     * @param callable(): T $callable The operation to execute
     * @param array<string, scalar> $additionalTags Additional tags for this specific execution
     * @return T The result from the callable
     * @throws \Throwable Re-throws any exception after tracking
     */
    public function execute(callable $callable, array $additionalTags = []): mixed
    {
        $startTime = microtime(true);
        $tags = array_merge($this->baseTags, $additionalTags, ['operation' => $this->operation]);

        try {
            $result = $callable();
            
            // Track success
            $this->telemetry->increment(
                "slo.{$this->operation}.success",
                1.0,
                $tags
            );
            
            return $result;
        } catch (\Throwable $e) {
            // Track failure
            $this->telemetry->increment(
                "slo.{$this->operation}.failure",
                1.0,
                array_merge($tags, [
                    'exception' => get_class($e),
                    'error_type' => $this->classifyError($e)
                ])
            );
            
            throw $e;
        } finally {
            // Always track latency
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $this->telemetry->timing(
                "slo.{$this->operation}.latency",
                $duration,
                $tags
            );
            
            // Track total requests
            $this->telemetry->increment(
                "slo.{$this->operation}.total",
                1.0,
                $tags
            );
        }
    }

    /**
     * Create a new SLOWrapper for a specific operation.
     *
     * @param TelemetryTrackerInterface $telemetry
     * @param string $operation
     * @param array<string, scalar> $baseTags
     * @return self
     */
    public static function for(
        TelemetryTrackerInterface $telemetry,
        string $operation,
        array $baseTags = []
    ): self {
        return new self($telemetry, $operation, $baseTags);
    }

    /**
     * Classify error type for better categorization.
     *
     * @param \Throwable $e
     * @return string
     */
    private function classifyError(\Throwable $e): string
    {
        return match (true) {
            $e instanceof \InvalidArgumentException,
            $e instanceof \LogicException => 'client_error',
            $e instanceof \RuntimeException => 'server_error',
            default => 'unknown_error'
        };
    }

    /**
     * Get the operation name.
     *
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Get the base tags.
     *
     * @return array<string, scalar>
     */
    public function getBaseTags(): array
    {
        return $this->baseTags;
    }
}
