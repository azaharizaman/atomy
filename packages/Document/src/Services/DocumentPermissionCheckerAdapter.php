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
        $user = $this->userRepository->findById($userId);
        if (!$user) return false;
        return $this->identityPermissions->hasPermission($user, 'document.view');
    }

    public function canEdit(string $userId, string $documentId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) return false;
        return $this->identityPermissions->hasPermission($user, 'document.edit');
    }

    public function canDelete(string $userId, string $documentId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) return false;
        return $this->identityPermissions->hasPermission($user, 'document.delete');
    }

    public function canShare(string $userId, string $documentId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) return false;
        return $this->identityPermissions->hasPermission($user, 'document.share');
    }

    public function canCreateVersion(string $userId, string $documentId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) return false;
        return $this->identityPermissions->hasPermission($user, 'document.version');
    }
}
