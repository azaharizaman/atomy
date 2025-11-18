<?php

declare(strict_types=1);

namespace Nexus\DataProcessor\Contracts;

use Nexus\DataProcessor\ValueObjects\ProcessingResult;

/**
 * Document Recognizer Interface
 * 
 * Contract for OCR services that extract structured data from documents.
 * Implementations must be provided in the application layer.
 */
interface DocumentRecognizerInterface
{
    /**
     * Recognize and extract data from a document
     * 
     * @param string $filePath Path to the document file or URL
     * @param string $documentType Type of document (invoice, receipt, contract, id, passport)
     * @param array<string, mixed> $options Additional processing options
     * 
     * @return ProcessingResult Extraction result with confidence scores
     * 
     * @throws \Nexus\DataProcessor\Exceptions\ProcessingFailedException
     * @throws \Nexus\DataProcessor\Exceptions\UnsupportedDocumentTypeException
     */
    public function recognizeDocument(string $filePath, string $documentType, array $options = []): ProcessingResult;

    /**
     * Get supported document types for this recognizer
     * 
     * @return array<string> List of supported document types
     */
    public function getSupportedDocumentTypes(): array;

    /**
     * Check if a document type is supported
     */
    public function supportsDocumentType(string $documentType): bool;
}
