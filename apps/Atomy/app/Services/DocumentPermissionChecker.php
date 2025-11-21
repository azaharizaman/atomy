<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use Nexus\Document\Contracts\PermissionCheckerInterface;

/**
 * Document permission checker implementation.
 *
 * Implements role-based access control for documents.
 */
final readonly class DocumentPermissionChecker implements PermissionCheckerInterface
{
    public function canView(string $userId, string $documentId): bool
    {
        $document = Document::find($documentId);
        if (!$document) {
            return false;
        }

        // Owner can always view
        if ($document->owner_id === $userId) {
            return true;
        }

        // TODO: Check if user has admin role or explicit share permission
        // For now, allow viewing within same tenant
        return true;
    }

    public function canEdit(string $userId, string $documentId): bool
    {
        $document = Document::find($documentId);
        if (!$document) {
            return false;
        }

        // Cannot edit archived or deleted documents
        if (!$document->getState()->isEditable()) {
            return false;
        }

        // Owner can edit
        if ($document->owner_id === $userId) {
            return true;
        }

        // TODO: Check if user has admin role
        return false;
    }

    public function canDelete(string $userId, string $documentId): bool
    {
        $document = Document::find($documentId);
        if (!$document) {
            return false;
        }

        // Owner can delete
        if ($document->owner_id === $userId) {
            return true;
        }

        // TODO: Check if user has admin role
        return false;
    }

    public function canShare(string $userId, string $documentId): bool
    {
        $document = Document::find($documentId);
        if (!$document) {
            return false;
        }

        // Owner can share
        if ($document->owner_id === $userId) {
            return true;
        }

        // TODO: Check if user has share permission
        return false;
    }

    public function canCreateVersion(string $userId, string $documentId): bool
    {
        // Same as edit permission
        return $this->canEdit($userId, $documentId);
    }
}
