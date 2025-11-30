<?php

declare(strict_types=1);

namespace Nexus\Export\Services;

use Generator;
use Nexus\Export\Contracts\DefinitionValidatorInterface;
use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\Contracts\TemplateEngineInterface;
use Nexus\Export\Exceptions\UnsupportedDestinationException;
use Nexus\Export\Exceptions\UnsupportedFormatException;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportDestination;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportResult;
use Psr\Log\LoggerInterface;

/**
 * Export manager - main orchestration service
 * 
 * Coordinates the export pipeline:
 * 1. Validate ExportDefinition
 * 2. Select appropriate formatter
 * 3. Generate output (with circuit breaker for external services)
 * 4. Deliver to destination (with rate limiting)
 * 5. Audit the operation
 * 
 * This is the primary public API for the Export package.
 */
final readonly class ExportManager
{
    /**
     * @param array<ExportFormat, ExportFormatterInterface> $formatters Format-to-formatter mapping
     */
    public function __construct(
        private array $formatters,
        private DefinitionValidatorInterface $validator,
        private ?TemplateEngineInterface $templateEngine = null,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Export definition to specified format and destination
     * 
     * @throws UnsupportedFormatException
     * @throws UnsupportedDestinationException
     */
    public function export(
        ExportDefinition $definition,
        ExportFormat $format,
        ExportDestination $destination
    ): ExportResult {
        $startTime = microtime(true);
        
        try {
            // Step 1: Validate definition
            $this->logger?->info('Validating export definition', [
                'title' => $definition->metadata->title,
                'format' => $format->value,
                'destination' => $destination->value,
            ]);
            
            $this->validator->validateOrFail($definition);

            // Step 2: Get formatter
            $formatter = $this->getFormatter($format);

            // Step 3: Generate output
            $this->logger?->info('Generating export output', ['format' => $format->value]);
            
            $output = $formatter->format($definition);
            $sizeBytes = strlen($output);

            // Step 4: Deliver to destination
            $filePath = $this->deliverToDestination($output, $format, $destination, $definition);

            // Step 5: Calculate metrics
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logger?->info('Export completed successfully', [
                'format' => $format->value,
                'destination' => $destination->value,
                'size_bytes' => $sizeBytes,
                'duration_ms' => $durationMs,
            ]);

            return new ExportResult(
                success: true,
                format: $format,
                destination: $destination,
                filePath: $filePath,
                sizeBytes: $sizeBytes,
                durationMs: $durationMs
            );

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logger?->error('Export failed', [
                'error' => $e->getMessage(),
                'format' => $format->value,
                'destination' => $destination->value,
                'duration_ms' => $durationMs,
            ]);

            return new ExportResult(
                success: false,
                format: $format,
                destination: $destination,
                filePath: null,
                sizeBytes: 0,
                durationMs: $durationMs,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Export definition to specified format using streaming
     * 
     * Ideal for large datasets (100K+ rows) to minimize memory usage.
     * 
     * @return Generator<string> Output chunks
     * @throws UnsupportedFormatException
     */
    public function stream(
        ExportDefinition $definition,
        ExportFormat $format
    ): Generator {
        $this->logger?->info('Streaming export', [
            'title' => $definition->metadata->title,
            'format' => $format->value,
        ]);

        // Validate definition
        $this->validator->validateOrFail($definition);

        // Get formatter
        $formatter = $this->getFormatter($format);

        if (!$formatter->supportsStreaming()) {
            $this->logger?->warning('Formatter does not support streaming, falling back to buffered mode', [
                'format' => $format->value,
            ]);
            
            yield $formatter->format($definition);
            return;
        }

        // Stream output
        yield from $formatter->stream($definition);
    }

    /**
     * Export from template with data binding
     * 
     * @param array<string, mixed> $context Template variables
     * @throws UnsupportedFormatException
     */
    public function exportFromTemplate(
        string $templateId,
        array $context,
        ExportFormat $format,
        ExportDestination $destination
    ): ExportResult {
        if ($this->templateEngine === null) {
            throw new \RuntimeException('Template engine not configured');
        }

        $this->logger?->info('Exporting from template', [
            'template_id' => $templateId,
            'format' => $format->value,
            'destination' => $destination->value,
        ]);

        // Render template
        $rendered = $this->templateEngine->render($templateId, $context);

        // For template-based formats (PDF, HTML), the rendered output IS the final output
        // For data formats, we'd need to parse the rendered template back to ExportDefinition
        // This is a simplified implementation

        $startTime = microtime(true);
        $sizeBytes = strlen($rendered);
        
        $filePath = $this->deliverToDestination($rendered, $format, $destination, null);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        return new ExportResult(
            success: true,
            format: $format,
            destination: $destination,
            filePath: $filePath,
            sizeBytes: $sizeBytes,
            durationMs: $durationMs
        );
    }

    /**
     * Get formatter for specified format
     * 
     * @throws UnsupportedFormatException
     */
    private function getFormatter(ExportFormat $format): ExportFormatterInterface
    {
        if (!isset($this->formatters[$format->value])) {
            throw UnsupportedFormatException::forFormat($format);
        }

        return $this->formatters[$format->value];
    }

    /**
     * Deliver output to destination
     * 
     * NOTE: This is a placeholder implementation. In Atomy, this should:
     * - Inject StorageInterface for STORAGE destination
     * - Inject NotifierInterface for EMAIL destination
     * - Inject WebhookClient for WEBHOOK destination
     * - Inject RateLimiterInterface for rate-limited destinations
     * 
     * @throws UnsupportedDestinationException
     */
    private function deliverToDestination(
        string $output,
        ExportFormat $format,
        ExportDestination $destination,
        ?ExportDefinition $definition
    ): ?string {
        return match ($destination) {
            ExportDestination::DOWNLOAD => $this->prepareDownload($output, $format, $definition),
            ExportDestination::STORAGE => throw new UnsupportedDestinationException(
                'STORAGE destination requires StorageInterface implementation in Atomy'
            ),
            ExportDestination::EMAIL => throw new UnsupportedDestinationException(
                'EMAIL destination requires NotifierInterface implementation in Atomy'
            ),
            ExportDestination::WEBHOOK => throw new UnsupportedDestinationException(
                'WEBHOOK destination requires WebhookClient implementation in Atomy'
            ),
            ExportDestination::PRINTER => throw new UnsupportedDestinationException(
                'PRINTER destination not implemented in MVP (Phase 2)'
            ),
            ExportDestination::DOCUMENT_LIBRARY => throw new UnsupportedDestinationException(
                'DOCUMENT_LIBRARY destination requires Nexus\Document package (future)'
            ),
        };
    }

    /**
     * Prepare file for download
     * 
     * In a real application, this would save to temporary storage
     * and return a signed URL. For now, we just return a placeholder path.
     */
    private function prepareDownload(
        string $output,
        ExportFormat $format,
        ?ExportDefinition $definition
    ): string {
        $filename = $this->generateFilename($format, $definition);
        
        // In Atomy, this should use StorageInterface to save to temp storage
        // For now, return a virtual path
        return "/tmp/exports/{$filename}";
    }

    /**
     * Generate filename for export
     */
    private function generateFilename(ExportFormat $format, ?ExportDefinition $definition): string
    {
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $title = $definition?->metadata->title ?? 'export';
        
        // Sanitize title for filename
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title);
        $sanitized = strtolower($sanitized);
        
        return "{$sanitized}_{$timestamp}.{$format->getFileExtension()}";
    }
}
