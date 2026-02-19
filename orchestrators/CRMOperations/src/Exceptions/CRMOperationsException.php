<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Exceptions;

use Exception;
use Throwable;

/**
 * Base Exception for CRM Operations
 * 
 * All exceptions in the CRMOperations package should extend this class.
 * Provides context-rich factory methods for common error scenarios.
 * 
 * @package Nexus\CRMOperations\Exceptions
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
abstract class CRMOperationsException extends Exception
{
    /**
     * @param string $message Exception message
     * @param string|null $context Additional context information
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        private ?string $context = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the context information
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Get full message with context
     */
    public function getFullMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->context !== null) {
            $message .= " [Context: {$this->context}]";
        }
        
        return $message;
    }

    /**
     * Factory method for lead conversion errors
     */
    public static function leadConversionFailed(
        string $leadId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new class($leadId, $reason, $previous) extends CRMOperationsException {
            public function __construct(
                string $leadId,
                string $reason,
                ?Throwable $previous
            ) {
                parent::__construct(
                    message: "Lead conversion failed for lead {$leadId}: {$reason}",
                    context: "lead_id: {$leadId}",
                    previous: $previous
                );
            }
        };
    }

    /**
     * Factory method for opportunity errors
     */
    public static function opportunityFailed(
        string $opportunityId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new class($opportunityId, $reason, $previous) extends CRMOperationsException {
            public function __construct(
                string $opportunityId,
                string $reason,
                ?Throwable $previous
            ) {
                parent::__construct(
                    message: "Opportunity operation failed for {$opportunityId}: {$reason}",
                    context: "opportunity_id: {$opportunityId}",
                    previous: $previous
                );
            }
        };
    }

    /**
     * Factory method for workflow errors
     */
    public static function workflowFailed(
        string $workflowType,
        string $step,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new class($workflowType, $step, $reason, $previous) extends CRMOperationsException {
            public function __construct(
                string $workflowType,
                string $step,
                string $reason,
                ?Throwable $previous
            ) {
                parent::__construct(
                    message: "Workflow '{$workflowType}' failed at step '{$step}': {$reason}",
                    context: "workflow: {$workflowType}, step: {$step}",
                    previous: $previous
                );
            }
        };
    }

    /**
     * Factory method for validation errors
     */
    public static function validationFailed(
        string $field,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new class($field, $reason, $previous) extends CRMOperationsException {
            public function __construct(
                string $field,
                string $reason,
                ?Throwable $previous
            ) {
                parent::__construct(
                    message: "Validation failed for '{$field}': {$reason}",
                    context: "field: {$field}",
                    previous: $previous
                );
            }
        };
    }

    /**
     * Factory method for integration errors
     */
    public static function integrationFailed(
        string $service,
        string $operation,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new class($service, $operation, $reason, $previous) extends CRMOperationsException {
            public function __construct(
                string $service,
                string $operation,
                string $reason,
                ?Throwable $previous
            ) {
                parent::__construct(
                    message: "Integration with '{$service}' failed during '{$operation}': {$reason}",
                    context: "service: {$service}, operation: {$operation}",
                    previous: $previous
                );
            }
        };
    }
}
