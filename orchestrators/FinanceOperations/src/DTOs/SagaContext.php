<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Context for saga execution.
 *
 * Maintains state across a long-running saga process, including
 * step outputs, current status, and correlation tracking.
 *
 * @since 1.0.0
 */
final readonly class SagaContext
{
    /**
     * @param string $tenantId Tenant identifier for multi-tenancy
     * @param string $sagaId Unique saga instance identifier
     * @param array<string, mixed> $data Saga-specific data
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $correlationId Correlation ID for distributed tracing
     * @param array<string, array<string, mixed>> $stepOutputs Outputs from completed steps
     * @param string|null $currentStep Current step being executed
     * @param string $status Saga status: 'pending', 'running', 'completed', 'failed', 'compensating'
     */
    public function __construct(
        public string $tenantId,
        public string $sagaId,
        public array $data = [],
        public array $metadata = [],
        public ?string $correlationId = null,
        public array $stepOutputs = [],
        public ?string $currentStep = null,
        public string $status = 'pending',
    ) {}

    /**
     * Get a specific data value.
     *
     * @param string $key Data key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a data key exists.
     *
     * @param string $key Data key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Create new context with step output data.
     *
     * @param string $stepName Step that produced the data
     * @param array<string, mixed> $output Step output data
     * @return self
     */
    public function withStepOutput(string $stepName, array $output): self
    {
        $newOutputs = $this->stepOutputs;
        $newOutputs[$stepName] = $output;

        return new self(
            tenantId: $this->tenantId,
            sagaId: $this->sagaId,
            data: $this->data,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            stepOutputs: $newOutputs,
            currentStep: $this->currentStep,
            status: $this->status,
        );
    }

    /**
     * Create new context with updated status.
     *
     * @param string $status New status value
     * @return self
     */
    public function withStatus(string $status): self
    {
        return new self(
            tenantId: $this->tenantId,
            sagaId: $this->sagaId,
            data: $this->data,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            stepOutputs: $this->stepOutputs,
            currentStep: $this->currentStep,
            status: $status,
        );
    }

    /**
     * Get output from a specific step.
     *
     * @param string $stepName Step identifier
     * @return array<string, mixed>|null Step output or null
     */
    public function getStepOutput(string $stepName): ?array
    {
        return $this->stepOutputs[$stepName] ?? null;
    }

    /**
     * Check if saga is in a terminal state.
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'compensated'], true);
    }
}
