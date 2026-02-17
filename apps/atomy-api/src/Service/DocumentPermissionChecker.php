<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\AuthContextInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface as IdentityPermissionChecker;

/**
 * Robust document permission checker integrated with Nexus Identity.
 */
final readonly class DocumentPermissionChecker implements PermissionCheckerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthContextInterface $authContext,
        private IdentityPermissionChecker $identityPermissions
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function canView(string $userId, string $documentId): bool
    {
        $document = $this->getDocument($documentId);
        if (!$document) {
            return false;
        }

        // 1. Owner can always view
        if ($document->getOwnerId() === $userId) {
            return true;
        }

        // 2. Check for explicit view permission in Identity system
        return $this->identityPermissions->hasPermission(
            $userId,
            'document.view',
            ['document_id' => $documentId, 'tenant_id' => $document->getTenantId()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function canEdit(string $userId, string $documentId): bool
    {
        $document = $this->getDocument($documentId);
        if (!$document) {
            return false;
        }

        // Only editable in certain states
        if (!$document->getState()->isEditable()) {
            return false;
        }

        // 1. Owner can edit
        if ($document->getOwnerId() === $userId) {
            return true;
        }

        // 2. Roles/Explicit permission
        return $this->identityPermissions->hasPermission(
            $userId,
            'document.edit',
            ['document_id' => $documentId, 'tenant_id' => $document->getTenantId()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function canDelete(string $userId, string $documentId): bool
    {
        $document = $this->getDocument($documentId);
        if (!$document) {
            return false;
        }

        // 1. Check for global delete permission
        return $this->identityPermissions->hasPermission(
            $userId,
            'document.delete',
            ['document_id' => $documentId, 'tenant_id' => $document->getTenantId()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function canShare(string $userId, string $documentId): bool
    {
        $document = $this->getDocument($documentId);
        if (!$document) {
            return false;
        }

        // 1. Owner can share
        if ($document->getOwnerId() === $userId) {
            return true;
        }

        return $this->identityPermissions->hasPermission(
            $userId,
            'document.share',
            ['document_id' => $documentId, 'tenant_id' => $document->getTenantId()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function canCreateVersion(string $userId, string $documentId): bool
    {
        // Same as edit permission
        return $this->canEdit($userId, $documentId);
    }

    private function getDocument(string $id): ?Document
    {
        return $this->entityManager->getRepository(Document::class)->find($id);
    }
}
