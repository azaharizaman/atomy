<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * Document Service Interface
 * 
 * Interface for document storage and retrieval services.
 * 
 * @package Nexus\CRMOperations\Contracts
 */
interface DocumentServiceInterface
{
    /**
     * Create a new document
     * 
     * @param array<string, mixed> $data Document data
     * @return string Created document ID
     */
    public function create(array $data): string;

    /**
     * Update an existing document
     * 
     * @param string $documentId Document ID
     * @param array<string, mixed> $data Updated data
     */
    public function update(string $documentId, array $data): void;

    /**
     * Delete a document
     * 
     * @param string $documentId Document ID
     */
    public function delete(string $documentId): void;

    /**
     * Find document by ID
     * 
     * @param string $documentId Document ID
     * @return array<string, mixed>|null Document data or null if not found
     */
    public function findById(string $documentId): ?array;
}
