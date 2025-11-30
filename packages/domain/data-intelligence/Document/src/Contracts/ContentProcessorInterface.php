<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentFormat;

/**
 * Content processor interface for document transformation and analysis.
 *
 * Defines extensibility points for:
 * - PDF rendering (Service Reports, Invoices)
 * - ML-driven document classification
 * - OCR and metadata extraction
 * - Content redaction
 *
 * Default implementation is a No-Op placeholder.
 * Production implementation integrates with Nexus\Intelligence.
 */
interface ContentProcessorInterface
{
    /**
     * Render data into a specific document format (e.g., PDF).
     *
     * Used for generating Service Reports, Invoices, Contracts from templates.
     *
     * @param string $templateName Template identifier (e.g., 'FIELD_SERVICE_REPORT')
     * @param array<string, mixed> $data Dynamic data to populate the template
     * @param DocumentFormat $format Desired output format (PDF, HTML, DOCX)
     * @return string Raw binary content of the generated file
     * @throws \Nexus\Document\Exceptions\DocumentRenderingException If rendering fails
     */
    public function render(
        string $templateName,
        array $data,
        DocumentFormat $format
    ): string;

    /**
     * Analyze document content using ML (OCR, classification, metadata extraction).
     *
     * Used for auto-classifying uploaded documents and extracting metadata.
     *
     * @param string $documentPath Path to the file in Nexus\Storage
     * @return ContentAnalysisResult Structured analysis results
     * @throws \Nexus\Document\Exceptions\ContentAnalysisException If analysis fails
     */
    public function analyze(string $documentPath): ContentAnalysisResult;

    /**
     * Extract text content from a document (OCR for images/PDFs).
     *
     * @param string $documentPath Path to the file in Nexus\Storage
     * @return string Extracted text content
     * @throws \Nexus\Document\Exceptions\ContentAnalysisException If extraction fails
     */
    public function extractText(string $documentPath): string;

    /**
     * Redact sensitive data from a document.
     *
     * @param string $documentPath Path to the file in Nexus\Storage
     * @param array<string> $patterns Regex patterns or keywords to redact
     * @return string Path to the redacted document
     * @throws \Nexus\Document\Exceptions\ContentAnalysisException If redaction fails
     */
    public function redact(string $documentPath, array $patterns): string;
}
