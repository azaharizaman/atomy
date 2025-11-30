<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Permission checker interface for document access control.
 *
 * Defines authorization methods for document operations.
 * Implementation should check ownership, roles, and explicit sharing.
 */
interface PermissionCheckerInterface
{
    /**
     * Check if a user can view a document.
     *
     * @param string $userId User ULID
     * @param string $documentId Document ULID
     */
    public function canView(string $userId, string $documentId): bool;

    /**
     * Check if a user can edit a document.
     *
     * @param string $userId User ULID
     * @param string $documentId Document ULID
     */
    public function canEdit(string $userId, string $documentId): bool;

    /**
     * Check if a user can delete a document.
     *
     * @param string $userId User ULID
     * @param string $documentId Document ULID
     */
    public function canDelete(string $userId, string $documentId): bool;

    /**
     * Check if a user can share a document with others.
     *
     * @param string $userId User ULID
     * @param string $documentId Document ULID
     */
    public function canShare(string $userId, string $documentId): bool;

    /**
     * Check if a user can create a new version of a document.
     *
     * @param string $userId User ULID
     * @param string $documentId Document ULID
     */
    public function canCreateVersion(string $userId, string $documentId): bool;
}
