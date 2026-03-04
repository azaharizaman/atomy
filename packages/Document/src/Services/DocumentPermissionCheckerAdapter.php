<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\PermissionCheckerInterface as IdentityPermissionChecker;
use Nexus\Identity\Contracts\UserRepositoryInterface;

/**
 * Adapter for Document package to use the Identity PermissionChecker.
 */
final readonly class DocumentPermissionCheckerAdapter implements PermissionCheckerInterface
{
    public function __construct(
        private IdentityPermissionChecker $identityPermissions,
        private UserRepositoryInterface $userRepository,
        private DocumentRepositoryInterface $documentRepository
    ) {
    }

    public function canView(string $userId, string $documentId): bool
    {
        return $this->hasResourcePermission($userId, $documentId, 'view');
    }

    public function canEdit(string $userId, string $documentId): bool
    {
        return $this->hasResourcePermission($userId, $documentId, 'edit');
    }

    public function canDelete(string $userId, string $documentId): bool
    {
        return $this->hasResourcePermission($userId, $documentId, 'delete');
    }

    public function canShare(string $userId, string $documentId): bool
    {
        return $this->hasResourcePermission($userId, $documentId, 'share');
    }

    public function canCreateVersion(string $userId, string $documentId): bool
    {
        return $this->hasResourcePermission($userId, $documentId, 'version');
    }

    private function hasResourcePermission(string $userId, string $documentId, string $action): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return false;
        }

        // Global override for admins/system
        if ($this->identityPermissions->hasPermission($user, "document.{$action}.all")) {
            return true;
        }

        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            return false;
        }

        // Check ownership
        if ($document->getOwnerId() === $userId) {
            return true;
        }

        // Delegate to identity system for specific resource access (e.g. via shared access tables)
        return $this->identityPermissions->hasPermission($user, "document.{$action}.{$documentId}");
    }
}
