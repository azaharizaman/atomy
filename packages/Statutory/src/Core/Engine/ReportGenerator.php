<?php

declare(strict_types=1);

namespace Nexus\Statutory\Core\Engine;

use Psr\Log\LoggerInterface;
use Nexus\Statutory\ValueObjects\ReportFormat;
use Nexus\Statutory\Exceptions\ValidationException;

/**
 * Report generator for statutory reports.
 * 
 * Orchestrates data extraction, validation, and format conversion.
 */
final class ReportGenerator
{
    public function __construct(
        private readonly SchemaValidator $schemaValidator,
        private readonly FormatConverter $formatConverter,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a report from data.
     *
     * @param string $reportType The report type
     * @param array<string, mixed> $data The report data
     * @param ReportFormat $format The desired output format
     * @param string $schemaIdentifier The schema to validate against
     * @return string The generated report content
     * @throws ValidationException If data validation fails
     */
    public function generate(
        string $reportType,
        array $data,
        ReportFormat $format,
        string $schemaIdentifier
    ): string {
        $this->logger->info("Generating report", [
            'report_type' => $reportType,
            'format' => $format->value,
            'schema' => $schemaIdentifier,
        ]);

        // Validate data against schema
        $validationErrors = $this->schemaValidator->validate($schemaIdentifier, $data);
        if (!empty($validationErrors)) {
            throw new ValidationException($reportType, $validationErrors);
        }

        // Convert to desired format
        $content = $this->formatConverter->convert($data, $format);

        $this->logger->info("Report generated successfully", [
            'report_type' => $reportType,
            'content_length' => strlen($content),
        ]);

        return $content;
    }

    /**
     * Generate a report with metadata.
     *
     * @param string $reportType The report type
     * @param array<string, mixed> $data The report data
     * @param array<string, mixed> $metadata Additional metadata
     * @param ReportFormat $format The desired output format
     * @param string $schemaIdentifier The schema to validate against
     * @return array{content: string, metadata: array<string, mixed>}
     */
    public function generateWithMetadata(
        string $reportType,
        array $data,
        array $metadata,
        ReportFormat $format,
        string $schemaIdentifier
    ): array {
        $content = $this->generate($reportType, $data, $format, $schemaIdentifier);

        $enrichedMetadata = array_merge($metadata, [
            'generated_at' => (new \DateTimeImmutable())->format('c'),
            'format' => $format->value,
            'content_length' => strlen($content),
            'checksum' => hash('sha256', $content),
        ]);

        return [
            'content' => $content,
            'metadata' => $enrichedMetadata,
        ];
    }
}
