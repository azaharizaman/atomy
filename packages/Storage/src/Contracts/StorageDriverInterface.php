<?php

declare(strict_types=1);

namespace Nexus\Storage\Contracts;

use Nexus\Storage\Exceptions\FileNotFoundException;
use Nexus\Storage\Exceptions\InvalidPathException;
use Nexus\Storage\Exceptions\StorageException;
use Nexus\Storage\ValueObjects\FileMetadata;
use Nexus\Storage\ValueObjects\Visibility;

/**
 * StorageDriverInterface defines the contract for file storage operations.
 *
 * This interface abstracts the underlying storage system (local disk, S3, Azure Blob, etc.)
 * and provides a consistent API for file operations. All implementations MUST prioritize
 * PHP stream handling for memory efficiency when dealing with large files.
 *
 * @package Nexus\Storage\Contracts
 */
interface StorageDriverInterface
{
    /**
     * Store a file at the specified path.
     *
     * @param string $path The storage path (e.g., 'invoices/2024/file.pdf')
     * @param resource|string $contents The file contents as a stream resource or string
     * @param Visibility $visibility The file visibility (public or private)
     *
     * @return bool True on success
     *
     * @throws InvalidPathException If the path contains invalid characters or patterns
     * @throws StorageException If the storage operation fails
     */
    public function put(string $path, mixed $contents, Visibility $visibility = Visibility::Private): bool;

    /**
     * Retrieve a file's contents as a stream.
     *
     * @param string $path The storage path
     *
     * @return resource A stream resource for reading the file
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws StorageException If the retrieval operation fails
     */
    public function get(string $path): mixed;

    /**
     * Check if a file exists at the specified path.
     *
     * @param string $path The storage path
     *
     * @return bool True if the file exists, false otherwise
     *
     * @throws StorageException If the check operation fails
     */
    public function exists(string $path): bool;

    /**
     * Delete a file at the specified path.
     *
     * @param string $path The storage path
     *
     * @return bool True on success
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws StorageException If the deletion operation fails
     */
    public function delete(string $path): bool;

    /**
     * Get metadata for a file.
     *
     * @param string $path The storage path
     *
     * @return FileMetadata The file metadata
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws StorageException If the operation fails
     */
    public function getMetadata(string $path): FileMetadata;

    /**
     * Set the visibility of a file.
     *
     * @param string $path The storage path
     * @param Visibility $visibility The desired visibility
     *
     * @return bool True on success
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws StorageException If the operation fails
     */
    public function setVisibility(string $path, Visibility $visibility): bool;

    /**
     * Create a directory at the specified path.
     *
     * @param string $path The directory path
     *
     * @return bool True on success
     *
     * @throws InvalidPathException If the path is invalid
     * @throws StorageException If the operation fails
     */
    public function createDirectory(string $path): bool;

    /**
     * List all files in a directory.
     *
     * @param string $path The directory path
     * @param bool $recursive Whether to list files recursively
     *
     * @return array<FileMetadata> Array of file metadata objects
     *
     * @throws StorageException If the operation fails
     */
    public function listFiles(string $path, bool $recursive = false): array;

    /**
     * Copy a file from one location to another.
     *
     * @param string $source The source path
     * @param string $destination The destination path
     *
     * @return bool True on success
     *
     * @throws FileNotFoundException If the source file does not exist
     * @throws StorageException If the operation fails
     */
    public function copy(string $source, string $destination): bool;

    /**
     * Move a file from one location to another.
     *
     * @param string $source The source path
     * @param string $destination The destination path
     *
     * @return bool True on success
     *
     * @throws FileNotFoundException If the source file does not exist
     * @throws StorageException If the operation fails
     */
    public function move(string $source, string $destination): bool;
}
