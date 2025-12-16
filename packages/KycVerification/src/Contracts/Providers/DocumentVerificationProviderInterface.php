<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts\Providers;

use Nexus\KycVerification\Enums\DocumentType;
use Nexus\KycVerification\ValueObjects\DocumentVerification;

/**
 * Interface for document verification provider.
 * 
 * This interface abstracts document verification operations.
 * The orchestrator layer must implement this using external verification services
 * or Nexus\Document integration.
 */
interface DocumentVerificationProviderInterface
{
    /**
     * Verify a document
     * 
     * @param string $documentId The document identifier
     * @param DocumentType $documentType The type of document
     * @param array<string, mixed> $documentData Document metadata and extracted data
     */
    public function verify(
        string $documentId,
        DocumentType $documentType,
        array $documentData = []
    ): DocumentVerification;

    /**
     * Extract data from a document (OCR)
     * 
     * @param string $documentPath Path or URL to the document
     * @param DocumentType $documentType Expected document type
     * @return array<string, mixed> Extracted data
     */
    public function extractData(
        string $documentPath,
        DocumentType $documentType
    ): array;

    /**
     * Validate document authenticity
     * 
     * @param string $documentId The document identifier
     * @param DocumentType $documentType The type of document
     * @return array{valid: bool, confidence: float, issues: array<string>}
     */
    public function validateAuthenticity(
        string $documentId,
        DocumentType $documentType
    ): array;

    /**
     * Check if document has expired
     * 
     * @param string $documentId The document identifier
     * @return array{expired: bool, expiry_date: ?\DateTimeImmutable}
     */
    public function checkExpiry(string $documentId): array;

    /**
     * Get document metadata
     * 
     * @param string $documentId The document identifier
     * @return array<string, mixed>|null
     */
    public function getDocumentMetadata(string $documentId): ?array;

    /**
     * Compare face in document with provided photo
     * 
     * @param string $documentId Document with face (e.g., passport)
     * @param string $selfieId Selfie or live photo
     * @return array{match: bool, confidence: float, liveness_verified: bool}
     */
    public function compareFaces(
        string $documentId,
        string $selfieId
    ): array;
}
