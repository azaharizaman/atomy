<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

/**
 * Gateway interface for document storage operations
 */
interface DocumentStorageGatewayInterface
{
    /**
     * Store employee document
     */
    public function storeDocument(string $employeeId, string $documentType, string $filePath): string;
    
    /**
     * Retrieve document metadata and URL
     */
    public function retrieveDocument(string $documentId): array;
    
    /**
     * Delete document
     */
    public function deleteDocument(string $documentId): void;
    
    /**
     * Check if document exists
     */
    public function documentExists(string $documentId): bool;
}
