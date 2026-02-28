<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use App\Repository\UserRepository;
use Nexus\IdentityOperations\Services\UserStateManagerInterface;
use Nexus\Identity\ValueObjects\UserStatus;

final readonly class UserStateManagerAdapter implements UserStateManagerInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function suspend(string $userId): void
    {
        $user = $this->userRepository->find($userId);
        if ($user) {
            $user->setStatus(UserStatus::SUSPENDED);
            $this->userRepository->save($user);
        }
    }

    public function activate(string $userId): void
    {
        $user = $this->userRepository->find($userId);
        if ($user) {
            $user->setStatus(UserStatus::ACTIVE);
            $this->userRepository->save($user);
        }
    }

    public function deactivate(string $userId): void
    {
        $user = $this->userRepository->find($userId);
        if ($user) {
            $user->setStatus(UserStatus::DEACTIVATED);
            $this->userRepository->save($user);
        }
    }

    public function setAccessEnabled(string $userId, bool $enabled): void
    {
        // For now, this is tied to status.
        // If we want a separate 'access_enabled' flag, we'd need to add it to the User entity.
    }
}
