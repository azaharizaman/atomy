<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Standard contract for any operation result (Service or Orchestrator).
 */
interface OperationResultInterface
{
    /**
     * Check if the operation was successful.
     */
    public function isSuccess(): bool;

    /**
     * Get a human-readable message about the result.
     */
    public function getMessage(): string;

    /**
     * Get any data returned by the operation.
     * 
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Get detailed issues or validation errors (if any).
     * 
     * @return array<array{rule: string, message: string}>
     */
    public function getIssues(): array;
}
