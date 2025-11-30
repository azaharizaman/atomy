<?php

declare(strict_types=1);

namespace Nexus\Analytics\Contracts;

/**
 * Represents the result of an analytics query execution
 */
interface QueryResultInterface
{
    /**
     * Get the query ID that produced this result
     */
    public function getQueryId(): string;

    /**
     * Get the result data
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Get execution metadata (timing, resources used, etc.)
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if the query execution was successful
     */
    public function isSuccessful(): bool;

    /**
     * Get error message if execution failed
     */
    public function getError(): ?string;

    /**
     * Get the execution timestamp
     */
    public function getExecutedAt(): \DateTimeImmutable;

    /**
     * Get the execution duration in milliseconds
     */
    public function getDurationMs(): int;
}
