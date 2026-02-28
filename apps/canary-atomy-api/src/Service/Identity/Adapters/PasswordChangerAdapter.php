<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use App\Repository\UserRepository;
use Nexus\IdentityOperations\Services\PasswordChangerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordChangerAdapter implements PasswordChangerInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function changeWithVerification(string $userId, string $currentPassword, string $newPassword): void
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new \RuntimeException('Invalid current password');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $this->userRepository->save($user);
    }

    public function resetByAdmin(string $userId, string $newPassword): void
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $this->userRepository->save($user);
    }
}
