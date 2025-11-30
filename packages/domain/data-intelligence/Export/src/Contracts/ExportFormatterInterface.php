<?php

declare(strict_types=1);

namespace Nexus\Export\Contracts;

use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;

/**
 * Export formatter contract
 * 
 * Formatters convert ExportDefinition into specific output formats.
 * Concrete implementations live in application layer (Atomy).
 * 
 * Framework-agnostic formatters (CSV, JSON, TXT) in package Core/.
 * Framework-dependent formatters (PDF, Excel) in Atomy.
 */
interface ExportFormatterInterface
{
    /**
     * Convert export definition to formatted output
     * 
     * @param ExportDefinition $definition Validated export definition
     * @return string Binary content or file path depending on format
     * @throws \Nexus\Export\Exceptions\FormatterException
     */
    public function format(ExportDefinition $definition): string;

    /**
     * Get supported export format
     */
    public function getFormat(): ExportFormat;

    /**
     * Check if formatter supports streaming output
     * 
     * Streaming formatters return generators for memory efficiency
     */
    public function supportsStreaming(): bool;

    /**
     * Check if formatter requires external service
     * 
     * External formatters should be wrapped in circuit breaker
     */
    public function requiresExternalService(): bool;

    /**
     * Get required schema version
     * 
     * @return string Minimum schema version (e.g., '>=1.0')
     */
    public function requiresSchemaVersion(): string;

    /**
     * Stream large export for memory efficiency
     * 
     * Only called if supportsStreaming() returns true
     * 
     * @param ExportDefinition $definition Validated export definition
     * @return \Generator<string> Chunks of output data
     * @throws \Nexus\Export\Exceptions\FormatterException
     */
    public function stream(ExportDefinition $definition): \Generator;
}
