<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use App\Entity\User;
use App\Repository\UserRepository;
use Nexus\IdentityOperations\Services\TenantUserAssignerInterface;
use Nexus\IdentityOperations\Services\UserCreatorInterface;
use Nexus\IdentityOperations\Services\UserUpdaterInterface;
use Nexus\IdentityOperations\DTOs\UserUpdateRequest;
use Nexus\Identity\ValueObjects\UserStatus;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserOnboardingAdapter implements UserCreatorInterface, UserUpdaterInterface, TenantUserAssignerInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function create(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null
    ): string {
        $user = new User($email);
        $user->setName(trim($firstName . ' ' . $lastName));
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setStatus(UserStatus::PENDING_ACTIVATION);
        
        $this->userRepository->save($user);

        return $user->getId();
    }

    public function update(UserUpdateRequest $request): void
    {
        $user = $this->userRepository->find($request->userId);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($request->email !== null) {
            $user->setEmail($request->email);
        }

        if ($request->firstName !== null || $request->lastName !== null) {
            $nameParts = explode(' ', $user->getName() ?? '', 2);
            $firstName = $request->firstName ?? $nameParts[0] ?? '';
            $lastName = $request->lastName ?? $nameParts[1] ?? '';
            $user->setName(trim($firstName . ' ' . $lastName));
        }

        $this->userRepository->save($user);
    }

    public function assignTenantRoles(string $userId, string $tenantId, array $roles): string
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $user->setTenantId($tenantId);
        $user->setRoles($roles);
        
        $this->userRepository->save($user);

        return $userId; // In this simple implementation, tenantUserId is the same as userId
    }
}
