<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

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
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function canView(string $userId, string $documentId): bool
    {
        return $this->hasPermissionForUser($userId, 'document.view');
    }

    public function canEdit(string $userId, string $documentId): bool
    {
        return $this->hasPermissionForUser($userId, 'document.edit');
    }

    public function canDelete(string $userId, string $documentId): bool
    {
        return $this->hasPermissionForUser($userId, 'document.delete');
    }

    public function canShare(string $userId, string $documentId): bool
    {
        return $this->hasPermissionForUser($userId, 'document.share');
    }

    public function canCreateVersion(string $userId, string $documentId): bool
    {
        return $this->hasPermissionForUser($userId, 'document.version');
    }

    private function hasPermissionForUser(string $userId, string $permission): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return false;
        }
        return $this->identityPermissions->hasPermission($user, $permission);
    }
}
