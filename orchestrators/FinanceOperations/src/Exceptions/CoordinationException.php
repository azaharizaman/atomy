<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for all FinanceOperations coordination errors.
 * 
 * This exception serves as the root of the FinanceOperations exception hierarchy.
 * All coordination-related errors should extend this class or one of its subclasses.
 * 
 * @since 1.0.0
 */
class CoordinationException extends RuntimeException
{
    /**
     * @param string $message The exception message
     * @param string $coordinatorName The name of the coordinator that threw the exception
     * @param array<string, mixed> $context Additional context data
     * @param Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        string $message,
        private readonly string $coordinatorName = '',
        private readonly array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the coordinator name that threw this exception.
     */
    public function getCoordinatorName(): string
    {
        return $this->coordinatorName;
    }

    /**
     * Get the context data associated with this exception.
     * 
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create exception for missing required data.
     */
    public static function missingRequiredData(
        string $coordinatorName,
        string $dataType,
        string $tenantId
    ): self {
        return new self(
            sprintf('Required data "%s" is not available for tenant %s', $dataType, $tenantId),
            $coordinatorName,
            ['data_type' => $dataType, 'tenant_id' => $tenantId]
        );
    }

    /**
     * Create exception for coordination failure.
     */
    public static function coordinationFailed(
        string $coordinatorName,
        string $operation,
        string $reason,
        array $context = []
    ): self {
        return new self(
            sprintf('Coordination failed for %s: %s', $operation, $reason),
            $coordinatorName,
            array_merge(['operation' => $operation, 'reason' => $reason], $context)
        );
    }

    /**
     * Create exception for workflow failure.
     */
    public static function workflowFailed(
        string $coordinatorName,
        string $workflowId,
        string $step,
        string $reason
    ): self {
        return new self(
            sprintf('Workflow %s failed at step %s: %s', $workflowId, $step, $reason),
            $coordinatorName,
            ['workflow_id' => $workflowId, 'step' => $step, 'reason' => $reason]
        );
    }
}
